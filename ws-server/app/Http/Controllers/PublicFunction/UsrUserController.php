<?php


namespace App\Http\Controllers\PublicFunction;


use App\Http\CommonClass\Snowflake;
use App\Models\UserAchievement;
use App\Models\UserMedalAssociated;
use Illuminate\Support\Facades\Redis;

class UsrUserController
{

    /**
     * @abstract 用户运动结束后，更新用户数据，历史记录数据
     * @param array $_user_play
     * @param String $_user_token
     * @return array
     */
    public static function userStopPlayHasNewAchievement(array $_user_play,String $_user_token,String $_language){
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);

//        本次运动作为用户上次运动数据
        unset($_user_play["user_play_detail"]);
        unset($_user_play["section_duration"]);
        $_usr_user["last_play"] = $_user_play;

//        获取用户记录
        if(!isset($_usr_user["achievement"])){
            $_usr_user["achievement"] = array(
                "duration"=>0,
                "speed_max"=>0,
                "circle_count"=>0,
                "endurance_max"=>0,
                "play_count"=>0
            );
        }

        $_arrOfNewAchievement = array();

//        持续时间记录
        if($_user_play["duration"]>$_usr_user["achievement"]["duration"]){
            array_push($_arrOfNewAchievement,array(
                "key"=>"duration",
                "text"=>LanguageController::getLanguage($_language,"duration_time"),
                "value"=>$_user_play["duration"]
            ));
            $_usr_user["achievement"]["duration"] = $_user_play["duration"];
        }

//        最高转速记录
        if($_user_play["speed_max"]>$_usr_user["achievement"]["speed_max"]){
            array_push($_arrOfNewAchievement,array(
                "key"=>"speed_max",
                "text"=>LanguageController::getLanguage($_language,"speed_max"),
                "value"=>$_user_play["speed_max"]
            ));
            $_usr_user["achievement"]["speed_max"] = $_user_play["speed_max"];
        }

//        最高圈数记录
        if($_user_play["circle_count"]>$_usr_user["achievement"]["circle_count"]){
            array_push($_arrOfNewAchievement,array(
                "key"=>"circle_count",
                "text"=>LanguageController::getLanguage($_language,"circle_count"),
                "value"=>$_user_play["circle_count"]
            ));
            $_usr_user["achievement"]["circle_count"] = $_user_play["circle_count"];
        }

//        耐力记录
        if($_user_play["endurance_max"]>$_usr_user["achievement"]["endurance_max"]){
            array_push($_arrOfNewAchievement,array(
                "key"=>"endurance_max",
                "text"=>LanguageController::getLanguage($_language,"endurance_max"),
                "value"=>$_user_play["endurance_max"]
            ));
            $_usr_user["achievement"]["endurance_max"] = $_user_play["endurance_max"];
        }

//        更新用户最高记录
        $_usr_user["achievement"]["play_count"] = $_usr_user["achievement"]["play_count"]+1;

        Redis::hset("usr_user",$_user_token,json_encode($_usr_user));

//        修改个人记录
        UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update($_usr_user["achievement"]);

        return $_arrOfNewAchievement;
    }


    /**
     * @abstract 用户运动结束后回去本次运动得到的新徽章
     * @param array $_user_play
     * @param String $_user_token
     * @return array
     */
    public static function userStopPlayHasNewMedal(array $_user_play,String $_user_token){
        Redis::select(1);
//        获取用户信息
        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);

        $_usr_user["achievement"]["play_count"] = $_usr_user["achievement"]["play_count"]+1;

//       用户已获得得徽章
        $_my_medal = isset($_usr_user["my_medal"])?$_usr_user["my_medal"]:array();
        $_arrOfMyMedalKey = array();
        foreach ($_my_medal as $value){
            if(!array_key_exists($value["sys_sys_medal_id"],$_arrOfMyMedalKey)){
                $_arrOfMyMedalKey[$value["sys_sys_medal_id"]] = array();
            }
            $_arrOfMyMedalKey[$value["sys_sys_medal_id"]][0] = $value;
        }

//       获取系统内所有徽章数据
        $_sys_medal = Redis::hgetall("sys_medal");
        $_arrOfSysMedal = array();
        foreach ($_sys_medal as $key=>$value){
            $_arrOfSysMedal[$key] = json_decode($value,true);
        }


//        循环对比，得到本次运动满足条件的徽章
        $_user_medal = array();
        foreach ($_arrOfSysMedal as $value){
            foreach ($value["node_medal"] as $key=>$node){
                $_medal_conditions = $node["medal_conditions"];
                $_has_medal = true;
                for ($_i=0;$_i<count($_medal_conditions);$_i+=2){
                    if($_medal_conditions[$_i]!="play_count"){
                        $_has_medal = $_has_medal&&($_user_play[$_medal_conditions[$_i]]>$_medal_conditions[$_i+1]);
                    }
                    if($_medal_conditions[$_i]=="play_count"){
                        $_has_medal = $_has_medal&&($_usr_user["achievement"]["play_count"]>$_medal_conditions[$_i+1]);
                    }
                }

//                如果满足徽章获取条件，放入数组，
                if($_has_medal){
//                    以根徽章ID作为key，避免同一类型徽章，不同级别导致徽章重复
                    if(!array_key_exists($node["sys_sys_medal_id"],$_user_medal))
                        $_user_medal[$node["sys_sys_medal_id"]] = $node;
                }
            }
        }

//        从用户已有徽章中筛选本次运动获得的勋章
        $_this_play_medal = array();
        foreach ($_user_medal as $value){
//            如果勋章类别不存在，判定为本次获得
            if(!array_key_exists($value["sys_sys_medal_id"],$_arrOfMyMedalKey)){
                array_push($_this_play_medal,$value);
            }else if($_arrOfMyMedalKey[$value["sys_sys_medal_id"]][0]["sys_medal_id"]!=$value["sys_medal_id"]){
//                如果子勋章和我已获得的勋章不一致，判定为本次获得
                array_push($_this_play_medal,$value);
            }
        }

//        如果没有定义用户徽章数据
        if(!isset($_usr_user["my_medal"])){
            $_usr_user["my_medal"] = array();
        }

//        循环存储本次获得的勋章
        $_obj = new Snowflake(StaticDataController::$_workId);
        foreach ($_this_play_medal as $value){
            array_push($_usr_user["my_medal"],$value);
//            创建用户徽章关联
            UserMedalAssociated::create([
                "user_medal_associated_id"=>$_obj->nextId(),
                "status"=>1,
                "sys_medal_id"=>$value["sys_medal_id"],
                "created_uid"=>$_usr_user["user_id"],
                "user_id"=>$_usr_user["user_id"],
            ]);
        }

        Redis::hset("usr_user",$_user_token,json_encode($_usr_user));

//        返回完整图片地址
        foreach ($_this_play_medal as $key=>$value){
            $value["medal_image"] = StaticDataController::$_server_url."/".$value["medal_image"];
            $value["medal_image_active"] = StaticDataController::$_server_url."/".$value["medal_image_active"];
            $_this_play_medal[$key] = $value;
        }
        return $_this_play_medal;
    }


    /**
     * @abstract 获取用户徽章列表
     * @param String $_user_token
     * @param int $_medal_length
     * @return mixed
     */
    public static function getUserMedal(String $_user_token,int $_medal_length){
        Redis::select(1);
//        获取用户信息
        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);

        $_user_medal_id = array();
        if(isset($_usr_user["my_medal"])){
            foreach ($_usr_user["my_medal"] as $key=>$value){
                if(!in_array($value["sys_sys_medal_id"],$_user_medal_id)){
                    array_push($_user_medal_id,$value["sys_sys_medal_id"]);
                }

                $_usr_user["my_medal"][$key] = array(
                    "medal_image_active"=>StaticDataController::$_server_url."/".$value["medal_image_active"],
                    "is_get"=>true,
                    "user_medal_name"=>$value["user_medal_name"],
                    "description"=>$value["description"],
                    "level_name"=>$value["level_name"],
                );
            }
        }else{
            $_usr_user["my_medal"] = array();
        }

        $_sys_medal = Redis::hgetall("sys_medal");

        foreach ($_sys_medal as $key=>$value){
            if(!in_array($key,$_user_medal_id)){
                $_medal_arr = json_decode($value,true);
                $_medal = $_medal_arr["node_medal"][0];

//                如果定义最大长度，并且在最大长度范围内，或没有定义最大长度，都将当前徽章放入用户徽章内
                if($_medal_length!=0&&count($_usr_user["my_medal"])<$_medal_length||$_medal_length==0){
                    array_push($_usr_user["my_medal"],array(
                        "medal_image_active"=>StaticDataController::$_server_url."/".$_medal["medal_image"],
                        "is_get"=>false,
                        "user_medal_name"=>$_medal["user_medal_name"],
                        "description"=>$_medal["description"],
                        "level_name"=>$_medal["level_name"],
                    ));
                }
            }
        }

        return $_usr_user["my_medal"];
    }



}
