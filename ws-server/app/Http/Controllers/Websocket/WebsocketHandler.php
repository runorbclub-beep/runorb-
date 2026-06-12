<?php


namespace App\Http\Controllers\Websocket;


use App\Http\Controllers\PublicFunction\LanguageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Websocket\Frame;
use SwooleTW\Http\Server\Facades\Server;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;
use SwooleTW\Http\Websocket\HandlerContract;

class WebsocketHandler implements HandlerContract
{

    public static function index(Request $request){

        $_data = array(
            "request"=>$request
        );
        Log::info("index：".json_encode($_data));
    }

    /**
     * @abstract websocket 监听用户连接
     * @param int $fd
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        // TODO: Implement onOpen() method.


        $websocket = Websocket::getFacadeRoot();
        Redis::select(13);
        $_language = $request->header("language")!=null?$request->header("language"):'zh-CN';

        $_arrOfPkData = json_decode($request->header("pkdata"),true);

        $_test_user_play = isset($_arrOfPkData["test_user_play"])?$_arrOfPkData["test_user_play"]:"";
        if($_test_user_play!=""){
//            存储数据
            Redis::setex($_test_user_play,3600*24,$fd);
            return true;
        }

        $_pk_room_id = isset($_arrOfPkData["pk_room_id"])?$_arrOfPkData["pk_room_id"]:"";

//        当前PK的用户数据
        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id),true);

//        未找到房间
        if(!$_arrOfPkRoom){
            $_return_arr = array(
                "code"=>0,
                "msg"=>LanguageController::getLanguage($_language,"not_found")
            );
            $websocket->setSender($fd);
            $websocket->to($fd)->emit("|error|",json_encode($_return_arr));
            return true;
        }

//        如果在redis缓存中存在
        if(isset($_arrOfPkRoom[$_arrOfPkData["user_group"]]) && isset($_arrOfPkRoom[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]])){
            $_arrOfPkRoom[$_arrOfPkData["user_group"]][$_arrOfPkData["user_id"]]["fd"] = $fd;

//            存入缓存
            Redis::setex($_pk_room_id,3600*24,json_encode($_arrOfPkRoom));
        }

        $_data = array(
            "fd"=>$fd,
            "token"=>$request->header("token"),
            "pkdata"=>json_decode($request->header("pkdata"),true),
        );

        Log::info("onOpen111：".json_encode($_data));

        return true;
    }

    public function onClose($fd, $reactorId)
    {

        $_data = array(
            "fd"=>$fd,
            "reactorId"=>$reactorId
        );
        Log::info("onClose333：".json_encode($_data));

        return true;
        // TODO: Implement onClose() method.
    }

    /**
     * @param Frame $frame
     * @return mixed
     */
    public function onMessage(Frame $frame)
    {

        $_data = array(
            "frame"=>$frame
        );
        Log::info("onMessage：".json_encode($_data));

        return true;
        // TODO: Implement onMessage() method.
    }
}
