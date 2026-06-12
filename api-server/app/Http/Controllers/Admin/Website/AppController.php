<?php


namespace App\Http\Controllers\Admin\Website;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\WebsiteApp;
use App\Models\WebsiteAboutme;
use Illuminate\Support\Facades\Redis;

class AppController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/18 10:59
     * @abstract _新增版本
     */
    public function postAddVersion(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["app_image_android"]) || !isset($_data["app_image_ios"]) || !isset($_data["app_android_code"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_base_url = StaticDataController::$_server_url."/";
        $_app_image_android = $_data["app_image_android"];
        $_app_image_ios = $_data["app_image_ios"];

        $_app_image_android = str_replace($_base_url,"",$_app_image_android);
        $_app_image_ios = str_replace($_base_url,"",$_app_image_ios);

        $_app_package_path_android = isset($_data["app_package_path_android"])?$_data["app_package_path_android"]:"";
        $_app_package_path_android = str_replace($_base_url,"",$_app_package_path_android);
        $_app_package_path_ios = isset($_data["app_package_path_ios"])?$_data["app_package_path_ios"]:"";
        $_app_package_path_ios = str_replace($_base_url,"",$_app_package_path_ios);

        $_arrOfWebSiteData = array(
            "status"=>1,
            "app_version"=>isset($_data["app_version"])?$_data["app_version"]:"",
            "app_image_android"=>$_app_image_android,
            "app_image_ios"=>$_app_image_ios,
            "app_android_code"=>$_data["app_android_code"],
            "app_version_ios"=>isset($_data["app_version_ios"])?$_data["app_version_ios"]:"",
            "app_version_android"=>isset($_data["app_version_android"])?$_data["app_version_android"]:"",
            "app_description_android_cn"=>isset($_data["app_description_android_cn"])?$_data["app_description_android_cn"]:"",
            "app_description_android_en"=>isset($_data["app_description_android_en"])?$_data["app_description_android_en"]:"",
            "app_description_ios_cn"=>isset($_data["app_description_ios_cn"])?$_data["app_description_ios_cn"]:"",
            "app_description_ios_en"=>isset($_data["app_description_ios_en"])?$_data["app_description_ios_en"]:"",
            "app_package_path_android"=>$_app_package_path_android,
            "app_package_path_ios"=>$_app_package_path_ios,
            'is_strong_update' => $_data['is_strong_update'] ?? 0
        );

//        编辑
        if(isset($_data["website_app_id"])){
            $_arrOfWebSiteData["updated_uid"] = $_admin_user["admin_user_id"];

            WebsiteApp::where(["website_app_id"=>$_data["website_app_id"],])->update($_arrOfWebSiteData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );

        }else{
            $_arrOfWebSiteData["created_uid"] = $_admin_user["admin_user_id"];
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfWebSiteData["website_app_id"] = $_sno->nextId();
            $_arrOfWebSiteData["app_update_time"] = date("Y-m-d",time());

            WebsiteApp::create($_arrOfWebSiteData);

            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/18 11:14
     * @abstract _删除版本
     */
    public function postAppVersionDelete(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["website_app_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebsiteApp::where([
            "website_app_id"=>$_data["website_app_id"]
        ])->update([
            "status"=>0,
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"删除成功"
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/18 11:12
     * @abstract _查询历史版本
     */
    public function postAppVersionList(){
        $_data = request()->input();

        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;

        $_arrOfWebsiteAppQuery = WebsiteApp::where([
            "status"=>1
        ])->select(
            "website_app_id","app_version","app_version_ios","app_version_android","app_image_ios","app_image_android","app_update_time"
            ,"app_description_android_cn","app_description_android_en","app_description_ios_cn","app_description_ios_en"
            ,"app_package_path_android","app_package_path_ios","app_android_code", "is_strong_update"
        )
            ->orderBy("website_app_id","DESC");

        $_arrOfWebsiteAppCount = $_arrOfWebsiteAppQuery->count();
        $_arrOfWebsiteApp = $_arrOfWebsiteAppQuery->skip(($_page-1)*$_limit)->take($_limit)->get();

        foreach ($_arrOfWebsiteApp as $value){
            $value["app_image_ios"] = StaticDataController::$_server_url."/".$value["app_image_ios"];
            $value["app_image_android"] = StaticDataController::$_server_url."/".$value["app_image_android"];
            $value["app_package_path_android"] = StaticDataController::$_server_url."/".$value["app_package_path_android"];
            $value["app_package_path_ios"] = StaticDataController::$_server_url."/".$value["app_package_path_ios"];
        }


//        最新版本号
        $_arrOfFirstAppVersion = WebsiteApp::where([
            "status"=>1
        ])->select("app_android_code")->orderBy("app_android_code","DESC")->limit(1)->get();



        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfWebsiteAppCount,
                "list"=>$_arrOfWebsiteApp,
                "next_app_android_code"=>$_arrOfFirstAppVersion[0]["app_android_code"]+1
            )
        );
    }
    
    
    public function postAboutmeList()
    {
        $_data = request()->input();

        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;

        $_arrOfWebsiteAppQuery = WebsiteAboutme::where([
            "status"=>1
        ])->orderBy("website_aboutme_id","DESC");

        $_arrOfWebsiteAppCount = $_arrOfWebsiteAppQuery->count();
        $_arrOfWebsiteApp = $_arrOfWebsiteAppQuery->skip(($_page-1)*$_limit)->take($_limit)->get();

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfWebsiteAppCount,
                "list"=>$_arrOfWebsiteApp
            )
        );
    }


}
