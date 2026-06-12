<?php


namespace App\Http\Controllers\Admin\Website;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\WebsiteApp;
use App\Models\WebsiteHome;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/18 15:40
     * @abstract _查询官网首页内容列表
     */
    public function postHomeList(){
        $_arrOfWebsiteHome = WebsiteHome::where([
            "status"=>1,
        ])->select(
            "website_home_id","title_cn","title_en","content","content_en","subtitle","source","source_type","index"
        )->orderBy("index","ASC")->get();


        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfWebsiteHome),
                "list"=>$_arrOfWebsiteHome
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/18 15:45
     * @abstract _新增，编辑首页内容
     */
    public function postHomeContentAdd(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["title_cn"]) || !isset($_data["title_en"]) || !isset($_data["subtitle"]) || !isset($_data["content"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebSiteHomeData = array(
            "status"=>1,
            "title_cn"=>$_data["title_cn"],
            "title_en"=>$_data["title_en"],
            "subtitle"=>$_data["subtitle"],
            "content"=>$_data["content"],
            "content_en"=>isset($_data["content_en"])?$_data["content_en"]:"",
            "index"=>isset($_data["index"])?$_data["index"]:999,
        );

        if(isset($_data["source"])){
            $_arrOfWebSiteHomeData["source"] = $_data["source"];

            $_base_url = StaticDataController::$_server_url."/";
            $_arrOfWebSiteHomeData["source"] = str_replace($_base_url,"",$_arrOfWebSiteHomeData["source"]);
        }
        if(isset($_data["source_type"])){
            $_arrOfWebSiteHomeData["source_type"] = $_data["source_type"];
        }

//        编辑
        if(isset($_data["website_home_id"])){
            $_arrOfWebSiteHomeData["updated_uid"] = $_admin_user["admin_user_id"];

            WebsiteHome::where(["website_home_id"=>$_data["website_home_id"],])->update($_arrOfWebSiteHomeData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );

        }else{
            $_arrOfWebSiteHomeData["created_uid"] = $_admin_user["admin_user_id"];
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfWebSiteHomeData["website_home_id"] = $_sno->nextId();

            WebsiteHome::create($_arrOfWebSiteHomeData);

            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/18 15:49
     * @abstract _编辑首页内容排序
     */
    public function postWebsiteHomeIndexUpdate(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["website_home_id"]) || !isset($_data["index"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebsiteHome::where([
            "website_home_id"=>$_data["website_home_id"]
        ])->update([
            "index"=>$_data["index"],
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"编辑成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/18 15:49
     * @abstract 删除内容
     */
    public function postWebsiteHomeDelete(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["website_home_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebsiteHome::where([
            "website_home_id"=>$_data["website_home_id"]
        ])->update([
            "status"=>0,
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"删除成功"
        );
    }


}
