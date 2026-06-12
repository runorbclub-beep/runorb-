<?php


namespace App\Http\Controllers\Admin\Website;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\WebsiteAboutme;
use App\Models\WebsiteApp;
use Illuminate\Support\Facades\Redis;

class AboutmeController  extends Controller
{


    public function postAboutmeAdd(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["title"]) || !isset($_data["content"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebSiteAboutmeData = array(
            "status"=>1,
            "content"=>$_data["content"],
            "title"=>$_data["title"],
            "content_en"=>isset($_data["content_en"])?$_data["content_en"]:"",
            "title_en"=>isset($_data["title_en"])?$_data["title_en"]:""
        );

//        编辑
        if(isset($_data["website_aboutme_id"])){
            $_arrOfWebSiteAboutmeData["updated_uid"] = $_admin_user["admin_user_id"];

            WebsiteAboutme::where(["website_aboutme_id"=>$_data["website_aboutme_id"],])->update($_arrOfWebSiteAboutmeData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );

        }else{
            $_arrOfWebSiteAboutmeData["created_uid"] = $_admin_user["admin_user_id"];
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfWebSiteAboutmeData["website_aboutme_id"] = $_sno->nextId();

            WebsiteAboutme::create($_arrOfWebSiteAboutmeData);

            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/18 14:43
     * @abstract _关于我们
     */
    public function getAboutme(){

        $_arrOfWebsiteAbout = WebsiteAboutme::where([
            "status"=>1
        ])->select("website_aboutme_id","title","content","title_en","content_en")->orderBy("website_aboutme_id","DESC")->get();

        if(count($_arrOfWebsiteAbout)>0){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfWebsiteAbout[0]
            );
        }else{
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>array()
            );
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/18 15:22
     * @abstract _删除关于我们
     */
    public function postAboutmeDelete(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["website_aboutme_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebsiteAboutme::where([
            "website_aboutme_id"=>$_data["website_aboutme_id"]
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
    public function postAboutmeList(){
        $_data = request()->input();

        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;

        $_arrOfWebsiteAppQuery = WebsiteAboutme::where([
            "status"=>1
        ])->select("website_aboutme_id","title","content","title_en","content_en")
            ->orderBy("website_aboutme_id","DESC");

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
