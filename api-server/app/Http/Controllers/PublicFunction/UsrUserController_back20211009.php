<?php


namespace App\Http\Controllers\PublicFunction;


use App\Http\CommonClass\Snowflake;
use App\Models\SysMedal;
use App\Models\UserAchievement;
use App\Models\UserMedalAssociated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UsrUserController
{

    /**
     * @abstract 用户运动结束后，更新用户数据，历史记录数据
     * @param array $_user_play
     * @param String $_user_token
     * @return array
     */
    public static function userStopPlayHasNewAchievement(array $_user_play, string $_user_token, string $_language)
    {
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        本次运动作为用户上次运动数据
        unset($_user_play["user_play_detail"]);
        unset($_user_play["section_duration"]);
        $_usr_user["last_play"] = $_user_play;

//        获取用户记录
        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
            );
        }

        $_arrOfNewAchievement = array();

//        持续时间记录
        if ($_user_play["duration"] > $_usr_user["achievement"]["duration"]) {
            array_push($_arrOfNewAchievement, array(
                "key" => "duration",
                "text" => LanguageController::getLanguage($_language, "duration_time"),
                "value" => $_user_play["duration"]
            ));
            $_usr_user["achievement"]["duration"] = $_user_play["duration"];
        }

//        最高转速记录
        if ($_user_play["speed_max"] > $_usr_user["achievement"]["speed_max"]) {
            array_push($_arrOfNewAchievement, array(
                "key" => "speed_max",
                "text" => LanguageController::getLanguage($_language, "speed_max"),
                "value" => $_user_play["speed_max"]
            ));
            $_usr_user["achievement"]["speed_max"] = $_user_play["speed_max"];
            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update([
                "speed_max" => $_user_play["speed_max"],
                "speed_max_time" => time()
            ]);
        }

//        最高圈数记录
        if ($_user_play["circle_count"] > $_usr_user["achievement"]["circle_count"]) {
            array_push($_arrOfNewAchievement, array(
                "key" => "circle_count",
                "text" => LanguageController::getLanguage($_language, "circle_count"),
                "value" => $_user_play["circle_count"]
            ));
            $_usr_user["achievement"]["circle_count"] = $_user_play["circle_count"];
        }

//        耐力记录
        if ($_user_play["endurance_max"] > $_usr_user["achievement"]["endurance_max"]) {
            array_push($_arrOfNewAchievement, array(
                "key" => "endurance_max",
                "text" => LanguageController::getLanguage($_language, "endurance_max"),
                "value" => $_user_play["endurance_max"]
            ));
            $_usr_user["achievement"]["endurance_max"] = $_user_play["endurance_max"];
        }

//        更新用户最高记录
        $_usr_user["achievement"]["play_count"] = $_usr_user["achievement"]["play_count"] + 1;

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

//        修改个人记录
        $_achievement = $_usr_user["achievement"];
        unset($_achievement["user_achievement_id"]);
        unset($_achievement["user_id"]);
        unset($_achievement["status"]);
        UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(array_only($_achievement, ['user_achievement_id',
            'user_id',
            'created_time',
            'updated_time',
            'created_uid',
            'updated_uid',
            'status',
            'duration',
            'speed_max',
            'circle_count',
            'endurance_max',
            'play_count',
            'distance_max',
            'thrmin',
            'half_marathon',
            'marathon',
            'exponent_denominator',
            'exponent_molecular',
            'runball_exponent',
            'speed_max_time',
            'runball_exponent_time',
            'exponent_molecular_time',
            'marathon_time',
            'win_num',
            'join_match_count']));

        return $_arrOfNewAchievement;
    }


    /**
     * @abstract 用户运动结束后回去本次运动得到的新徽章
     * @param array $_user_play
     * @param String $_user_token
     * @return array
     */
    public static function userStopPlayHasNewMedal(array $_user_play, string $_user_token, string $_language)
    {
        Redis::select(1);
//        获取用户信息
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_usr_user["alldistance"])) {
            $_usr_user["alldistance"] = 0;
        }

        $_usr_user["alldistance"] += $_user_play["distance"];

        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
            );
        }
        $_usr_user["achievement"]["play_count"] = $_usr_user["achievement"]["play_count"] + 1;

//       用户已获得得徽章
        $_my_medal = isset($_usr_user["my_medal"]) ? $_usr_user["my_medal"] : array();
        $_arrOfMyMedalKey = array();
        foreach ($_my_medal as $value) {
            if (!array_key_exists($value["sys_sys_medal_id"], $_arrOfMyMedalKey)) {
                $_arrOfMyMedalKey[$value["sys_sys_medal_id"]] = array();
            }
            $_arrOfMyMedalKey[$value["sys_sys_medal_id"]][0] = $value;
        }

//       获取系统内所有徽章数据
        $_sys_medal = Redis::hgetall("sys_medal");
        $_arrOfSysMedal = array();
        foreach ($_sys_medal as $key => $value) {
            $_arrOfSysMedal[$key] = json_decode($value, true);
        }


//        循环对比，得到本次运动满足条件的徽章
        $_user_medal = array();
        foreach ($_arrOfSysMedal as $value) {
            foreach ($value["node_medal"] as $key => $node) {
                $_medal_conditions = $node["medal_conditions"];
                $_has_medal = true;
                for ($_i = 0; $_i < count($_medal_conditions); $_i += 2) {
                    if ($_medal_conditions[$_i] == "play_count") {
                        $_has_medal = $_has_medal && ($_usr_user["achievement"]["play_count"] >= $_medal_conditions[$_i + 1]);
                    } else if ($_medal_conditions[$_i] == "alldistance") {
                        $_has_medal = $_has_medal && ($_usr_user["alldistance"] >= $_medal_conditions[$_i + 1]);
                    } else {
                        $_has_medal = $_has_medal && ($_user_play[$_medal_conditions[$_i]] >= $_medal_conditions[$_i + 1]);
                    }
                }

//                如果满足徽章获取条件，放入数组，
                if ($_has_medal) {
                    $_user_medal[$node["sys_sys_medal_id"]] = $node;
                }
            }
        }


        $_arrOfSysMedal = UserMedalAssociated::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"]
        ])->select("sys_medal_id")->get();

//        用户已获得的徽章
        $_arrOfSysMedalId = array();
        foreach ($_arrOfSysMedal as $value) {
            array_push($_arrOfSysMedalId, $value["sys_medal_id"]);
        }


        foreach ($_user_medal as $key => $value) {
            if (in_array($value["sys_medal_id"], $_arrOfSysMedalId)) {
                unset($_user_medal[$key]);
            }
        }


//        从用户已有徽章中筛选本次运动获得的勋章
        $_this_play_medal = $_user_medal;

//        如果没有定义用户徽章数据
        if (!isset($_usr_user["my_medal"])) {
            $_usr_user["my_medal"] = array();
        }

//        循环存储本次获得的勋章
        $_obj = new Snowflake(StaticDataController::$_workId);
        foreach ($_this_play_medal as $value) {
//            array_push($_usr_user["my_medal"],$value);
//            创建用户徽章关联
            if (!in_array($value["sys_medal_id"], $_arrOfSysMedalId)) {
                Log::info("徽章关联创建",[
                    "user_medal_associated_id" => $_obj->nextId(),
                    "status" => 1,
                    "sys_medal_id" => $value["sys_medal_id"],
                    "created_uid" => $_usr_user["user_id"],
                    "user_id" => $_usr_user["user_id"],
                    "user_play_id" => $_user_play["user_play_id"],
                ]);


                UserMedalAssociated::create([
                    "user_medal_associated_id" => $_obj->nextId(),
                    "status" => 1,
                    "sys_medal_id" => $value["sys_medal_id"],
                    "created_uid" => $_usr_user["user_id"],
                    "user_id" => $_usr_user["user_id"],
                    "user_play_id" => $_user_play["user_play_id"],
                ]);
            }
        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

//        返回完整图片地址
        foreach ($_this_play_medal as $key => $value) {
            $value["medal_image"] = StaticDataController::$_server_url . "/" . $value["medal_image"];
            $value["medal_image_active"] = StaticDataController::$_server_url . "/" . $value["medal_image_active"];
            $_this_play_medal[$key] = $value;
        }
        sort($_this_play_medal);
        return $_this_play_medal;
    }


    /**
     * @abstract 获取用户徽章列表
     * @param String $_user_token
     * @param int $_medal_length
     * @return mixed
     */
    public static function getUserMedal(string $_user_token, int $_medal_length, string $_language)
    {
        Redis::select(1);
//        获取用户信息
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);


        $_sys_medal = Redis::hgetall("sys_medal");

        if ($_sys_medal == null) {
            $_arrOfSysMedal = SysMedal::where(["status" => 1])->select(
                "sys_medal_id", "sys_sys_medal_id", "user_medal_name_cn", "user_medal_name_en", "description_cn"
                , "description_en", "medal_conditions", "level_name", "medal_image", "medal_image_active"
                , "description_en", "user_medal_name_en"
            )->orderBy("sys_medal_id", "ASC")->get();

            $_arrOfRedis_medal = array();
            foreach ($_arrOfSysMedal as $value) {
                $_node = array(
                    "sys_medal_id" => $value["sys_medal_id"],
                    "sys_sys_medal_id" => $value["sys_sys_medal_id"],
                    "user_medal_name_cn" => $value["user_medal_name_cn"],
                    "user_medal_name_en" => $value["user_medal_name_en"],
                    "description_cn" => $value["description_cn"],
                    "description_en" => $value["description_en"],
                    "medal_conditions" => json_decode($value["medal_conditions"], true),
                    "level_name" => $value["level_name"],
                    "medal_image" => $value["medal_image"],
                    "medal_image_active" => $value["medal_image_active"],
                    "status" => 1,
                );

                if ($value["sys_sys_medal_id"] == null) {
                    $_node["node_medal"] = array();
                    $_arrOfRedis_medal[$value["sys_medal_id"]] = $_node;
                } else {
                    array_push($_arrOfRedis_medal[$value["sys_sys_medal_id"]]["node_medal"], $_node);
                }
            }

            foreach ($_arrOfRedis_medal as $key => $value) {
                Redis::hset("sys_medal", $key, json_encode($value));
            }

            $_sys_medal = $_arrOfRedis_medal;
        }

        $_arrOfUserMedalKey = array();
        foreach ($_sys_medal as $value) {
            $_medal = json_decode($value, true);
            $_medal_node = $_medal["node_medal"][0];
            $_medal_node["is_get"] = false;
            $_arrOfUserMedalKey[$_medal["sys_medal_id"]] = $_medal_node;
        }

        $_arrOfUserMedal = UserMedalAssociated::where([
            "user_medal_associated.status" => 1,
            "user_medal_associated.user_id" => $_usr_user["user_id"],
        ])->join("sys_medal", function ($join) {
            $join->on("user_medal_associated.sys_medal_id", "=", "sys_medal.sys_medal_id");
        })->select(
            "sys_medal.sys_medal_id", "sys_medal.sys_sys_medal_id", "sys_medal.medal_image", "sys_medal.medal_image_active", "sys_medal.user_medal_name_cn"
            , "sys_medal.user_medal_name_en", "sys_medal.description_cn", "sys_medal.description_en", "sys_medal.level_name", "sys_medal.medal_conditions"
        )->distinct("sys_medal.sys_medal_id")->orderBy("sys_medal.sys_medal_id", "ASC")->get();


        foreach ($_arrOfUserMedal as $value) {
            if (array_key_exists($value["sys_sys_medal_id"], $_arrOfUserMedalKey)) {
                $value["is_get"] = true;
                $_arrOfUserMedalKey[$value["sys_sys_medal_id"]] = $value;
            }
        }

        $_arrOfUserMedalValue = array_values($_arrOfUserMedalKey);
        foreach ($_arrOfUserMedalValue as $key => $value) {
            $value["medal_image"] = StaticDataController::$_server_url . "/" . $value["medal_image"];

            if ($value["is_get"]) {
                $value["medal_image_active"] = StaticDataController::$_server_url . "/" . $value["medal_image_active"];
            } else {
                $value["medal_image_active"] = $value["medal_image"];
            }
            $_arrOfUserMedalValue[$key] = $value;
        }

        return array_values($_arrOfUserMedalValue);
    }


    /**
     * @abstract 获取用户记录
     * @param String $_user_token
     * @return mixed
     */
    public static function getUserAchievement(string $_user_token)
    {
        Redis::select(1);
//        获取用户信息
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_redis_user_achievement = isset($_usr_user["achievement"]) ? $_usr_user["achievement"] : array();

        if (count($_redis_user_achievement) == 0) {
            $_redis_user_achievement = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
                "runball_exponent" => 0,
            );
            $_usr_user["achievement"] = $_redis_user_achievement;
        } else {
            return $_redis_user_achievement;
        }

        $_arrOfUserAchievement = UserAchievement::where([
            "user_id" => $_usr_user["user_id"],
            "status" => 1
        ])->select("user_achievement_id", "runball_exponent", "duration", "speed_max", "circle_count", "endurance_max", "play_count", "distance_max", "thrmin", "half_marathon")->get();

//        创建数据
        if (count($_arrOfUserAchievement) == 0) {
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_redis_user_achievement["user_achievement_id"] = $_sno->nextId();
            $_redis_user_achievement["status"] = 1;
            $_redis_user_achievement["user_id"] = $_usr_user["user_id"];

            UserAchievement::create($_redis_user_achievement);
        } else {
            $_redis_user_achievement = array(
                "duration" => $_arrOfUserAchievement[0]["duration"],
                "speed_max" => $_arrOfUserAchievement[0]["speed_max"],
                "circle_count" => $_arrOfUserAchievement[0]["circle_count"],
                "endurance_max" => $_arrOfUserAchievement[0]["endurance_max"],
                "play_count" => $_arrOfUserAchievement[0]["play_count"],
                "thrmin" => $_arrOfUserAchievement[0]["thrmin"],
                "half_marathon" => $_arrOfUserAchievement[0]["half_marathon"],
                "distance_max" => $_arrOfUserAchievement[0]["distance_max"],
                "runball_exponent" => $_arrOfUserAchievement[0]["runball_exponent"],
            );
        }

        return $_redis_user_achievement;
    }

}
