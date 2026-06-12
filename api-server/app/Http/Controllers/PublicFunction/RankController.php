<?php


namespace App\Http\Controllers\PublicFunction;


use App\Constants\SettingMessage;
use App\Models\MatchsUserGrade;
use App\Models\SysMatch;
use App\Models\UserAchievement;
use App\Models\UserPlay;
use App\Models\UsrUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RankController
{

    public static function UserAchivementV2($_user_age_type = 0, $_ranking_type, $_user_type, $_address, $_api_type, $_page, $_limit, $_user_id)
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserAcchievementQuery = UserAchievement::where([
            "user_achievement.status" => 1,
            "usr_user.is_yang" => $_user_age_type,
            'usr_user.sys_user_type_id' => '1809649560981504'
        ])
            ->where(function ($query) use ($_address, $_user_type) {
                if ($_address) {
//                    $query->where('usr_user.address', 'like', '%' . $_address . '%');

                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("user_achievement.user_id", "=", "usr_user.user_id");
            });

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("runball_exponent", ">", 0);
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("speed_max", ">", 0);
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("exponent_molecular", ">", 0);
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("marathon", ">", 0);
                break;
        }

        $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "user_achievement.runball_exponent"
            , "user_achievement.speed_max", "user_achievement.exponent_molecular", "user_achievement.marathon"
            , "user_achievement.speed_max_time", "user_achievement.runball_exponent_time", "user_achievement.exponent_molecular_time"
            , "user_achievement.marathon_time", "usr_user.address"
        )->distinct("usr_user.user_id");

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("runball_exponent", "DESC")->orderBy("runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("speed_max", "DESC")->orderBy("speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("exponent_molecular", "DESC")->orderBy("exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("marathon", "ASC")->orderBy("marathon_time", "ASC");
                break;
        }

        $_arrOfUserAcchievementCount = $_arrOfUserAcchievementQuery->get();
        $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->skip($_offset)->take($_limit)->get();


        $_return_arr = array();
        foreach ($_arrOfUserAcchievement as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_ranking_type) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)$value["runball_exponent"];
                    $_time = date($_format, $value["runball_exponent_time"]);
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (string)$value["speed_max"];
                    $_time = date($_format, $value["speed_max_time"]);
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["exponent_molecular"] / 1000, 2);
                    $_time = date($_format, $value["exponent_molecular_time"]);
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = (string)self::timeFormat($value["marathon"]);;
                    $_time = date($_format, $value["marathon_time"]);
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time
            ));
        }


        $_my_ranking = 0;
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserAcchievementCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "my_ranking" => $_my_ranking,
                "count" => count($_arrOfUserAcchievementCount),
                "list" => $_return_arr,
            )
        );
    }


    public static function UserAchivementV3($_user_age_type, $_ranking_type, $_user_type, $_address, $_api_type, $_page, $_limit, $_user_id,$_sys_sex_id,$_title = '')
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserAcchievementQuery = UserAchievement::where([
            "user_achievement.status" => 1,
            'usr_user.sys_user_type_id' => '1809649560981504'
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("user_achievement.user_id", "=", "usr_user.user_id");
            });

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("runball_exponent", ">", 0);
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("speed_max", ">", 0);
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("exponent_molecular", ">", 0);
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("marathon", ">", 0);
                break;
        }

        $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "user_achievement.runball_exponent"
            , "user_achievement.speed_max", "user_achievement.exponent_molecular", "user_achievement.marathon"
            , "user_achievement.speed_max_time", "user_achievement.runball_exponent_time", "user_achievement.exponent_molecular_time"
            , "user_achievement.marathon_time", "usr_user.address","usr_user.sys_sex_id"
        )->distinct("usr_user.user_id");

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("runball_exponent", "DESC")->orderBy("runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("speed_max", "DESC")->orderBy("speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("exponent_molecular", "DESC")->orderBy("exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("marathon", "ASC")->orderBy("marathon_time", "ASC");
                break;
        }

        $_arrOfUserAcchievementCount = $_arrOfUserAcchievementQuery->get();

        if ($_title){//用户名搜索
            $_arrOfUserAcchievementQuery->where('user_name','like','%'.$_title.'%');
        }
        $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->skip($_offset)->take($_limit)->get();


        $_return_arr = array();
        foreach ($_arrOfUserAcchievement as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_ranking_type) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)$value["runball_exponent"];
                    $_time = date($_format, $value["runball_exponent_time"]);
                    $time_unix = $value["runball_exponent_time"];
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (string)$value["speed_max"];
                    $_time = date($_format, $value["speed_max_time"]);
                    $time_unix = $value["speed_max_time"];
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["exponent_molecular"] / 1000, 3);
                    $_time = date($_format, $value["exponent_molecular_time"]);
                    $time_unix = $value["exponent_molecular_time"];
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = (string)self::timeFormat($value["marathon"]);;
                    $_time = date($_format, $value["marathon_time"]);
                    $time_unix = $value["marathon_time"];
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $time_unix,
                "sys_sex_id" => $value['sys_sex_id']
            ));
        }

        $_my_ranking = 0;
        $my_ranking_info = null;
        empty($_title) ? '' : $_return_arr = array();
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserAcchievementCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];

                    //            数据单位
                    $my_ranking_info['unit'] = "";
                    $my_ranking_info['value'] = "";
                    $my_ranking_info['time'] = "";
                    $my_ranking_info['time_unix'] = "";
                    $_format = "Y-m-d H:i:s";
                    switch ($_ranking_type) {
                        case "exponent"://摇跑指数
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)$value["runball_exponent"];
                            $my_ranking_info['time'] = date($_format, $value["runball_exponent_time"]);
                            $my_ranking_info['time_unix'] = $value["runball_exponent_time"];
                            break;
                        case "max_speed"://个人最高速度
                            $my_ranking_info['unit'] = "rpm";
                            $my_ranking_info['value'] = (string)$value["speed_max"];
                            $my_ranking_info['time'] = date($_format, $value["speed_max_time"]);
                            $my_ranking_info['time_unix'] = $value["speed_max_time"];
                            break;
                        case "onemin"://个人1分钟，m
                            $my_ranking_info['unit'] = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                            $my_ranking_info['value'] = (string)round($value["exponent_molecular"] / 1000, 3);
                            $my_ranking_info['time'] = date($_format, $value["exponent_molecular_time"]);
                            $my_ranking_info['time_unix'] = $value["exponent_molecular_time"];
                            break;
                        case "marathon"://个人马拉松
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)self::timeFormat($value["marathon"]);;
                            $my_ranking_info['time'] = date($_format, $value["marathon_time"]);
                            $my_ranking_info['time_unix'] = $value["marathon_time"];
                            break;
                    }
                }

                if ($_title){//用户名搜索
                    foreach ($_arrOfUserAcchievement as $k => $v) {

                        if ($value['user_id'] == $v['user_id']){
                            //            数据单位
                            $_unit = "";
                            $_value = "";
                            $_time = "";
                            $_format = "Y-m-d H:i:s";
                            switch ($_ranking_type) {
                                case "exponent"://摇跑指数
                                    $_unit = "";
                                    $_value = (string)$v["runball_exponent"];
                                    $_time = date($_format, $v["runball_exponent_time"]);
                                    $time_unix = $v["runball_exponent_time"];
                                    break;
                                case "max_speed"://个人最高速度
                                    $_unit = "rpm";
                                    $_value = (string)$v["speed_max"];
                                    $_time = date($_format, $v["speed_max_time"]);
                                    $time_unix = $v["speed_max_time"];
                                    break;
                                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                                    $_value = (string)round($v["exponent_molecular"] / 1000, 3);
                                    $_time = date($_format, $v["exponent_molecular_time"]);
                                    $time_unix = $v["exponent_molecular_time"];
                                    break;
                                case "marathon"://个人马拉松
                                    $_unit = "";
                                    $_value = (string)self::timeFormat($v["marathon"]);;
                                    $_time = date($_format, $v["marathon_time"]);
                                    $time_unix = $v["marathon_time"];
                                    break;
                            }
//
                            array_push($_return_arr, array(
                                "index" => $key + 1,
                                "user_id" => $v["user_id"],
                                "user_img" => StaticDataController::$_server_url . "/" . $v["user_img"],
                                "user_name" => $v["user_name"],
                                "address" => $v["address"],
                                "value" => $_value,
                                "unit" => $_unit,
                                "time" => $_time,
                                "time_unix" => $time_unix,
                                "sys_sex_id" => $v['sys_sex_id']
                            ));
                        }
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "ranking_img" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img,
                "ranking_img_en" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img_en,
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => $my_ranking_info,
                "count" => count($_arrOfUserAcchievementCount),
                "list" => $_return_arr,
            )
        );
    }

    /**
     * @param $_user_age_type
     * @param $_ranking_type
     * @param $_user_type
     * @param $_address
     * @param $_api_type
     * @param $_page
     * @param $_limit
     * @param $_user_id
     * @param $_sys_sex_id
     * @param $_title
     * @return array
     * User: zxw
     * Date: 2022/3/23 10:09
     */
    public static function myRankingDetails($_user_age_type, $_ranking_type, $_user_type, $_address, $_api_type, $_page, $_limit, $_user_id,$_sys_sex_id,$_title = '')
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserAcchievementQuery = UserAchievement::where([
            "user_achievement.status" => 1,
            'usr_user.sys_user_type_id' => '1809649560981504'
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("user_achievement.user_id", "=", "usr_user.user_id");
            });

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("runball_exponent", ">", 0);
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("speed_max", ">", 0);
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("exponent_molecular", ">", 0);
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("marathon", ">", 0);
                break;
        }

        $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "user_achievement.runball_exponent"
            , "user_achievement.speed_max", "user_achievement.exponent_molecular", "user_achievement.marathon"
            , "user_achievement.speed_max_time", "user_achievement.runball_exponent_time", "user_achievement.exponent_molecular_time"
            , "user_achievement.marathon_time", "usr_user.address","usr_user.sys_sex_id"
        )->distinct("usr_user.user_id");

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("runball_exponent", "DESC")->orderBy("runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("speed_max", "DESC")->orderBy("speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("exponent_molecular", "DESC")->orderBy("exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("marathon", "ASC")->orderBy("marathon_time", "ASC");
                break;
        }

        $_arrOfUserAcchievementCount = $_arrOfUserAcchievementQuery->get();

        if ($_title){//用户名搜索
            $_arrOfUserAcchievementQuery->where('user_name','like','%'.$_title.'%');
        }
        $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->skip($_offset)->take($_limit)->get();


        $_return_arr = array();
        foreach ($_arrOfUserAcchievement as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_ranking_type) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)$value["runball_exponent"];
                    $_time = date($_format, $value["runball_exponent_time"]);
                    $time_unix = $value["runball_exponent_time"];
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (string)$value["speed_max"];
                    $_time = date($_format, $value["speed_max_time"]);
                    $time_unix = $value["speed_max_time"];
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["exponent_molecular"] / 1000, 3);
                    $_time = date($_format, $value["exponent_molecular_time"]);
                    $time_unix = $value["exponent_molecular_time"];
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = (string)self::timeFormat($value["marathon"]);;
                    $_time = date($_format, $value["marathon_time"]);
                    $time_unix = $value["marathon_time"];
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $time_unix,
                "sys_sex_id" => $value['sys_sex_id']
            ));
        }

        $_my_ranking = 0;
        $my_ranking_info = null;
        empty($_title) ? '' : $_return_arr = array();
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserAcchievementCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];

                    //            数据单位
                    $my_ranking_info['unit'] = "";
                    $my_ranking_info['value'] = "";
                    $my_ranking_info['time'] = "";
                    $my_ranking_info['time_unix'] = "";
                    $_format = "Y-m-d H:i:s";
                    switch ($_ranking_type) {
                        case "exponent"://摇跑指数
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)$value["runball_exponent"];
                            $my_ranking_info['time'] = date($_format, $value["runball_exponent_time"]);
                            $my_ranking_info['time_unix'] = $value["runball_exponent_time"];
                            break;
                        case "max_speed"://个人最高速度
                            $my_ranking_info['unit'] = "rpm";
                            $my_ranking_info['value'] = (string)$value["speed_max"];
                            $my_ranking_info['time'] = date($_format, $value["speed_max_time"]);
                            $my_ranking_info['time_unix'] = $value["speed_max_time"];
                            break;
                        case "onemin"://个人1分钟，m
                            $my_ranking_info['unit'] = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                            $my_ranking_info['value'] = (string)round($value["exponent_molecular"] / 1000, 3);
                            $my_ranking_info['time'] = date($_format, $value["exponent_molecular_time"]);
                            $my_ranking_info['time_unix'] = $value["exponent_molecular_time"];
                            break;
                        case "marathon"://个人马拉松
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)self::timeFormat($value["marathon"]);;
                            $my_ranking_info['time'] = date($_format, $value["marathon_time"]);
                            $my_ranking_info['time_unix'] = $value["marathon_time"];
                            break;
                    }
                }

                if ($_title){//用户名搜索
                    foreach ($_arrOfUserAcchievement as $k => $v) {

                        if ($value['user_id'] == $v['user_id']){
                            //            数据单位
                            $_unit = "";
                            $_value = "";
                            $_time = "";
                            $_format = "Y-m-d H:i:s";
                            switch ($_ranking_type) {
                                case "exponent"://摇跑指数
                                    $_unit = "";
                                    $_value = (string)$v["runball_exponent"];
                                    $_time = date($_format, $v["runball_exponent_time"]);
                                    $time_unix = $v["runball_exponent_time"];
                                    break;
                                case "max_speed"://个人最高速度
                                    $_unit = "rpm";
                                    $_value = (string)$v["speed_max"];
                                    $_time = date($_format, $v["speed_max_time"]);
                                    $time_unix = $v["speed_max_time"];
                                    break;
                                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                                    $_value = (string)round($v["exponent_molecular"] / 1000, 3);
                                    $_time = date($_format, $v["exponent_molecular_time"]);
                                    $time_unix = $v["exponent_molecular_time"];
                                    break;
                                case "marathon"://个人马拉松
                                    $_unit = "";
                                    $_value = (string)self::timeFormat($v["marathon"]);;
                                    $_time = date($_format, $v["marathon_time"]);
                                    $time_unix = $v["marathon_time"];
                                    break;
                            }
//
                            array_push($_return_arr, array(
                                "index" => $key + 1,
                                "user_id" => $v["user_id"],
                                "user_img" => StaticDataController::$_server_url . "/" . $v["user_img"],
                                "user_name" => $v["user_name"],
                                "address" => $v["address"],
                                "value" => $_value,
                                "unit" => $_unit,
                                "time" => $_time,
                                "time_unix" => $time_unix,
                                "sys_sex_id" => $v['sys_sex_id']
                            ));
                        }
                    }
                }
            }
        }

        return $my_ranking_info;
    }

    /**
     * 排行榜v2（个人排行榜）==新版赛事
     * @param $_user_age_type 用户年龄类型，0：成年榜，1：青年榜
     * @param $_ranking_type 榜单类型  exponent：摇跑指数，max_speed：摇跑最高转速，onemin：摇跑一分钟，marathon：摇跑马拉松
     * @param $_user_type 个人与团队
     * @param $_address 城市
     * @param $_api_type
     * @param $_page 当前页
     * @param $_limit 每页展示数量
     * @param $_user_id 用户id
     * @param $_sys_sex_id 用户性别
     * @param $sys_match_id
     * @param $sys_sys_match_id
     * @return array
     * User: zxw
     * Date: 2021/10/15 9:19
     */
    public static function matchPersonalLeaderboardV2($_user_age_type, $_ranking_type, $_user_type, $_address, $_api_type, $_page, $_limit, $_user_id,$_sys_sex_id,$sys_match_id,$sys_sys_match_id)
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserAcchievementQuery = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $sys_match_id,
            "matchs_stage.sys_sys_match_id" => $sys_sys_match_id,
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            'usr_user.sys_user_type_id' => '1809649560981504' //游客的不展示
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
            })
            ->join("matchs_stage", function ($join) {
                $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
            });

        switch ($_ranking_type) {//是否展示成绩为0的用户
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_runball_exponent", ">", 0);
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_speed_max", ">", 0);
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_exponent_molecular", ">", 0);
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_marathon", ">", 0);
                break;
        }

        $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.s_runball_exponent"
            , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
            , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
            , "matchs_user_grade.s_marathon_time", "usr_user.address","usr_user.sys_sex_id","usr_user.sys_user_type_id",DB::raw("10000/matchs_user_grade.s_marathon AS s_marathon_asc"), "matchs_user_grade.team_tag"
        )->distinct("usr_user.user_id");

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_runball_exponent", "DESC")->orderBy("s_runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_speed_max", "DESC")->orderBy("s_speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_exponent_molecular", "DESC")->orderBy("s_exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_marathon_asc", "DESC")->orderBy("s_marathon_time", "ASC");
                break;
        }

        $_arrOfUserAcchievementCount = $_arrOfUserAcchievementQuery->get();

        //TODO 修复前端分页bug...
        if ($_page == 1){
            $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->get();
        }else{
            $_arrOfUserAcchievement = [];
        }

//      $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->skip($_offset)->take($_limit)->get();



        $_return_arr = array();
        foreach ($_arrOfUserAcchievement as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_ranking_type) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)round($value["s_runball_exponent"],2);
                    $_time = $_value > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_runball_exponent_time"] : '';
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (int)$value["s_speed_max"];
                    $_time = $_value > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_speed_max_time"] : '';
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["s_exponent_molecular"] / 1000, 2);
                    $_time = $_value > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_exponent_molecular_time"] : '';
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = empty($value["s_marathon"]) ? 0 : (string)self::timeFormat($value["s_marathon"]);
                    $_time = $_value !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_marathon_time"] : '';
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $_time_unix,
                "sys_sex_id" => $value['sys_sex_id'],
                "team_tag" => $value['team_tag'],
            ));
        }


        $_my_ranking = 0;
        $my_ranking_info = null;
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserAcchievementCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];
                    $my_ranking_info['team_tag'] = $value['team_tag'];

                    //            数据单位
                    $my_ranking_info['unit'] = "";
                    $my_ranking_info['value'] = "";
                    $my_ranking_info['time'] = "";
                    $my_ranking_info['time_unix'] = "";
                    $_format = "Y-m-d H:i:s";
                    switch ($_ranking_type) {
                        case "exponent"://摇跑指数
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)round($value["s_runball_exponent"],2);
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_runball_exponent_time"] : '';
                            break;
                        case "max_speed"://个人最高速度
                            $my_ranking_info['unit'] = "rpm";
                            $my_ranking_info['value'] = (int)$value["s_speed_max"];
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_speed_max_time"] : '';
                            break;
                        case "onemin"://个人1分钟，m
                            $my_ranking_info['unit'] = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                            $my_ranking_info['value'] = (string)round($value["s_exponent_molecular"] / 1000, 2);
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_exponent_molecular_time"] : '';
                            break;
                        case "marathon"://个人马拉松
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = empty($value["s_marathon"]) ? 0 : (string)self::timeFormat($value["s_marathon"]);
                            $my_ranking_info['time'] = $my_ranking_info['value'] !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_marathon_time"] : '';
                            break;
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => empty($my_ranking_info) ? null : $my_ranking_info,
                "count" => count($_arrOfUserAcchievementCount),
                "list" => $_return_arr,
            )
        );
    }
    
    
        /**
     * 排行榜v2（个人排行榜-用户所在团队内的比较）==新版赛事
     * @param $_user_age_type 用户年龄类型，0：成年榜，1：青年榜
     * @param $_ranking_type 榜单类型  exponent：摇跑指数，max_speed：摇跑最高转速，onemin：摇跑一分钟，marathon：摇跑马拉松
     * @param $_user_type 个人与团队
     * @param $_address 城市
     * @param $_api_type
     * @param $_page 当前页
     * @param $_limit 每页展示数量
     * @param $_user_id 用户id
     * @param $_sys_sex_id 用户性别
     * @param $sys_match_id
     * @param $sys_sys_match_id
     * @return array
     * User: zxw
     * Date: 2021/10/15 9:19
     */
    public static function matchPersonalLeaderboardV3($_user_age_type, $_ranking_type, $_user_type, $_address, $_api_type, $_page, $_limit, $_user_id,$_sys_sex_id,$sys_match_id,$sys_sys_match_id)
    {
        
        //查询自己所在团队标签
        $teamTagData = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $sys_match_id,
            "matchs_stage.sys_sys_match_id" => $sys_sys_match_id,
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            "matchs_user_grade.user_id" => $_user_id,
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
        })->select('matchs_user_grade.team_tag', 'matchs_user_grade.user_id')
            ->first();
            
        if (empty($teamTagData)) {
            return [
                "code" => 1,
                "msg" => "success",
                "data" => null
            ];
        }

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserAcchievementQuery = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $sys_match_id,
            "matchs_stage.sys_sys_match_id" => $sys_sys_match_id,
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            'usr_user.sys_user_type_id' => '1809649560981504' //游客的不展示
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id, $teamTagData) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
                $query->where('matchs_user_grade.team_tag', $teamTagData['team_tag']);
            })
            ->join("usr_user", function ($join) {
                $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
            })
            ->join("matchs_stage", function ($join) {
                $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
            });

        switch ($_ranking_type) {//是否展示成绩为0的用户
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_runball_exponent", ">", 0);
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_speed_max", ">", 0);
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_exponent_molecular", ">", 0);
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("s_marathon", ">", 0);
                break;
        }

        $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.s_runball_exponent"
            , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
            , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
            , "matchs_user_grade.s_marathon_time", "usr_user.address","usr_user.sys_sex_id","usr_user.sys_user_type_id",DB::raw("10000/matchs_user_grade.s_marathon AS s_marathon_asc"), "matchs_user_grade.team_tag"
        )->distinct("usr_user.user_id");

        switch ($_ranking_type) {
            case "exponent"://摇跑指数榜单
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_runball_exponent", "DESC")->orderBy("s_runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_speed_max", "DESC")->orderBy("s_speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_exponent_molecular", "DESC")->orderBy("s_exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->orderBy("s_marathon_asc", "DESC")->orderBy("s_marathon_time", "ASC");
                break;
        }

        $_arrOfUserAcchievementCount = $_arrOfUserAcchievementQuery->get();

        //TODO 修复前端分页bug...
        if ($_page == 1){
            $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->get();
        }else{
            $_arrOfUserAcchievement = [];
        }

//      $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->skip($_offset)->take($_limit)->get();



        $_return_arr = array();
        foreach ($_arrOfUserAcchievement as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_ranking_type) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)round($value["s_runball_exponent"],2);
                    $_time = $_value > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_runball_exponent_time"] : '';
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (int)$value["s_speed_max"];
                    $_time = $_value > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_speed_max_time"] : '';
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["s_exponent_molecular"] / 1000, 2);
                    $_time = $_value > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_exponent_molecular_time"] : '';
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = empty($value["s_marathon"]) ? 0 : (string)self::timeFormat($value["s_marathon"]);
                    $_time = $_value !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_marathon_time"] : '';
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $_time_unix,
                "sys_sex_id" => $value['sys_sex_id'],
                "team_tag" => $value['team_tag'],
            ));
        }


        $_my_ranking = 0;
        $my_ranking_info = null;
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserAcchievementCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];
                    $my_ranking_info['team_tag'] = $value['team_tag'];

                    //            数据单位
                    $my_ranking_info['unit'] = "";
                    $my_ranking_info['value'] = "";
                    $my_ranking_info['time'] = "";
                    $my_ranking_info['time_unix'] = "";
                    $_format = "Y-m-d H:i:s";
                    switch ($_ranking_type) {
                        case "exponent"://摇跑指数
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = (string)round($value["s_runball_exponent"],2);
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_runball_exponent_time"] : '';
                            break;
                        case "max_speed"://个人最高速度
                            $my_ranking_info['unit'] = "rpm";
                            $my_ranking_info['value'] = (int)$value["s_speed_max"];
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_speed_max_time"] : '';
                            break;
                        case "onemin"://个人1分钟，m
                            $my_ranking_info['unit'] = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                            $my_ranking_info['value'] = (string)round($value["s_exponent_molecular"] / 1000, 2);
                            $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_exponent_molecular_time"] : '';
                            break;
                        case "marathon"://个人马拉松
                            $my_ranking_info['unit'] = "";
                            $my_ranking_info['value'] = empty($value["s_marathon"]) ? 0 : (string)self::timeFormat($value["s_marathon"]);
                            $my_ranking_info['time'] = $my_ranking_info['value'] !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                            $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_marathon_time"] : '';
                            break;
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => empty($my_ranking_info) ? null : $my_ranking_info,
                "count" => count($_arrOfUserAcchievementCount),
                "list" => $_return_arr,
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/9 18:53
     * @abstract _从数据库查询用户
     */
    public static function UserAchievement($_redis_key)
    {
        Redis::select(1);

//            查询前100条数据
        $_arrOfUserAcchievementQuery = UserAchievement::where([
            "user_achievement.status" => 1,
        ]);


//        摇跑指数
        if ($_redis_key == "rank_list_exponent") {
            $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("user_achievement.runball_exponent", ">", 0)
                ->orderBy("user_achievement.runball_exponent", "DESC");
        }

//        个人最高速度
        if ($_redis_key == "rank_list_max_speed") {
            $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("user_achievement.speed_max", ">", 0)
                ->orderBy("user_achievement.speed_max", "DESC");
        }

//        个人三分钟
        if ($_redis_key == "rank_list_self_thrmin") {
            $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("user_achievement.thrmin", ">", 0)
                ->orderBy("user_achievement.thrmin", "DESC");
        }

//        个人1分钟
        if ($_redis_key == "rank_list_self_onemin") {
            $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("user_achievement.exponent_molecular", ">", 0)
                ->orderBy("user_achievement.exponent_molecular", "DESC");
        }

//        个人全马
        if ($_redis_key == "rank_list_self_marathon") {
            $_arrOfUserAcchievementQuery = $_arrOfUserAcchievementQuery->where("user_achievement.marathon", ">", 0)
                ->orderBy("user_achievement.marathon", "ASC");
        }

//        获取前1000条数据
        $_arrOfUserAcchievement = $_arrOfUserAcchievementQuery->select(
            "user_achievement.thrmin", "user_achievement.half_marathon", "user_achievement.speed_max", "user_achievement.marathon", "user_achievement.runball_exponent"
            , "user_achievement.exponent_molecular", "user_achievement.user_id"
        )->skip(0)->take(500)->get();


//            计算摇跑指数
        if ($_redis_key == "rank_list_exponent") {
            foreach ($_arrOfUserAcchievement as $value) {
                Redis::zadd($_redis_key, $value["runball_exponent"], $value["user_id"]);
            }
        }

//        个人最高速度，圈 每分钟 rpm
        if ($_redis_key == "rank_list_max_speed") {
            foreach ($_arrOfUserAcchievement as $value) {
                Redis::zadd($_redis_key, $value["speed_max"], $value["user_id"]);
            }
        }

//        个人三分钟榜单，运动距离，米
        if ($_redis_key == "rank_list_self_thrmin") {
            foreach ($_arrOfUserAcchievement as $value) {
                $_format_thrmin = round($value["thrmin"] / 1000, 2);

                Redis::zadd($_redis_key, $_format_thrmin, $value["user_id"]);
            }
        }
//        个人1分钟榜单，运动距离，米
        if ($_redis_key == "rank_list_self_onemin") {
            foreach ($_arrOfUserAcchievement as $value) {
                $_format_thrmin = $value["exponent_molecular"];

                Redis::zadd($_redis_key, $_format_thrmin, $value["user_id"]);
            }
        }

//        个人全马拉松，运动时间，s
        if ($_redis_key == "rank_list_self_marathon") {
            foreach ($_arrOfUserAcchievement as $value) {
                Redis::zadd($_redis_key, $value["marathon"], $value["user_id"]);
            }
        }

//        重新获取redis数据返回
        $_arrOfRedisList = Redis::zrevrange($_redis_key, 0, 100);

        return $_arrOfRedisList;
    }

    /**
     * @author pengjl
     * @time 2021/5/9 19:04
     * @abstract _根据榜单用户ID，查询用户基本信息
     */
    public static function UserRankList($_arrOfRedisList, $_redis_key)
    {
//        从数据库查询用户信息
        $_arrOfUsrUser = UsrUser::where([
            "status" => 1
        ])->whereIn("user_id", $_arrOfRedisList)->select("user_id", "user_img", "user_name", "self_description")->get();

        $_arrOfUsrUserKey = array();
        foreach ($_arrOfUsrUser as $value) {
            $_arrOfUsrUserKey[$value["user_id"]] = $value;
        }

        $_arrOfUser = array();
        foreach ($_arrOfRedisList as $key => $value) {
//            用户信息
            $_user = $_arrOfUsrUserKey[$value];

//            数据单位
            $_unit = "";
            $_value = "";
            switch ($_redis_key) {
                case "rank_list_exponent"://摇跑指数
                    $_unit = "";
                    $_value = round(Redis::zscore($_redis_key, $value), 2);
                    break;
                case "rank_list_max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = Redis::zscore($_redis_key, $value);
                    break;
                case "rank_list_self_thrmin"://个人三分钟，km
                    $_unit = "km";
                    $_value = round(Redis::zscore($_redis_key, $value), 2);
                    break;
                case "rank_list_self_marathon"://个人马拉松
                    $_unit = "";
                    $_value = self::timeFormat(Redis::zscore($_redis_key, $value));
                    break;
                default:
                    $_unit = "m";
                    $_value = round(Redis::zscore($_redis_key, $value), 2);
            }
//
            array_push($_arrOfUser, array(
                "index" => $key + 1,
                "user_id" => $_user["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $_user["user_img"],
                "user_name" => $_user["user_name"],
                "self_description" => $_user["self_description"],
                "value" => $_value,
                "unit" => $_unit
            ));
        }

        return $_arrOfUser;
    }


    /**
     * @author pengjl
     * @time 2021/5/9 19:22
     * @abstract _时间格式化
     */
    public static function timeFormat($_seconds)
    {
        /*$_hours = floor($_seconds / 3600);

        $_min = floor(($_seconds - $_hours * 3600) / 60);

        $_sec = $_seconds - $_hours * 3600 - $_min * 60;

        if ($_hours < 10) {
            $_hours = "0" . $_hours;
        }
        if ($_min < 10) {
            $_min = "0" . $_min;
        }
        if ($_sec < 10) {
            $_sec = "0" . $_sec;
        }

        return $_hours . ":" . $_min . ":" . $_sec;*/

        $ymd = strtotime(date('Y-m-d'));
        $end = $ymd + $_seconds;
        return date('H:i:s',$end);
    }

    public static function RankingTodayHighestSpeed($_user_age_type, $_user_type, $_address, $_page, $_limit, $_user_id,$_sys_sex_id,$_day_time,$_title = '')
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserPlayQuery = UserPlay::where([
            "user_play.status" => 1,
            "user_play.is_abnormal" => 0,
            'usr_user.sys_user_type_id' => '1809649560981504'
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("user_play.user_id", "=", "usr_user.user_id");
            });

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("speed_max", ">", 0);

        // 如果 $_day_time 存在并且格式有效，则使用它作为查询条件，否则查询当天的数据
        if (!empty($_day_time)) {
            $_arrOfUserPlayQuery->whereRaw('DATE(FROM_UNIXTIME(user_play.start_time)) = ?', [$_day_time]);
        } else {
            $_arrOfUserPlayQuery->whereRaw('DATE(FROM_UNIXTIME(user_play.start_time)) = CURDATE()');
        }

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", DB::raw('MAX(user_play.speed_max) as speed_max'), "user_play.created_time", "user_play.source"
            , "user_play.distance", "user_play.start_time", "user_play.stop_time")->groupBy('usr_user.user_id');

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("speed_max", "DESC")->orderBy("created_time", "DESC");

        $_arrOfUserPlayCount = $_arrOfUserPlayQuery->get();

        if ($_title){//用户名搜索
            $_arrOfUserPlayQuery->where('user_name','like','%'.$_title.'%');
        }
        $_arrOfUserPlay = $_arrOfUserPlayQuery->skip($_offset)->take($_limit)->get();

        $_return_arr = array();
        foreach ($_arrOfUserPlay as $key => $value) {
//            数据单位
            $_format = "Y-m-d H:i:s";

            $_unit = "rpm";
            $_value = (string)$value["speed_max"];
            $_time = date($_format, $value["stop_time"]);
            $time_unix = $value["stop_time"];
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $time_unix,
                "sys_sex_id" => $value['sys_sex_id']
            ));
        }

        $_my_ranking = 0;
        $my_ranking_info = null;
        empty($_title) ? '' : $_return_arr = array();
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserPlayCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];

                    //            数据单位
                    $_format = "Y-m-d H:i:s";

                    $my_ranking_info['unit'] = "rpm";
                    $my_ranking_info['value'] = (string)$value["speed_max"];
                    $my_ranking_info['time'] = date($_format, $value["stop_time"]);
                    $my_ranking_info['time_unix'] = $value["stop_time"];

                }

                if ($_title){//用户名搜索
                    foreach ($_arrOfUserPlay as $k => $v) {

                        if ($value['user_id'] == $v['user_id']){
                            //            数据单位
                            $_format = "Y-m-d H:i:s";

                            $_unit = "rpm";
                            $_value = (string)$v["speed_max"];
                            $_time = date($_format, $v["stop_time"]);
                            $time_unix = $v["stop_time"];
//
                            array_push($_return_arr, array(
                                "index" => $key + 1,
                                "user_id" => $v["user_id"],
                                "user_img" => StaticDataController::$_server_url . "/" . $v["user_img"],
                                "user_name" => $v["user_name"],
                                "address" => $v["address"],
                                "value" => $_value,
                                "unit" => $_unit,
                                "time" => $_time,
                                "time_unix" => $time_unix,
                                "sys_sex_id" => $v['sys_sex_id']
                            ));
                        }
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "ranking_img" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img,
                "ranking_img_en" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img_en,
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => $my_ranking_info,
                "count" => count($_arrOfUserPlayCount),
                "list" => $_return_arr,
            )
        );
    }

    public static function RankingAccumulatedDistanceToday($_user_age_type, $_user_type, $_address, $_page, $_limit, $_user_id,$_sys_sex_id,$_day_time,$_title = '')
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfUserPlayQuery = UserPlay::where([
            "user_play.status" => 1,
            "user_play.is_abnormal" => 0,
            'usr_user.sys_user_type_id' => '1809649560981504'
        ])
            ->where(function ($query) use ($_address, $_user_type, $_user_age_type, $_sys_sex_id) {
                if ($_user_age_type) {
                    $query->where('usr_user.is_yang', $_user_age_type);
                }

                if ($_address) {
                    $query->whereRaw('
                        CONCAT(usr_user.address, "市") LIKE "%' . $_address . '%"
                    ');
                }

                if ($_sys_sex_id){
                    $query->where('usr_user.sys_sex_id', $_sys_sex_id);
                }

                if ($_user_type == 1) {
                    $query->where('usr_user.is_group', $_user_type);
                } else {
                    $query->whereRaw(' (usr_user.is_group = 0 OR usr_user.is_group = -1) ');
                }
            })
            ->join("usr_user", function ($join) {
                $join->on("user_play.user_id", "=", "usr_user.user_id");
            });

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("speed_max", ">", 0);

        // 如果 $_day_time 存在并且格式有效，则使用它作为查询条件，否则查询当天的数据
        if (!empty($_day_time)) {
            $_arrOfUserPlayQuery->whereRaw('DATE(FROM_UNIXTIME(user_play.start_time)) = ?', [$_day_time]);
        } else {
            $_arrOfUserPlayQuery->whereRaw('DATE(FROM_UNIXTIME(user_play.start_time)) = CURDATE()');
        }

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", DB::raw('SUM(user_play.distance) as total_distance'), "user_play.created_time", "user_play.source"
            , "user_play.distance", "user_play.start_time", "user_play.stop_time")->groupBy('usr_user.user_id');

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("total_distance", "DESC")->orderBy("created_time", "DESC");

        $_arrOfUserPlayCount = $_arrOfUserPlayQuery->get();

        if ($_title){//用户名搜索
            $_arrOfUserPlayQuery->where('user_name','like','%'.$_title.'%');
        }
        $_arrOfUserPlay = $_arrOfUserPlayQuery->skip($_offset)->take($_limit)->get();


        $_return_arr = array();
        foreach ($_arrOfUserPlay as $key => $value) {
//            数据单位
            $_format = "Y-m-d H:i:s";

            $_unit = "km";
            $_value = (string)round($value["total_distance"] / 1000, 3);
            $_time = date($_format, $value["stop_time"]);
            $time_unix = $value["stop_time"];
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "user_id" => $value["user_id"],
                "user_img" => StaticDataController::$_server_url . "/" . $value["user_img"],
                "user_name" => $value["user_name"],
                "address" => $value["address"],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "time_unix" => $time_unix,
                "sys_sex_id" => $value['sys_sex_id']
            ));
        }

        $_my_ranking = 0;
        $my_ranking_info = null;
        empty($_title) ? '' : $_return_arr = array();
        if ($_user_id != null && $_user_id != "") {
            foreach ($_arrOfUserPlayCount as $key => $value) {
                if ($value["user_id"] === $_user_id) {
                    $_my_ranking = $key + 1;
                    $my_ranking_info['index'] = $_my_ranking;
                    $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                    $my_ranking_info['user_name'] = $value['user_name'];
                    $my_ranking_info['user_id'] = $value['user_id'];
                    $my_ranking_info['address'] = $value['address'];
                    $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];

                    //            数据单位
                    $_format = "Y-m-d H:i:s";

                    $my_ranking_info['unit'] = "km";
                    $my_ranking_info['value'] = (string)round($value["total_distance"] / 1000, 3);
                    $my_ranking_info['time'] = date($_format, $value["stop_time"]);
                    $my_ranking_info['time_unix'] = $value["stop_time"];

                }

                if ($_title){//用户名搜索
                    foreach ($_arrOfUserPlay as $k => $v) {

                        if ($value['user_id'] == $v['user_id']){
                            //            数据单位
                            $_format = "Y-m-d H:i:s";

                            $_unit = "rpm";
                            $_value = (string)round($value["total_distance"] / 1000, 3);
                            $_time = date($_format, $v["stop_time"]);
                            $time_unix = $v["stop_time"];
//
                            array_push($_return_arr, array(
                                "index" => $key + 1,
                                "user_id" => $v["user_id"],
                                "user_img" => StaticDataController::$_server_url . "/" . $v["user_img"],
                                "user_name" => $v["user_name"],
                                "address" => $v["address"],
                                "value" => $_value,
                                "unit" => $_unit,
                                "time" => $_time,
                                "time_unix" => $time_unix,
                                "sys_sex_id" => $v['sys_sex_id']
                            ));
                        }
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "ranking_img" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img,
                "ranking_img_en" => StaticDataController::$_server_url . "/" .SettingMessage::ranking_img_en,
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => $my_ranking_info,
                "count" => count($_arrOfUserPlayCount),
                "list" => $_return_arr,
            )
        );
    }

}
