<?php

namespace App\Http\Controllers\Admin\Members;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use Illuminate\Support\Facades\Redis;

class MembersController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/6/12 12:06
     * @abstract _获取会员招募说明
     */
    public function postMembersTitle(){

        Redis::select(1);
        $_members_title_default = Redis::hgetall("members_title_default");

        $_members_title = array();
        foreach ($_members_title_default as $key=>$value){
            $_members_title[$key] = json_decode($value,true);
        }

//        如果redis没有数据
        if(count($_members_title)!=2){
            $_members_title = StaticDataController::$_befor_members_title_default;

            foreach ($_members_title as $key=>$value){
                Redis::hset("members_title_default",$key,json_encode($value));
            }
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_members_title
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/12 12:17
     * @abstract _更新会员招募信息
     */
    public function postMembersUpdate(){
        Redis::select(1);
        $_data = request()->input();

        Redis::hset("members_title_default",$_data["language"],json_encode($_data["members_title_default"][0]));

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }
}
