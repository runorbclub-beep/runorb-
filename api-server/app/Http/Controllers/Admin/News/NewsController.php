<?php

namespace App\Http\Controllers\Admin\News;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use phpDocumentor\Reflection\Types\Self_;

class NewsController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/11 20:03
     * @abstract _新增，编辑新闻
     */
    public function postNewsAdd(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["news_title"]) || !isset($_data["news_content"]) || !isset($_data["news_type"]) || !isset($_data["news_img"])){
            return SystemErrorController::paramtersError($_language);
        }


        $_base_url = StaticDataController::$_server_url."/";
        $_news_img = $_data["news_img"];
        $_news_img = str_replace($_base_url,"",$_news_img);

        $_arrOfSysNewsData = array(
            "news_title"=>$_data["news_title"],
            "news_content"=>$_data["news_content"],
            "news_title_en"=>isset($_data["news_title_en"])?$_data["news_title_en"]:"",
            "news_content_en"=>isset($_data["news_content_en"])?$_data["news_content_en"]:"",
            "news_type"=>$_data["news_type"],
            "news_img"=>$_news_img,
            "created_time" => time(),
            "updated_time" => time(),
        );
        isset($_data['is_top']) ? $_arrOfSysNewsData['is_top'] = $_data['is_top'] : '';

//        已存在ID，编辑
        if(isset($_data["sys_new_id"])){
            $_arrOfSysNewsData["updated_uid"] = $_admin_user["admin_user_id"];
            isset($_data["created_date"]) ? $_arrOfSysNewsData["created_time"] = strtotime($_data["created_date"]) : '';
            $_arrOfSysNewsData["updated_time"] = time();
            SysNew::where([
                "sys_new_id"=>$_data["sys_new_id"]
            ])->update($_arrOfSysNewsData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );
        }else{
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfSysNewsData["sys_new_id"] = $_sno->nextId();
            $_arrOfSysNewsData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfSysNewsData["status"] = 1;
            isset($_data["created_date"]) ? $_arrOfSysNewsData["created_time"] = strtotime($_data["created_date"]) : '';

            SysNew::create($_arrOfSysNewsData);

            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/5/11 20:21
     * @abstract _查看新闻详情
     */
    public function postNewsInfo(){

        $_data = request()->input();
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';


        if(!isset($_data["sys_new_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfNews = SysNew::where([
            "sys_new_id"=>$_data["sys_new_id"],
            "status"=>1,
        ])->select(
            'sys_new_id','created_time','news_title','news_title_en','news_type','news_content','news_content_en','view_num','news_img','is_top'
        )->get();

        if(count($_arrOfNews)==1){
            $_arrOfNewsInfo = $_arrOfNews[0];
            $_arrOfNewsInfo["created_date"] = date("Y-m-d H:i",$_arrOfNewsInfo["created_time"]);
            unset($_arrOfNewsInfo["created_time"]);

//            阅读量加1
            SysNew::where(["sys_new_id"=>$_arrOfNewsInfo["sys_new_id"]])->update(["view_num"=>$_arrOfNewsInfo["view_num"]=1]);

            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfNewsInfo
            );
        }else{
            return array(
                "code"=>0,
                "msg"=>"未查询到内容"
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/5/11 20:21
     * @abstract _查询新闻列表
     */
    public function postNewsList(Request $request){

        $_data = request()->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_type = isset($_data["news_type"])?$_data["news_type"]:"";


        return array(
            "code"=>1,
            "msg"=> "success",
            "data"=>self::getNewsList($_page,$_limit,$_type,$_search)
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/11 20:21
     * @abstract _删除新闻
     */
    public function postNwesDelete(Request $request){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["sys_new_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        SysNew::where(["sys_new_id"=>$_data["sys_new_id"]])->update(["status"=>0,"updated_uid"=>$_admin_user["admin_user_id"]]);

        return array(
            "code"=>1,
            "msg"=> "删除成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/11 20:16
     * @abstract _查询新闻列表公共方法
     */
    public static function getNewsList($_page,$_limit,$_type,$_search){

        $_offset = ($_page-1)*$_limit;

        $_arrOfNewsQuery = SysNew::where([
            "status"=>1,
        ]);

        if($_search!=''){
            $_arrOfNewsQuery = $_arrOfNewsQuery->where(function($query) use ($_search){
                $query->where("news_title","like",'%'.$_search."%");
            });
        }

        if($_type!=''){
            $_arrOfNewsQuery = $_arrOfNewsQuery->where(["news_type"=>$_type]);
        }

        $_arrOfNewsQuery = $_arrOfNewsQuery->select('sys_new_id','created_time','news_title','news_title_en','news_type','news_content','news_content_en','view_num','news_img','is_top');

        $_arrOfNewsCount = $_arrOfNewsQuery->count();
        $_arrOfNews = $_arrOfNewsQuery->orderBy('is_top', 'desc')->orderBy('created_time', 'desc')->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfNews as $key=>$value){
            $_arrOfNewsNode = array(
                "created_date"=>date("Y-m-d H:i",$value["created_time"]),
                "sys_new_id"=>(String)$value["sys_new_id"],
                "news_title"=>$value["news_title"],
                "news_title_en"=>$value["news_title_en"],
                "news_type"=>$value["news_type"],
                "news_content"=>$value["news_content"],
                "news_content_en"=>$value["news_content_en"],
                "view_num"=>$value["view_num"],
                "is_top"=>$value["is_top"],
                "news_img"=>StaticDataController::$_server_url."/".$value["news_img"],
            );
            $_arrOfNews[$key] = $_arrOfNewsNode;
        }

        return array(
            "count"=>$_arrOfNewsCount,
            "list"=>$_arrOfNews
        );
    }

}
