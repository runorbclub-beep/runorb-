<?php

namespace App\Http\Controllers\Api\Members;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\UsrUser;
use Illuminate\Support\Facades\Redis;

class MembersController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/6/12 10:50
     * @abstract _报名会员申请前，获取会员相关描述信息
     */
    public function postBeforeMembers(){
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        Redis::select(1);
        $_members_title_default = json_decode(Redis::hget("members_title_default",$_language),true);

        if($_members_title_default == null || $_members_title_default == ""){
            $_members_title_default = StaticDataController::$_befor_members_title_default[$_language];

            Redis::hset("members_title_default",$_language,json_encode($_members_title_default));
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_members_title_default
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/12 11:10
     * @abstract _用户提交表单，申请加入成为会员
     */
    public function postMembersAdd(){
        $_data = request()->input();
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user",request()->header("token")),true);


        $_arrOfUserMembersData = array(
            "members_status"=>0
        );


        if(isset($_data["live_platform"])){
            $_arrOfUserMembersData["live_platform"] = $_data["live_platform"];
        }
        if(isset($_data["live_id"])){
            $_arrOfUserMembersData["live_id"] = $_data["live_id"];
        }
        if(isset($_data["wechart_id"])){
            $_arrOfUserMembersData["wechart_id"] = $_data["wechart_id"];
        }
        if(isset($_data["sys_sex_id"])){
            $_arrOfUserMembersData["sys_sex_id"] = $_data["sys_sex_id"];
        }
        if(isset($_data["address"])){
            $_arrOfUserMembersData["address"] = $_data["address"];
        }
        if(isset($_data["address_json"])){
            $_arrOfUserMembersData["address_json"] = $_data["address_json"];
        }
        if(isset($_data["address_detail"])){
            $_arrOfUserMembersData["address_detail"] = $_data["address_detail"];
        }
        if(isset($_data["user_name"])){
            $_arrOfUserMembersData["user_name"] = $_data["user_name"];
        }
        if(isset($_data["birthday"])){
            $_arrOfUserMembersData["birthday"] = $_data["birthday"];
        }
        if(isset($_data["phone"])){
            $_arrOfUserMembersData["phone"] = $_data["phone"];
        }
        if(isset($_data["phone_prefix"])){
            $_arrOfUserMembersData["phone_prefix"] = $_data["phone_prefix"];
        }

        UsrUser::where([
            "user_id"=>$_usr_user["user_id"]
        ])->update($_arrOfUserMembersData);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }
}
