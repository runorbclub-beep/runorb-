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

//        会员数
        $_intOfMembersUserCount = UsrUser::where(["status"=>1,"is_members"=>1])->count();
//        待审核会员数
        $_intOfMembersWaitUserCount = UsrUser::where(["status"=>1,"members_status"=>0])->count();


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

        $_arrOfSerise = array(
            "user_count"=>array(
                "name"=>"活跃用户数",
                "type"=>"line",
                "data"=>array()
            ),
            "play_count"=>array(
                "name"=>"运动次数",
                "type"=>"line",
                "yAxisIndex"=>1,
                "data"=>array()
            ),
        );



        $_arrOfXaxis = array();

        $_user_count_max = 0;
        $_play_count_max = 0;
        foreach ($_arrOfUserPkayKey as $key=>$value){
            array_push($_arrOfSerise["user_count"]["data"],count($value["user"]));
            array_push($_arrOfSerise["play_count"]["data"],count($value["play"]));
            array_push($_arrOfXaxis,date("m-d",$value["date_time"]));

            if($_user_count_max<count($value["user"])){
                $_user_count_max = count($value["user"]);
            }
            if($_play_count_max<count($value["play"])){
                $_play_count_max = count($value["play"]);
            }
        }

        $_arrOfLegend = array(
            array(
                "name"=>"活跃用户数"
            ),
            array(
                "name"=>"运动次数"
            )
        );

        $_arrOfYaxis = array(
            array(
                "type"=>"value",
                "name"=>"人数",
                "min"=>0,
                "max"=>$_user_count_max,
                "interval"=>10,
            ),
            array(
                "type"=>"value",
                "name"=>"次数",
                "min"=>0,
                "max"=>$_play_count_max,
                "interval"=>10,
            )
        );

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "user_count"=>$_intOfUserCount,
                "register_user_count"=>$_intOfRegisterUserCount,
                "user_group_count"=>$_intOfUserGroupCount,
                "group_user_count"=>$_intOfUserGroupAssociatedCount,
                "members_user_count"=>$_intOfMembersUserCount,
                "members_wait_user_count"=>$_intOfMembersWaitUserCount,
                "echarts_bar_user_play"=>array(
                    "xAxis"=>array(
                        "type"=>"category",
                        "data"=>$_arrOfXaxis
                    ),
                    "yAxis"=>$_arrOfYaxis,
                    "legend"=>$_arrOfLegend,
                    "series"=>array_values($_arrOfSerise),
                )

            )
        );
    }
}
