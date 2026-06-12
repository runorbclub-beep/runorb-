<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Models\WebsiteAboutme;

class AboutmeController  extends Controller
{


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
}
