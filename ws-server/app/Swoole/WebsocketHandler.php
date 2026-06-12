<?php
namespace App\Swoole;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\HandlerContract;
use SwooleTW\Http\Websocket\SocketIO\Packet;

class WebsocketHandler implements HandlerContract
{

    public function onOpen($fd, Request $request)
    {
        /**
         * 客户端建立起长链接后，返回客户端fd
         */
        $this->server->push($fd, json_encode(['event' => 'open', 'data' => ['fd' => $fd]]));
        return true;
    }

    public function onMessage(Frame $frame)
    {
        $data = json_decode($frame->data, true);
        if (!$data || !isset($data['event'])) {
            return;
        }

        $event = $data['event'];
        $payload = $data['data'] ?? [];

        switch ($event) {
            case 'join_pk':
                // 用户加入PK房间，绑定fd到Redis
                $this->handleJoinPk($frame->fd, $payload);
                break;
            case 'leave_pk':
                // 用户离开PK房间
                $this->handleLeavePk($frame->fd, $payload);
                break;
            case 'pk_ready':
                // 用户准备
                $this->handlePkReady($frame->fd, $payload);
                break;
            case 'pk_unready':
                // 用户取消准备
                $this->handlePkUnReady($frame->fd, $payload);
                break;
            case 'between_play':
                // PK进行中数据上报
                $this->handleBetweenPlay($frame->fd, $payload);
                break;
            case 'pk_stop':
                // 用户完成PK
                $this->handlePkStop($frame->fd, $payload);
                break;
            case 'pk_cancel':
                // 用户退出PK
                $this->handlePkCancel($frame->fd, $payload);
                break;
            default:
                Log::info("Unknown WS event: " . $event);
                break;
        }
    }

    public function onClose($fd, $reactorId)
    {
        // 清理Redis中的fd绑定
        Redis::select(12);
        $pk_data = Redis::get($fd);
        if ($pk_data) {
            $pk_data = json_decode($pk_data, true);
            if ($pk_data && isset($pk_data['pk_room_id'])) {
                Redis::select(13);
                $room = json_decode(Redis::get($pk_data['pk_room_id']), true);
                if ($room) {
                    // 从房间中移除该用户的fd
                    foreach (['red', 'blue'] as $group) {
                        if (isset($room[$group])) {
                            foreach ($room[$group] as $user_id => $user) {
                                if (isset($user['fd']) && $user['fd'] == $fd) {
                                    $room[$group][$user_id]['fd'] = 0;
                                    break;
                                }
                            }
                        }
                    }
                    Redis::setex($pk_data['pk_room_id'], 3600 * 24, json_encode($room));
                }
            }
            Redis::del($fd);
        }
        Log::info("WebSocket closed: fd=" . $fd);
    }

    /**
     * 处理用户加入PK房间
     */
    private function handleJoinPk($fd, $payload)
    {
        Redis::select(12);
        Redis::setex($fd, 600, json_encode($payload));

        Redis::select(13);
        $room = json_decode(Redis::get($payload['pk_room_id']), true);
        if ($room && isset($room[$payload['user_group']][$payload['user_id']])) {
            $room[$payload['user_group']][$payload['user_id']]['fd'] = $fd;
            Redis::setex($payload['pk_room_id'], 3600 * 24, json_encode($room));
            Log::info("User joined PK: fd=" . $fd . " room=" . $payload['pk_room_id']);
        }
    }

    /**
     * 处理用户离开PK房间
     */
    private function handleLeavePk($fd, $payload)
    {
        Redis::select(12);
        Redis::del($fd);
    }

    /**
     * 处理用户准备
     */
    private function handlePkReady($fd, $payload)
    {
        $result = \App\Http\Controllers\PublicFunction\PkController::pkReady($payload);
        if ($result) {
            $this->pushToRoom($payload['pk_room_id'], $result);
        }
    }

    /**
     * 处理用户取消准备
     */
    private function handlePkUnReady($fd, $payload)
    {
        $result = \App\Http\Controllers\PublicFunction\PkController::pkUnReady($payload);
        if ($result) {
            $this->pushToRoom($payload['pk_room_id'], $result);
        }
    }

    /**
     * 处理PK进行中数据上报
     */
    private function handleBetweenPlay($fd, $payload)
    {
        $result = \App\Http\Controllers\PublicFunction\PkController::butweenPlay($payload);
        // 数据已更新到Redis，BetweenPkCronJob定时器会推送
    }

    /**
     * 处理用户完成PK
     */
    private function handlePkStop($fd, $payload)
    {
        $result = \App\Http\Controllers\PublicFunction\PkController::pkStop($payload);
        if ($result) {
            $this->pushToRoom($payload['pk_room_id'], $result);
        }
    }

    /**
     * 处理用户退出PK
     */
    private function handlePkCancel($fd, $payload)
    {
        $result = \App\Http\Controllers\PublicFunction\PkController::pkCancel($payload);
        if ($result) {
            $this->pushToRoom($payload['pk_room_id'], $result);
        }
        Redis::select(12);
        Redis::del($fd);
    }

    /**
     * 推送消息到PK房间内所有用户
     */
    private function pushToRoom($pk_room_id, $event_type)
    {
        Redis::select(13);
        $room = json_decode(Redis::get($pk_room_id), true);
        if (!$room) return;

        $_return_data = array(
            "code" => 1,
            "msg" => "success",
            "event" => $event_type,
            "data" => array()
        );

        if ($event_type == "pkListChange") {
            $room["red"] = array_values($room["red"]);
            $room["blue"] = array_values($room["blue"]);
            $_return_data["data"] = array(
                "event" => "pkListChange",
                "list" => $room
            );
        } elseif ($event_type == "pkStart") {
            $_return_data["data"] = array(
                "event" => "pkStart",
            );
        }

        $message = json_encode($_return_data);

        foreach (['red', 'blue'] as $group) {
            if (isset($room[$group])) {
                foreach ($room[$group] as $user) {
                    if (isset($user['fd']) && $user['fd'] != 0) {
                        if ($this->server->isEstablished($user['fd'])) {
                            $this->server->push($user['fd'], $message);
                        }
                    }
                }
            }
        }
    }
}

