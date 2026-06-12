<?php

namespace App\Http\Controllers\PublicFunction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SwooleTW\Http\Websocket\Facades\Websocket;
use Swoole\WebSocket\Server;


/**
 * @author pengjl
 * Class WebsocketController
 * @package App\Http\Controllers\PublicFunction
 */
class WebsocketController
{


    /**
     * @abstract 用户加入PK，建立Redis数据缓存
     * @param $_user_pk_list_data
     */
    public static function userPkSocket($_user_pk_list_data){

        Redis::select(13);

        $_pk_room_id = $_user_pk_list_data["pk_room_id"];

        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id),true);

//        如果没有数据
        if(!$_arrOfPkRoom){
            $_arrOfPkRoom = array(
                "red"=>array(

                ),
                "blue"=>array(

                )
            );
        }

        if(strstr($_user_pk_list_data["user_img"],'http')===false){
            $_user_pk_list_data["user_img"] = StaticDataController::$_server_url."/".$_user_pk_list_data["user_img"];
        }

//        重载键值
        $_arrOfPkRoom[$_user_pk_list_data["user_group"]][$_user_pk_list_data["user_id"]] = array(
            "user_pk_list_id"=>$_user_pk_list_data["user_pk_list_id"],
            "user_id"=>$_user_pk_list_data["user_id"],
            "pk_room_id"=>$_user_pk_list_data["pk_room_id"],
            "fd"=>0,//会话ID
            "is_stop"=>0,//用户是否结束运动
            "is_ready"=>0,//用户是否准备运动
            "user_group"=>$_user_pk_list_data["user_group"],//用户组
            "user_name"=>$_user_pk_list_data["user_name"],//用户姓名
            "user_img"=>$_user_pk_list_data["user_img"],//用户姓名
        );


//        存入缓存
        Redis::setex($_pk_room_id,3600*24,json_encode($_arrOfPkRoom));

//        广播通知用户
//        self::sentStatusToUser($_pk_room_id,"pkListChange");

        $_arrOfPkRoom["red"] = array_values($_arrOfPkRoom["red"]);
        $_arrOfPkRoom["blue"] = array_values($_arrOfPkRoom["blue"]);
        return $_arrOfPkRoom;
    }


    /**
     * @abstract 用户切换队伍
     * @param $_user_pk_list_data
     */
    public static function userPkSocketChangeGroup($_user_pk_list_data){
        Redis::select(13);

        $_pk_room_id = $_user_pk_list_data["pk_room_id"];

        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id),true);

//        如果没有数据
        if(!$_arrOfPkRoom){
            $_arrOfPkRoom = array(
                "red"=>array(

                ),
                "blue"=>array(

                )
            );
        }

//        如果新队伍是红队
        if($_user_pk_list_data["user_group"] == "red"){
            $_arrOfOldData = $_arrOfPkRoom["blue"][$_user_pk_list_data["user_id"]];
            unset($_arrOfPkRoom["blue"][$_user_pk_list_data["user_id"]]);
        }else{
            $_arrOfOldData = $_arrOfPkRoom["red"][$_user_pk_list_data["user_id"]];
            unset($_arrOfPkRoom["red"][$_user_pk_list_data["user_id"]]);
        }

        $_arrOfOldData["user_group"] = $_user_pk_list_data["user_group"];
        $_arrOfOldData["is_ready"] = 0;//切换队伍后，需重新准备


//        重载键值
        $_arrOfPkRoom[$_user_pk_list_data["user_group"]][$_user_pk_list_data["user_id"]] = $_arrOfOldData;

//        存入缓存
        Redis::setex($_pk_room_id,3600*24,json_encode($_arrOfPkRoom));

//        广播通知用户
//        self::sentStatusToUser($_pk_room_id,"pkListChange");

        return;
    }

    /**
     * @abstract 用户退出PK
     * @param $_user_pk_list_data
     */
    public static function userClosePkSocket($_user_pk_list_data){
        Redis::select(13);

        $_pk_room_id = $_user_pk_list_data["pk_room_id"];

        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id),true);

//        如果没有数据
        if(!$_arrOfPkRoom){
            $_arrOfPkRoom = array(
                "red"=>array(

                ),
                "blue"=>array(

                )
            );
        }

        unset($_arrOfPkRoom["red"][$_user_pk_list_data["user_id"]]);
        unset($_arrOfPkRoom["blue"][$_user_pk_list_data["user_id"]]);

//        存入缓存
        Redis::setex($_pk_room_id,3600*24,json_encode($_arrOfPkRoom));

//        广播通知用户
//        self::sentStatusToUser($_pk_room_id,"pkListChange");
        return;
    }

}
