<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\News\NewsController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysNew;
use Illuminate\Support\Facades\Redis;


class WebsiteNewsController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/28 10:33
     * @abstract _新闻列表
     */
    public function newsList(){
        $_data = request()->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_type = isset($_data["news_type"])?$_data["news_type"]:"";


        $data = array();
        $key = "new:list:data";
        Redis::select(2);
        $jsonData = Redis::get($key);
        if ($jsonData !== null) {
            $data = json_decode($jsonData, true);
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$data
            );
        }

        $data = NewsController::getNewsList($_page,$_limit,$_type,$_search);
        Redis::setex($key,300,json_encode($data));

        return array(
            "code"=>1,
            "msg"=> "success",
            "data"=> $data
        );
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
            'sys_new_id','created_time','news_title','news_title_en','news_type','news_content','news_content_en','view_num','news_img', 'is_top'
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



}
