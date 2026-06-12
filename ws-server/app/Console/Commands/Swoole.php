<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Swoole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
//    protected $signature = 'command:name';
    protected $signature = 'swoole {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
//    protected $description = 'Command description';
    protected $description = 'swoole';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $action = $this->argument('action');

        switch ($action) {
            case 'close':

                break;

            default:
                $this->start();
                break;
        }
    }


    public function start(){
        //创建websocket服务器对象，监听0.0.0.0:9502端口
        $this->ws = new \swoole_websocket_server("0.0.0.0", 9502);

        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            echo "client:{$request->fd} is 链接成功;\n";

            Log::info("websockeMsg:  sclient:{$request->fd} is 链接成功;\n");

            $_arrOfPushData = array(
                "code"=>1,
                "msg"=>"success",
                "data"=>array(
                    "client_id"=>$request->fd
                )
            );

            $ws->push($request->fd, json_encode($_arrOfPushData));
        });

        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $frame) {
            // echo "Message: {$frame->data}\n";
             $ws->push($frame->fd, "监听到消息： {$frame->data}");

            Log::info("websockeMsg:  监听到消息：{$frame->fd}： {$frame->data};\n");
            // var_dump($ws->connection_info($frame->fd));
            //fd绑定客户端传过来的标识uid
//            $ws->bind($frame->fd, $frame->data);

            if($frame->data=="start"){
                $clients = $this->ws->getClientList();

                $_str = json_encode($clients);
                Log::info("websockeMsg: 客户端列表 {$_str}\n");
                echo "Message: ".json_encode($clients)."\n";
                foreach ($clients as $v) {
                    $this->ws->push($v, 'server：开始游戏');

                    Log::info("websockeMsg:  发送给客户端：{$v}  内容：开始游戏;\n");
                }
            }
        });


        $this->ws->on('request', function ($request, $response) {
            // 接收http请求从post获取参数
            // 获取所有连接的客户端，验证uid给指定用户推送消息
            // token验证推送来源，避免恶意访问
            $clients = $this->ws->getClientList();
            $clientId = [];
            foreach ($clients as $value) {
                $clientInfo = $this->ws->connection_info($value);
                if (array_key_exists('uid', $clientInfo) && $clientInfo['uid'] == $request->post['s_id']) {
                    $clientId[] = $value;
                }
            }
            if (!empty($clientId)) {
                foreach ($clientId as $v) {
                    $this->ws->push($v, $request->post['info']);
                }
            }
        });

        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            echo "client:{$fd} is closed\n";

            Log::error("websockeMsg:  客户端断开连接：{$fd}\n");
        });

        $this->ws->start();

    }
}
