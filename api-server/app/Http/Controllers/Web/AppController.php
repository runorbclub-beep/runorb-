<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\WebsiteApp;

class AppController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/18 11:00
     * @abstract _获取最新版本
     */
    public function getVersion(){

        $_arrOfWebsiteApp = WebsiteApp::where([
            "status"=>1
        ])->select("website_app_id","app_version","app_image","app_image_ios","app_image_android","app_update_time","app_version_ios","app_version_android")
            ->orderBy("website_app_id","DESC")->get();


        foreach ($_arrOfWebsiteApp as $value){
            $value["app_image_ios"] = StaticDataController::$_server_url."/".$value["app_image_ios"];
            $value["app_image_android"] = StaticDataController::$_server_url."/".$value["app_image_android"];
        }

        if(count($_arrOfWebsiteApp)>0){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfWebsiteApp[0]
            );
        }else{
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>array()
            );
        }
    }
}
