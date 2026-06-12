<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use SwooleTW\Http\Websocket\Facades\Websocket;

class testPlayController extends Controller
{


    /**
     * @abstract 获取模拟运动ID
     * @return array
     */
    public function getUserPlayId(){
        $sno = new Snowflake(StaticDataController::$_workId);

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "user_play_id"=>$sno->nextId()
            )
        );
    }


    /**
     * @abstract 存储模拟运动数据
     * @param Request $request
     * @return array
     */
    public function saveTestData(Request $request){
        Redis::select(10);
        $_data = $request->input();

        $_language = $request->header("language")!=null?$request->header("language"):'zh-CN';
        $_bluetooth_data = isset($_data["bluetooth_data"])?$_data["bluetooth_data"]:array();


        if(!isset($_data["user_play_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_redis_bluetooth_data = json_decode(Redis::get($_data["user_play_id"]),true);

        if(!$_redis_bluetooth_data){
            $_redis_bluetooth_data = array();
        }

        foreach ($_bluetooth_data as $value){
            array_push($_redis_bluetooth_data,$value);
        }

//        存入缓存
        Redis::setex($_data["user_play_id"],3600*24,json_encode($_redis_bluetooth_data));

        if(isset($_data["is_stop"]) && $_data["is_stop"]==1){
            Redis::lpush("test_play_data",json_encode($_redis_bluetooth_data));
        }

        return array(
            "code"=>1,
            "msg"=>"success"
        );

    }


    /**
     * @abstract 获取模拟运动数据
     * @param Request $request
     * @return array
     */
    public function getUserPlayData(Request $request){
        Redis::select(10);
        $_data = $request->input();

        $_language = $request->header("language")!=null?$request->header("language"):'zh-CN';
        if(!isset($_data["test_user_play"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_interval = isset($_data["interval"])?$_data["interval"]:500;


        $_redis_bluetooth_data = json_decode(Redis::lindex('test_play_data', 0),true);

        if(!$_redis_bluetooth_data){
            $_redis_bluetooth_data = array();
        }

        Redis::select(13);
        $fd = Redis::get($_data["test_user_play"]);

        $_websocket = Websocket::getFacadeRoot();

        $_websocket->setSender($fd);
        $_type = "sentTestData";

        for ($_i = 0;$_i<count($_redis_bluetooth_data);$_i++){

            $_websocket->to($fd)->emit("|".$_type."|", $_redis_bluetooth_data[$_i]);

            usleep($_interval*1000);
        }

//        foreach ($_redis_bluetooth_data as $value){
//            $_websocket->to($fd)->emit("|".$_type."|", $value);
//            sleep($_interval/1000);
//        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_redis_bluetooth_data
        );

    }
}
