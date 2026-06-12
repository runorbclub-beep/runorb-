<?php


namespace App\Http\Controllers\Web;


use App\Http\CommonClass\WxPayController;
use App\Http\Controllers\Admin\News\NewsController;
use App\Http\Controllers\Admin\System\SystemController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\AliPlayController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\SysNew;
use App\Models\WebsiteHome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


/**
 * @author pengjl
 * @time 2021/5/9 17:32
 * Class HomeController
 * @package App\Http\Controllers\Web
 * @abstract _首页相关接口
 */
class HomeController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/9 17:31
     * @abstract _官网首页查询
     */
    public function getHome(){
        $_data = request()->input();

        $_color = isset($_data["color"])?$_data["color"]:"#fff";
        $_fontSize = isset($_data["font_size"])?$_data["font_size"]:"16px";

        $data = array();
        
        $key = "home:data";
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

        $data["home"] = WebsiteHome::where([
            "status"=>1,
        ])->select(
            "website_home_id","title_cn","title_en","subtitle","content","content_en","source","source_type","index"
        )->orderBy("index","ASC")->get();


//        官网首页视频 1ea7843d05334ea2a3e5b5c3a1f62746  5455b618b1b448e0a67371ee17ffb6c0
        $_play_url = AliPlayController::getAliPlayInfo("ef2f2433c85b499cb9da2aba3ce5318f");
        if(!isset($_play_url["play_url"])){
            $_play_url = array(
                "play_url"=>StaticDataController::$_server_url."/website_source/runball.mp4",
                "cover_url"=>StaticDataController::$_server_url."/website_source/runball.jpg",
            );
        }

        if(isset($_play_url["play_url"])){
            $data["home_video"]["play_url"] = $_play_url['play_url'] ;
            $data["home_video"]['cover_url'] = $_play_url['cover_url'] ;
        }else{
            $data["home_video"]["play_url"] = StaticDataController::$_server_url."/website_source/runball.mp4";
            $data["home_video"]['cover_url'] = StaticDataController::$_server_url."/website_source/runball.jpg";
        }

        $data["news"] = NewsController::getNewsList(1,10,2,"");
        $data["information"] = NewsController::getNewsList(1,10,1,"");

        foreach ($data["home"] as $key=>$value){
            $value["content"] = str_replace('color: rgb(0, 0, 0);','',$value["content"]);
            $value["content"] = "<div style='color:".$_color.";font-size:".$_fontSize."'>".$value["content"]."</div>";

            $value["content_en"] = str_replace('color: rgb(0, 0, 0);','',$value["content_en"]);
            $value["content_en"] = "<div style='color:".$_color.";font-size:".$_fontSize."'>".$value["content_en"]."</div>";
            $data["home"][$key] = $value;
        }


        $_tip_cn = array(
            "谁是英雄，召募全国各城市摇跑PK擂主！",
            "恭喜 杨** 在摇跑赛中获得第一名",
            "恭喜 彭** 在马拉松赛中获得第二名"
        );

        $_tip_en = array(
            "Who is the hero, the national cities shake run PK challenge!",
            "Congratulations to Yang ** for winning the first place in the rolling race",
            "Congratulations to Peng ** on winning the second place in the marathon"
        );

        $data["tip_cn"] = $_tip_cn;
        $data["tip_en"] = $_tip_en;
        
        Redis::setex($key,300,json_encode($data));

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$data
        );
    }

    public function getProductVideo(){
        $_return_data = array(
            "play_url_cn"=>array(),
            "play_url_en"=>array(),
        );

//        中文产品介绍视频  6bfc90db79054ebd861799fdf8649082
        $_play_url_cn = AliPlayController::getAliPlayInfo("ffb87791ea1a4228a9ad5a8b7ce6c922");
        if(isset($_play_url_cn["play_url"])){
            $_return_data["play_url_cn"]["play_url"] = $_play_url_cn['play_url'] ;
            $_return_data["play_url_cn"]['cover_url'] = $_play_url_cn['cover_url'] ;
        }else{
            $_return_data["play_url_cn"]["play_url"] = StaticDataController::$_server_url."/website_source/product_video_cn.mp4";
            $_return_data["play_url_cn"]['cover_url'] = StaticDataController::$_server_url."/website_source/product_video_cn.png";
        }



//        英文产品介绍视频
        $_play_url_cn = AliPlayController::getAliPlayInfo("adbc262263914928aea79cf992ea0a87");
        if(isset($_play_url_cn["play_url"])){
            $_return_data["play_url_en"]["play_url"] = $_play_url_cn['play_url'] ;
            $_return_data["play_url_en"]['cover_url'] = $_play_url_cn['cover_url'] ;
        }else{
            $_return_data["play_url_en"]["play_url"] = StaticDataController::$_server_url."/website_source/product_video_en.mp4";
            $_return_data["play_url_en"]['cover_url'] = StaticDataController::$_server_url."/website_source/product_video_en.png";
        }
        

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_return_data
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/25 14:42
     * @abstract _官网查询会员相关信息
     */
    public function getMembersDescriptionInfo(){
        return SystemController::membersDescriptionInfo();
//        return redirect()->route('membersDescriptionInfo');
    }

}
