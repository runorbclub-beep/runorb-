<?php


namespace App\Http\Controllers\Admin\User;


use App\Http\Controllers\Api\UserPlayController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\SystemErrorController;

class AdminUserPlayController extends Controller
{

    public function postUserPlayInfo(){

        $_data = request()->input();
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["user_play_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_user_play = UserPlayController::getPlayDetail($_data["user_play_id"],true);

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_user_play
        );

    }
}
