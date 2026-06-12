<?php


namespace App\Http\Controllers\Admin\System;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysMember;
use App\Models\SysRankingType;
use App\Models\SysSetting;
use Illuminate\Support\Facades\Redis;

class SystemController  extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/18 15:02
     * @abstract _获取系统设置
     */
    public function postSystemSetting(){
        Redis::select(1);

        $_systemSetting = Redis::hgetAll("sys_setting");
         if (!$_systemSetting){
            $_systemSetting = SysSetting::where(['sys_setting_id' => 48216651664986112, 'status' => 1])->firstOrFail();
            
            Redis::hset('sys_setting', 'pk_person_time', $_systemSetting->pk_person_time);
            Redis::hset('sys_setting', 'pk_group_time', $_systemSetting->pk_group_time);
            Redis::hset('sys_setting', 'pk_group_user', $_systemSetting->pk_group_user);
            Redis::hset('sys_setting', 'exponent_title_description_en', $_systemSetting->exponent_title_description_en);
            Redis::hset('sys_setting', 'exponent_title_description_zh', $_systemSetting->exponent_title_description_zh);
            Redis::hset('sys_setting', 'exponent_molecular', $_systemSetting->exponent_molecular);
            Redis::hset('sys_setting', 'exponent_denominator', $_systemSetting->exponent_denominator);
            Redis::hset('sys_setting', 'match_stop_tips_en', $_systemSetting->match_stop_tips_en);
            Redis::hset('sys_setting', 'match_stop_tips_zh', $_systemSetting->match_stop_tips_zh);
            Redis::hset('sys_setting', 'exponent_molecular_tips_en', $_systemSetting->exponent_molecular_tips_en);
            Redis::hset('sys_setting', 'exponent_molecular_tips_zh', $_systemSetting->exponent_molecular_tips_zh);
            Redis::hset('sys_setting', 'exponent_denominator_tips_en', $_systemSetting->exponent_denominator_tips_en);
            Redis::hset('sys_setting', 'exponent_denominator_tips_zh', $_systemSetting->exponent_denominator_tips_zh);
            Redis::hset('sys_setting', 'match_group_user_num', $_systemSetting->match_group_user_num);
            Redis::hset('sys_setting', 'match_max_sign_count', $_systemSetting->match_max_sign_count);
            Redis::hset('sys_setting', 'each_integral', $_systemSetting->each_integral);
            Redis::hset('sys_setting', 'integral_exchange_ratio', $_systemSetting->integral_exchange_ratio);
            Redis::hset('sys_setting', 'points_withdrawal_rule', $_systemSetting->points_withdrawal_rule);
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_systemSetting
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/18 15:09
     * @abstract _编辑配置
     */
    public function postSystemSettingUpdate(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["type"]) || !isset($_data["value"]) || !isset($_data["sys_setting_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        Redis::select(1);
        Redis::hset("sys_setting",$_data["type"],$_data["value"]);

        SysSetting::where(['sys_setting_id' => 48216651664986112, 'status' => 1])->update([$_data["type"]=>$_data["value"]]);



        return array(
            "code"=>1,
            "msg"=>"编辑成功",
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/16 15:01
     * @abstract _获取榜单类型列表
     */
    public function postRankingList(){
        $_arrOfReturnList = SysRankingType::where([
            "status"=>1,
        ])->select(
            "ranking_title_zh","ranking_title_en","ranking_type","ranking_index"
            ,"ranking_rule_zh","ranking_rule_en","sys_ranking_type_id"
        )->orderBy("ranking_index","ASC")->get();


        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfReturnList),
                "list"=>$_arrOfReturnList
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/16 15:03
     * @abstract _榜单类型更新
     */
    public function postRankingUpdate(){
        $_data = request()->input();

        $_sys_ranking_type_id = $_data["sys_ranking_type_id"];
        unset($_data["sys_ranking_type_id"]);

        SysRankingType::where([
            "sys_ranking_type_id"=>$_sys_ranking_type_id,
        ])->update($_data);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/25 14:21
     * @abstract _新增会员说明内容
     */
    public function postMembersDescriptionAdd(){
        $_data = request()->input();

        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';
        if(!isset($_data["title_cn"]) || !isset($_data["title_en"]) || !isset($_data["members_amount"]) || !isset($_data["members_description_cn"]) || !isset($_data["members_description_en"]) ){
            return SystemErrorController::paramtersError($_language);
        }

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_sys_member_id = $_sno->nextId();
        $_arrOfSysMemberData = array(
            "sys_member_id"=>$_sys_member_id,
            "status"=>1,
            "title_cn"=>$_data["title_cn"],
            "title_en"=>$_data["title_en"],
            "members_amount"=>$_data["members_amount"],
            "members_description_cn"=>$_data["members_description_cn"],
            "members_description_en"=>$_data["members_description_en"],
            "currency"=>isset($_data["currency"])?$_data["currency"]:"CNY",
        );

        SysMember::where(["status"=>1])->update(["status"=>0]);

        SysMember::create($_arrOfSysMemberData);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/25 14:20
     * @abstract _查询会员说明信息
     */
    public function postMembersDescriptionInfo(){

        return self::membersDescriptionInfo();

    }

    public static function membersDescriptionInfo(){
        $_arrOfSysMember = SysMember::where(["status"=>1])->select(
            "title_cn","title_en","members_amount","members_description_cn","members_description_en","currency"
        )->get();

        if(count($_arrOfSysMember)>0){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfSysMember[0]
            );
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array()
        );
    }

}
