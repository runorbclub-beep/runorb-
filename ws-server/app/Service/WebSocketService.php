<?php

namespace App\Service;


use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\PkController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\WebsocketController;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\WebSocket\Server;
use SwooleTW\Http\Websocket\Facades\Websocket;

class WebSocketService implements WebSocketHandlerInterface
{

    public function __construct()
    {
    }

    public function onOpen(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request)
    {
        
        // Use raw Redis connection
        static $redis = null;
        if ($redis === null) {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
        }
        $redis->select(13);

        $fd = $request->fd;

        // TODO: Implement onOpen() method.
        error_log('WebSocket 连接建立：' . $fd);

        $_language = $request->header['language'] != null ? $request->header['language'] : 'zh-CN';

        $_arrOfPkData = json_decode($request->header['pkdata'], true);

        error_log("链接时PK数据：" . json_encode($_arrOfPkData));

        $_test_user_play = isset($_arrOfPkData["test_user_play"]) ? $_arrOfPkData["test_user_play"] : "";
        if ($_test_user_play != "") {
//            存储数据
            $redis->setex($_test_user_play, 3600 * 24, $fd);
            return true;
        }

        $_pk_room_id = isset($_arrOfPkData["pk_room_id"]) ? $_arrOfPkData["pk_room_id"] : "";

//        当前PK的用户数据
        $_arrOfPkRoom = json_decode($redis->get($_pk_room_id), true);


//        socket ID存储时间为2小时
        $redis->select(12);
        $redis->setex($fd, 3600 * 5, json_encode($_arrOfPkData));


//        未找到房间
        if (!$_arrOfPkRoom) {

            error_log("未找到房间数据：" . $fd);
            $_return_arr = array(
                "code" => 0,
                "event" => "error",
                "msg" => LanguageControllercron::getLanguage($_language, "not_found")
            );

            $server->push($request->fd, json_encode($_return_arr));

            return true;
        }


        
        // Use raw Redis connection
        static $redis = null;
        if ($redis === null) {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
        }
        $redis->select(13);

//        如果在redis缓存中存在
        if (isset($_arrOfPkRoom[$_arrOfPkData["user_group"]]) && isset($_arrOfPkRoom[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]])) {
            $_arrOfPkRoom[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]]["fd"] = $fd;

            error_log("链接用户写入Redis缓存：" . $_arrOfPkRoom[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]]["fd"]);
//            存入缓存
            $redis->setex($_pk_room_id, 3600 * 24, json_encode($_arrOfPkRoom));

//            发送消息
            self::sendMsg($_arrOfPkRoom, "pkListChange", $server);
        }

        $_return_arr = array(
            "code" => 1,
            "msg" => "success",
            "event" => "connection_success",
            "data" => array(
                "fd" => $fd
            )
        );

        $server->push($request->fd, json_encode($_return_arr));

        error_log("websocket连接成功：" . $fd);

        return true;

    }

    public function onMessage(\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame)
    {
        // TODO: Implement onMessage() method.
        // 调用 push 方法向客户端推送数据
        error_log('WebSocket 接收消息：' . $frame->fd . "：" . $frame->data);
//        $server->push($frame->fd, 'WebSocket 接收消息：' . date('Y-m-d H:i:s'));

        $_data = json_decode($frame->data, true);
        if (isset($_data["event"]) && $_data["event"] == "heartbeat") {
            return true;
        }


        if (isset($_data["event"])) {
            $_msg_event = "";

            switch ($_data["event"]) {
                case "between_play"://运动过程中传输数据
                    $_msg_event = PkController::butweenPlay($_data);

//                    存储数据出错
                    if ($_msg_event == "between_play_error") {
                        $_return_arr = array(
                            "code" => 1,
                            "msg" => "success",
                            "data" => array(
                                "event" => $_msg_event,
                                "msg" => "User Not Found"
                            )
                        );

                        $server->push($frame->fd, json_encode($_return_arr));
                    }
                    break;
                case "pk_ready"://用户准备PK
                    $_msg_event = PkController::pkReady($_data);
                    break;
                case "pk_unready"://用户取消准备
                    $_msg_event = PkController::pkUnReady($_data);
                    break;
                case "pk_stop"://用户结束PK
                    $_msg_event = PkController::pkStop($_data);
                    break;
                case "heartbeat"://长连接心跳包
                    $_msg_event = "heartbeat";
                    break;
                case "bind_again"://断线重连
                    $_msg_event = "bind_again";
                    $_arrOfPkRoom = json_decode($redis->get($_data["pk_room_id"]), true);

                    if ($_arrOfPkRoom["status"] == 2) { //进行中的不需要加9秒倒计时
                        $_new_time_long = time() - $_arrOfPkRoom["pk_start_time"];
                    } else {
                        $_new_time_long = time() - $_arrOfPkRoom["pk_start_time"] + 9;
                    }
                    $_arrOfPkRoom["time_long"] = $_new_time_long;

                    $_red_circle_count = 0;
                    $_blue_circle_count = 0;
                    foreach ($_arrOfPkRoom["red"] as $node) {
                        $_red_circle_count += $node["circle_count"][count($node["circle_count"]) - 1];
                    }

                    foreach ($_arrOfPkRoom["blue"] as $node) {
                        $_blue_circle_count += $node["circle_count"][count($node["circle_count"]) - 1];
                    }

//                    双方队伍已运动距离
                    $_arrOfPkRoom["red_distance"] = round($_red_circle_count * StaticDataController::$_circle_distance / 100 / 1000, 3);
                    $_arrOfPkRoom["blue_distance"] = round($_blue_circle_count * StaticDataController::$_circle_distance / 100 / 1000, 3);
                    $_arrOfPkRoom["user_group"] = $_data["user_group"];

                    $_return_data = array(
                        "code" => 1,
                        "msg" => "success",
                        "data" => array(
                            "event" => $_msg_event,
                            "list" => $_arrOfPkRoom
                        )
                    );

                    $server->push($frame->fd, json_encode($_return_data));
                    break;
                default://用户退出PK
                    $_msg_event = PkController::pkCancel($_data);
            }

            error_log("接收用户消息，触发事件：" . $_msg_event);

            
        // Use raw Redis connection
        static $redis = null;
        if ($redis === null) {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
        }
        $redis->select(13);

            $_arrOfPkRoom = json_decode($redis->get($_data["pk_room_id"]), true);

            self::sendMsg($_arrOfPkRoom, $_msg_event, $server);

        }

        return true;
    }

    public function onClose(\Swoole\WebSocket\Server $server, $fd, $reactorId)
    {
        // TODO: Implement onClose() method.
        error_log('WebSocket 连接关闭：' . $fd);

//        $redis->select(12);
//        $_arrOfPkData = json_decode($redis->get($fd),true);
//        error_log("socket 链接数据：".json_encode($_arrOfPkData));
//
//        if($_arrOfPkData!=null){
//            
        // Use raw Redis connection
        static $redis = null;
        if ($redis === null) {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
        }
        $redis->select(13);

//            $_pkRoomData = json_decode($redis->get($_arrOfPkData["pk_room_id"]),true);
//            error_log("pk房间数据：".json_encode($_pkRoomData));
//
//            $_pkRoomData[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]]["fd"] = 0;
//            $redis->setex($_arrOfPkData["pk_room_id"],3600*24,json_encode($_pkRoomData));
//
//            $_message = $_pkRoomData[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]]["user_name"]." Drops";
//            self::sendMsg($_pkRoomData,"user_drops",$server,$_message);
//
//            Log::error($_message);
//        }
    }


    public function sendMsg($_arrOfPkRoom, $_msg_event, $server, $_message = "success")
    {

        $_return_data = array(
            "code" => 1,
            "msg" => $_message,
            "data" => array(
                "event" => $_msg_event
            )
        );

//        去除键
        $_arrOfPkRoom["red"] = isset($_arrOfPkRoom["red"]) ? array_values($_arrOfPkRoom["red"]) : array();
        $_arrOfPkRoom["blue"] = isset($_arrOfPkRoom["blue"]) ? array_values($_arrOfPkRoom["blue"]) : array();

//        PK列表变更
        if ($_msg_event == "between_play" || $_msg_event == "heartbeat" || $_msg_event == "pkResult") {
//            运动过程中、心跳包、断开重连，直接退出
            return true;
        } else if ($_msg_event == "pkListChange" || $_msg_event == "pkStart") {
            if ($_arrOfPkRoom['status'] == 2) { //比赛进行中
                $_arrOfPkRoom["time_long"] = $_arrOfPkRoom["pk_stop_time"] - time();
            }
            $_return_data["data"]["list"] = $_arrOfPkRoom;
        }
//        else if($_msg_event=="pkResult"){
//
//            $_return_data["data"]["list"] = PkController::pkResult($_arrOfPkRoom["pk_room_id"]);
//        }

        error_log("服务端广播事件：" . $_msg_event);


//        通知红队
        if (isset($_arrOfPkRoom["red"])) {
            foreach ($_arrOfPkRoom["red"] as $key => $value) {
                if (isset($value["fd"]) && $value["fd"] != 0) {
                    $_return_data["data"]["list"]["user_group"] = "red";

                    error_log("链接客户端");
                    error_log("通知红队fd:".$value["fd"]."==数据记录：".json_encode($_return_data));
                    $server->push($value["fd"], json_encode($_return_data));
                }
            }
        }


//        通知蓝队
        if (isset($_arrOfPkRoom["blue"])) {
            foreach ($_arrOfPkRoom["blue"] as $key => $value) {
                if (isset($value["fd"]) && $value["fd"] != 0) {
                    $_return_data["data"]["list"]["user_group"] = "blue";
                    error_log("通知蓝队fd:".$value["fd"]."==数据记录：".json_encode($_return_data));
                    $server->push($value["fd"], json_encode($_return_data));
                }
            }
        }

        return true;
    }


}
