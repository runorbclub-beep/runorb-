<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\AppFontController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysAppAdvertising;
use App\Models\WebsiteApp;
use Illuminate\Http\Request;

class SystemController extends Controller
{

    public function postAppFont(Request $request){
        $_data = $request->input();

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>AppFontController::getAppFont($_data["font_type"])
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/25 11:59
     * @abstract _APP开屏页宣传图
     */
    public function postAppAdvertising(){


        $_arrOfSysAppAdvertising = SysAppAdvertising::where([
            "status"=>1,
        ])->select("img_375_812","img_414_896")->orderBy("created_time","DESC")->limit(1)->get();

        if(count($_arrOfSysAppAdvertising)>0){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>array(
                    "img_375_812"=>StaticDataController::$_server_url."/".$_arrOfSysAppAdvertising[0]["img_375_812"],
                    "img_414_896"=>StaticDataController::$_server_url."/".$_arrOfSysAppAdvertising[0]["img_414_896"],
                )
            );
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "img_375_812"=>"",
                "img_414_896"=>"",
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/7/3 17:01
     * @abstract _android 新版本检测
     */
    public function postAndroidVersionCheck(){
        $_data = request()->input();

        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        if(!isset($_data["android_code"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfSysApp = WebsiteApp::where(["status"=>1])->select(
            "app_android_code", "app_description_android_cn", "is_strong_update"
        )->orderBy("app_android_code","DESC")->take(0)->limit(1)->get();

        $_has_new_version = false;
        if(count($_arrOfSysApp)>0){
            $_has_new_version = $_arrOfSysApp[0]["app_android_code"]>$_data["android_code"]?true:false;
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=> [
                'is_update' => $_has_new_version,
                'is_strong_update' => $_arrOfSysApp[0]["is_strong_update"] ?? 0,
                'description' => $_arrOfSysApp[0]["app_description_android_cn"] ?? ''
            ]
        );

    }
}
