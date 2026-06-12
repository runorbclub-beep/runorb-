<?php


namespace App\Http\Controllers\Admin\System;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysAppAdvertising;
use Illuminate\Support\Facades\Redis;

class AppController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/6/19 14:24
     * @abstract _app启动页宣传图
     */
    public function postAppAdvertisingList (){

        $_data = request()->input();

        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;

        $_arrOfSysAppAdvertisingQuery = SysAppAdvertising::where("status","!=",-1)->select(
            "sys_app_advertising_id","status",
            "advertising_name","img_375_812","img_414_896"
        )->orderBy("created_time","DESC");

        $_arrOfSysAppAdvertisingCount = $_arrOfSysAppAdvertisingQuery->count();
        $_arrOfSysAppAdvertising = $_arrOfSysAppAdvertisingQuery->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfSysAppAdvertising as $value){
            $value["img_375_812"] = StaticDataController::$_server_url."/".$value["img_375_812"];
            $value["img_414_896"] = StaticDataController::$_server_url."/".$value["img_414_896"];
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfSysAppAdvertisingCount,
                "list"=>$_arrOfSysAppAdvertising
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/19 14:27
     * @abstract _新增APP宣传页
     */
    public function postAppAdvertisingAdd(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["advertising_name"]) || !isset($_data["img_375_812"]) || !isset($_data["img_414_896"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_base_url = StaticDataController::$_server_url."/";
        $_img_375_812 = $_data["img_375_812"];
        $_img_414_896 = $_data["img_414_896"];

        $_img_375_812 = str_replace($_base_url,"",$_img_375_812);
        $_img_414_896 = str_replace($_base_url,"",$_img_414_896);

        $_arrOfAppAdvertisingData = array(
            "sys_app_advertising_id"=>$_sno->nextId(),
            "advertising_name"=>$_data["advertising_name"],
            "status"=>0,
            "img_375_812"=>$_img_375_812,
            "img_414_896"=>$_img_414_896,
            "created_uid"=>$_admin_user["admin_user_id"],
        );



        SysAppAdvertising::create($_arrOfAppAdvertisingData);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/19 14:30
     * @abstract _宣传图状态变更
     */
    public function postAppAdvertisingUpdate(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["sys_app_advertising_id"]) || !isset($_data["status"])){
            return SystemErrorController::paramtersError($_language);
        }

//        如果当前是变更为启用状态，
        if($_data["status"] == 1){
//            已启用的广告禁用，
            SysAppAdvertising::where(["status"=>1])->update(["status"=>0]);
        }

        SysAppAdvertising::where([
            "sys_app_advertising_id"=>$_data["sys_app_advertising_id"]
        ])->update([
            "status"=>$_data["status"],
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }
}
