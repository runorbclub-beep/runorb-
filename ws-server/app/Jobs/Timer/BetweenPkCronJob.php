<?php
namespace App\Jobs\Timer;

use App\Http\Controllers\PublicFunction\StaticDataController;
use Hhxsv5\LaravelS\Swoole\Server;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class BetweenPkCronJob extends CronJob{

    protected $i = 0;

// 每隔 5000ms 执行一次任务
    public function interval()
    {
        return 3000;   // 定时器间隔，单位为 ms
    }

    // 是否在设置之后立即触发 run 方法执行
    public function isImmediate()
    {
        // 是否立即执行第一次，false则等待间隔时间后执行第一次
        return false;
    }


    public function run()
    {
        // TODO: Implement run() method.
        $swoole = app('swoole');

//        Log::info("有效链接数：".count($swoole->connections));

        Redis::select(12);
        $_arrOfPkRoomId = array();
        foreach ($swoole->connections as $fd) {
//            获取可用的链接
            if($swoole->isEstablished($fd)){
                $_pk_data = json_decode(Redis::get($fd),true);

                if($_pk_data!=null && !in_array($_pk_data["pk_room_id"],$_arrOfPkRoomId)){
                    array_push($_arrOfPkRoomId,$_pk_data["pk_room_id"]);
                }

//                维持缓存
                $_redis_socket_data = Redis::get($fd);
                Redis::setex($fd,600,$_redis_socket_data);

            }
        }

//        遍历所有存在活跃用户的房间
        Redis::select(13);
        foreach ($_arrOfPkRoomId as $value){
            $_pk_room_data = json_decode(Redis::get($value),true);

//            pk状态为进行中，PK开始时间+9秒 小于当前时间
            if($_pk_room_data["status"] == 2 && $_pk_room_data["pk_start_time"]+9 < time()){
                $_red_circle_count = 0;
                $_red_speed = array(0,0,0,0,0);

                $_blue_circle_count = 0;
                $_blue_speed = array(0,0,0,0,0);
                foreach ($_pk_room_data["red"] as $node){
                    $_red_circle_count += $node["circle_count"][count($node["circle_count"])-1];
                    $_red_speed = $node["speed"][count($node["speed"])-1]>$_red_speed[count($_red_speed)-1]?$node["speed"]:$_red_speed;
                }

                foreach ($_pk_room_data["blue"] as $node){
                    $_blue_circle_count += $node["circle_count"][count($node["circle_count"])-1];
                    $_blue_speed = $node["speed"][count($node["speed"])-1]>$_blue_speed[count($_blue_speed)-1]?$node["speed"]:$_blue_speed;
                }

                foreach ($_pk_room_data["red"] as $node){
                    $_return_data = array(
                        "code"=>1,
                        "msg"=>"success",
                        "data"=>array(
                            "event"=>"between_play",
                            "list"=>array(
                                "user_group"=>"red",
                                "red_distance"=>round($_red_circle_count*StaticDataController::$_circle_distance/100/1000,3),
                                "red_speed"=>$_red_speed,
                                "blue_distance"=>round($_blue_circle_count*StaticDataController::$_circle_distance/100/1000,3),
                                "blue_speed"=>$_blue_speed,
                            )
                        )
                    );

                    if(isset($node["fd"])){
                        $swoole->push($node["fd"],json_encode($_return_data));
                    }
                }

                foreach ($_pk_room_data["blue"] as $node){
                    $_return_data = array(
                        "code"=>1,
                        "msg"=>"success",
                        "data"=>array(
                            "event"=>"between_play",
                            "list"=>array(
                                "user_group"=>"blue",
                                "red_distance"=>round($_red_circle_count*StaticDataController::$_circle_distance/100/1000,3),
                                "red_speed"=>$_red_speed,
                                "blue_distance"=>round($_blue_circle_count*StaticDataController::$_circle_distance/100/1000,3),
                                "blue_speed"=>$_blue_speed,
                            )
                        )
                    );
                    if(isset($node["fd"])){
                        $swoole->push($node["fd"],json_encode($_return_data));
                    }
                }
            }

//            PK状态为进行中，且开始时间，加PK时长，加倒计时长 大于当前时间，服务端判定PK结束
            if($_pk_room_data["status"] == 2 && $_pk_room_data["pk_start_time"]+9+$_pk_room_data["time_long"] < time()){
                PkResultCronHob::CrontabPkResult($_pk_room_data["pk_room_id"]);
            }
        }
        return true;
    }

}
