<?php


namespace App\Jobs\Timer;


use App\Http\Controllers\PublicFunction\PkController;
use Hhxsv5\LaravelS\Swoole\Timer\CronJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PkResultCronHob extends CronJob
{

    protected $i = 0;

// 每隔 1000ms 执行一次任务
    public function interval()
    {
        return 1000;   // 定时器间隔，单位为 ms
    }

    // 是否在设置之后立即触发 run 方法执行
    public function isImmediate()
    {
        // 是否立即执行第一次，false则等待间隔时间后执行第一次
        return false;
    }


    public function run()
    {

        Redis::select(1);
        $_list_len = Redis::llen("user_pk_data");

        $_arrOfPkRoomId = array();
        for ($_i = 0;$_i<$_list_len;$_i++){
            Redis::select(1);
            $_user_pk_data = json_decode(Redis::lpop("user_pk_data"),true);


            Log::info("pkResult".json_encode($_user_pk_data));
            if($_user_pk_data!=null){
                Redis::select(13);
                if(!in_array($_user_pk_data["pk_room_id"],$_arrOfPkRoomId)){
                    array_push($_arrOfPkRoomId,$_user_pk_data["pk_room_id"]);
                }

                $_arrOfPkRoom = json_decode(Redis::get($_user_pk_data["pk_room_id"]),true);

                if(array_key_exists($_user_pk_data["user_id"],$_arrOfPkRoom[$_user_pk_data["user_group"]])){
                    $_arrOfPkRoom[$_user_pk_data["user_group"]][$_user_pk_data["user_id"]]["is_stop"] = 1;
                }
//                存入数据
                Redis::setex($_user_pk_data["pk_room_id"],3600*24,json_encode($_arrOfPkRoom));
            }
        }


        Redis::select(13);
        foreach ($_arrOfPkRoomId as $value){
            $_arrOfPkRoom = json_decode(Redis::get($value),true);

            $_arrOfPkGroupRed = $_arrOfPkRoom["red"];
            $_arrOfPkGroupBlue = $_arrOfPkRoom["blue"];

            $_is_stop = 1;

            $_arrOfStopRed = array();
            foreach ($_arrOfPkGroupRed as $node){
                array_push($_arrOfStopRed,$node["is_stop"]);
            }
            Log::info("红队状态：".json_encode($_arrOfStopRed));

            $_arrOfStopBlue = array();
            foreach ($_arrOfPkGroupBlue as $node){
                array_push($_arrOfStopBlue,$node["is_stop"]);
            }

            Log::info("蓝队状态：".json_encode($_arrOfStopRed));
            if(in_array(0,$_arrOfStopRed) || in_array(0,$_arrOfStopBlue)){
                $_is_stop = 0;
            }

            Log::info("PK状态：".$_is_stop);

            if($_is_stop==1  || ($value["status"] == 2 && (count($_arrOfStopRed)==0 || count($_arrOfStopBlue) ==0))){
                self::CrontabPkResult($value);
                return "pkResult";
            }
        }

        return true;
    }

    public static function CrontabPkResult($_pk_room_id){

        Log::info("触发结束----------------------------------------------------------");
        Redis::select(13);

        $swoole = app('swoole');

        $_return_data = array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "event"=>"pkResult",
                "list"=>PkController::pkResult($_pk_room_id)
            )
        );

        Log::info("PK结果广播：".json_encode($_return_data));

        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id),true);
//        通知红队
        if(isset($_arrOfPkRoom["red"])){
            foreach ($_arrOfPkRoom["red"] as $key=>$value){
                if(isset($value["fd"]) && $value["fd"]!=0){
                    $_return_data["data"]["list"]["user_group"] = "red";
                    if($swoole->isEstablished($value["fd"])){
                        $swoole->push($value["fd"],json_encode($_return_data));
                    }else {
                        Log::error("PK结果通知red出错：Err【".json_encode($_arrOfPkRoom["red"])."】");
                    }
                }
            }
        }

//        通知蓝队
        if(isset($_arrOfPkRoom["blue"])){
            foreach ($_arrOfPkRoom["blue"] as $key=>$value){
                if(isset($value["fd"]) && $value["fd"]!=0){
                    $_return_data["data"]["list"]["user_group"] = "blue";
                    if($swoole->isEstablished($value["fd"])){
                        $swoole->push($value["fd"],json_encode($_return_data));
                    }else {
                        Log::error("PK结果通知blue出错：Err【".json_encode($_arrOfPkRoom["red"])."】");
                    }
                }
            }
        }

        Log::info("Pk 房间数据：".json_encode($_arrOfPkRoom));

        return true;
    }
}
