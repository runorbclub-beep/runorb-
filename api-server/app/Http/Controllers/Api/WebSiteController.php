<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\AliPlayController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\SysNew;


/**
 * @author pengjl
 * @time 2021/5/7 21:51
 * Class WebSiteController
 * @package App\Http\Controllers\Api
 * @abstract _官网接口
 */
class WebSiteController extends Controller
{


      /**
      * @author jackchim
      * @time 2021/5/7 21:52
      * @abstract _网首页获取视频
      */
      public function postWebsiteHomeVideo(){
         $_play_url = AliPlayController::getAliPlayInfo("5455b618b1b448e0a67371ee17ffb6c0");
         $data = array();

         if(isset($_play_url["play_url"])){
            $data["play_url"] = $_play_url['play_url'] ;
            $data['cover_url'] = $_play_url['cover_url'] ;
         }else{
            $data["play_url"] = StaticDataController::$_server_url."/website_source/runball.mp4";
            $data['cover_url'] = StaticDataController::$_server_url."/website_source/runball.jpg";
         }

        $n = new SysNew;
        // news_type 1：资讯，2：新闻
        $latest_article = $n->getList(1, 1, 10);
        $latest_news = $n->getList(2, 1, 10);
        $data['article'] = $latest_article;
        $data['news'] = $latest_news;

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$data
        );
    }


}
