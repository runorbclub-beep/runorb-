<?php


namespace App\Http\Controllers\Admin\Home;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysMatch;
use App\Models\UserGroup;
use App\Models\UserGroupAssociated;
use App\Models\UserPlay;
use App\Models\UsrUser;
use Illuminate\Support\Facades\Redis;

class HomeController  extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/26 18:00
     * @abstract _管理后台首页查询
     */
    public function AdminHome(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

//        if(!isset($_data["is_group"]) || !isset($_data["matchs_user_id"]) || !isset($_data["matchs_stage_id"])){
//            return SystemErrorController::paramtersError($_language);
//        }

//        用户总数
        $_intOfUserCount = UsrUser::where(["status"=>1,])->count();

//        注册用户数
        $_intOfRegisterUserCount = UsrUser::where(["status"=>1,"sys_user_type_id"=>"1809649560981504"])->count();

//        团队数
        $_intOfUserGroupCount = UserGroup::where(["status"=>1])->count();

//        团队用户数
        $_intOfUserGroupAssociatedCount = UserGroupAssociated::where(["status"=>1])->count();


//        近30天，用户运动数据
        $_start_time = date("Y-m-d",strtotime("-31 day"))." 00:00:00";
        $_stop_time = date("Y-m-d",time())." 23:59:59";
        $_arrOfUserPlay = UserPlay::where([
            "status"=>1,
        ])->where("start_time",">=",strtotime($_start_time))
            ->where("stop_time","<=",strtotime($_stop_time))
            ->select("user_play_id","user_id","start_time","duration")->get();


        $_arrOfUserPkayKey = array();
        foreach ($_arrOfUserPlay as $value){
            $_date_time = date("Y-m-d",$value["start_time"]);

//            未定义
            if(!array_key_exists($_date_time,$_arrOfUserPkayKey)){
                $_arrOfUserPkayKey[$_date_time] = array(
                    "date_time" => $value["start_time"],
                    "user"=>array(),
                    "play"=>array()
                );
            }

            if(!in_array($value["user_play_id"],$_arrOfUserPkayKey[$_date_time]["play"])){
                array_push($_arrOfUserPkayKey[$_date_time]["play"],$value["user_play_id"]);
            }
            if(!in_array($value["user_id"],$_arrOfUserPkayKey[$_date_time]["user"])){
                array_push($_arrOfUserPkayKey[$_date_time]["user"],$value["user_id"]);
            }
        }



        $_arrOfUserPkayValue = array();
        foreach ($_arrOfUserPkayKey as $key=>$value){
            array_push($_arrOfUserPkayValue,array(
                "name"=>"活跃用户",
                "time"=>date("m-d",$value["date_time"]),
                "value"=>count($value["user"])
            ));

            array_push($_arrOfUserPkayValue,array(
                "name"=>"运动数",
                "time"=>date("m-d",$value["date_time"]),
                "value"=>count($value["play"])
            ));
        }


//        今日活跃用户数
        $_today = date("Y-m-d",time());
        $_intUserToday = isset($_arrOfUserPkayKey[$_today])?count($_arrOfUserPkayKey[$_today]["user"]):0;
        $_intUserTodayPlay = isset($_arrOfUserPkayKey[$_today])?count($_arrOfUserPkayKey[$_today]["play"]):0;

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "user_count"=>$_intOfUserCount,
                "register_user_count"=>$_intOfRegisterUserCount,
                "user_group_count"=>$_intOfUserGroupCount,
                "group_user_count"=>$_intOfUserGroupAssociatedCount,
//                "today_user_count"=>$_intUserToday,
//                "today_play_count"=>$_intUserTodayPlay,
                "his_user_play_data"=>$_arrOfUserPkayValue,
            )
        );
    }
}
