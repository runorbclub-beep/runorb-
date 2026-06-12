<?php

namespace App\Http\Controllers\Api\LocalPlay;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\SnowFlakeSwooles;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\fileMoveController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Controllers\PublicFunction\UsrUserController;
use App\Http\Requests\Api\LocalPlay\PostUploadLocalPlayV3Request;
use App\Jobs\DataUploadQueue;
use App\Models\MatchsUserGrade;
use App\Models\UserAchievement;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Services\LocalPlayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class LocalPlayController extends Controller
{


    /**
     * @abstract 本地运动数据上传
     * @param Request $request
     * @return array
     */
    public function postUploadLocalPlay(Request $request)
    {

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        if (!isset($_FILES["file"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_file = $_FILES["file"];

//        文件路径处理、移动文件
        $_file_path = fileMoveController::getFilePath("local_play", $_file["name"]);
        move_uploaded_file($_file["tmp_name"], $_file_path["file_path"]);

        Redis::select(1);
        $_user_token = $request->header('token');
//        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);


        // $_file_path = "play_data.json";

        $_file = fopen($_file_path["file_path"], "r");

        $_jsonData = json_decode(fread($_file, filesize($_file_path["file_path"])), true);


        if (!isset($_jsonData["created_uid"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_objSnowflake = new Snowflake(StaticDataController::$_workId);
//        如果本次运动没有运动ID，创建新的，
        if (!isset($_jsonData["user_play_id"])) {
            $_jsonData["user_play_id"] = $_objSnowflake->nextId();
        }

//        查询当前用户最后一次运动数据
        $_arrOfUserPlayHis = UserPlay::where([
            "user_id" => $_jsonData["created_uid"],
            "is_abnormal" => 0,
        ])->where("stop_time", "<", $_jsonData["start_time"])->select("distance")->skip(0)->take(1)->get();

        $_compare_last = 0;

        if (count($_arrOfUserPlayHis) == 1 && $_arrOfUserPlayHis[0]["distance"] < $_jsonData["distance"]) {
            $_compare_last = 1;
        } else if (count($_arrOfUserPlayHis) == 1 && $_arrOfUserPlayHis[0]["distance"] > $_jsonData["distance"]) {
            $_compare_last = -1;
        }

        //修复前端不计算摇跑指数bug
        if (!empty($_jsonData["exponent_denominator"])) {
            $_jsonData["exponent"] = round($_jsonData["exponent_molecular"] / ($_jsonData["exponent_denominator"] / 60), 2);
        } else {
            $_jsonData["exponent"] = 0;
        }

//        存储用户运动数据 is_abnormal
        $_arrOfUserPlayData = array(
            "status" => 1,
            "duration" => $_jsonData["duration"],
            "speed_max" => $_jsonData["speed_max"],
            "circle_count" => $_jsonData["circle_count"],
            "endurance_max" => $_jsonData["endurance_max"],
            "compare_last" => $_compare_last,
            "start_time" => $_jsonData["start_time"],
            "stop_time" => $_jsonData["stop_time"],
            "distance" => $_jsonData["distance"],
            "user_id" => $_jsonData["created_uid"],
            "is_abnormal" => $_jsonData["is_abnormal"],
            "exponent_molecular" => $_jsonData["exponent_molecular"] ?? 0,//摇跑指数，分子
            "exponent_denominator" => $_jsonData["exponent_denominator"] ?? 0,//摇跑指数，分母
            "exponent" => $_jsonData["exponent"] ?? 0,//摇跑指数
            "marathon" => $_jsonData["marathon"] ?? 0,//摇跑马拉松（全马）
            "created_time" => time(),
            "updated_time" => time(),
        );

        if (isset($_jsonData["user_pk_list_id"])) {
            $_arrOfUserPlayData["user_pk_list_id"] = $_jsonData["user_pk_list_id"];
        }
        if (isset($_jsonData["matchs_stage_id"])) {
            $_arrOfUserPlayData["matchs_stage_id"] = $_jsonData["matchs_stage_id"];
        }
        if (isset($_jsonData["sys_shake_id"])) {
            $_arrOfUserPlayData["sys_shake_id"] = $_jsonData["sys_shake_id"];
        }

//        查询当前运动是否存在
        $_arrOfUserPlay = UserPlay::where(["user_play_id" => $_jsonData["user_play_id"]])->select("user_id")->get();
//        修改
        if (count($_arrOfUserPlay) == 1) {
            UserPlay::where(["user_play_id" => $_jsonData["user_play_id"]])->update($_arrOfUserPlayData);
            $_arrOfUserPlayData["user_play_id"] = $_jsonData["user_play_id"];
        } else {
//            创建
            $_arrOfUserPlayData["user_play_id"] = $_jsonData["user_play_id"];
            UserPlay::create($_arrOfUserPlayData);
        }


//        获取区间数组
        $_arr_section = array();
        $_speed_section = StaticDataController::$_speed_section;
        for ($_i = 0; $_i < count($_speed_section); $_i++) {
            if ($_i > 0) {
                $_key = $_speed_section[$_i - 1] . "-" . $_speed_section[$_i];
                $_arr_section[$_key] = array(
                    "start_section" => $_speed_section[$_i - 1],
                    "stop_section" => $_speed_section[$_i],
                    "speed_detail" => array(),
                    "section_duration" => 0
                );
            }
        }

//        转速时刻
        $_user_play_detail = array();
//        循环圈数数组，
        for ($_i = 0; $_i < count($_jsonData["speed_detail"]); $_i++) {
//            当前速度 rpm  （当前圈数-上一秒圈数）*60秒*(1000 / 时间间隔 毫秒)，
            $_speed = $_jsonData["speed_detail"][$_i];

            $_moment = $_jsonData["start_time"] * 1000 + $_jsonData["interval"] * $_i;
            array_push($_user_play_detail, array(
                "moment" => $_moment,
                "speed" => $_speed,
            ));

            foreach ($_arr_section as $key => $node) {
                if ($_speed >= $node["start_section"] && $_speed < $node["stop_section"]) {
                    array_push($node["speed_detail"], $_speed);
                }
                $_arr_section[$key] = $node;
            }
        }


        $_max_section_duration = 0;
        foreach ($_arr_section as $key => $value) {
            $value["section_duration"] = round(count($value["speed_detail"]) * $_jsonData["interval"] / 1000);
            unset($value["speed_detail"]);
            $_arr_section[$key] = $value;
            if ($_max_section_duration < $value["section_duration"]) {
                $_max_section_duration = $value["section_duration"];
            }
        }

        foreach ($_arr_section as $key => $value) {
            $_percentage = $_max_section_duration > 0 ? round($value["section_duration"] / $_max_section_duration * 100) : $_max_section_duration;
            $value["percentage"] = $_percentage;
            $_arr_section[$key] = $value;
        }


//        运动详情数据

        $_arrOfUserPlayDetailData = array(
            "status" => 1,
            "speed_interval" => $_jsonData["interval"],
            "user_play_id" => $_jsonData["user_play_id"],
            "section_duration" => json_encode($_arr_section),
            "speed_detail" => json_encode($_user_play_detail),
            "created_time" => time(),
            "updated_time" => time(),
        );


//        修改
        if (count($_arrOfUserPlay) == 1) {
            UserPlayDetail::where(["user_play_id" => $_jsonData["user_play_id"]])->update($_arrOfUserPlayDetailData);
        } else {
//            创建
            $_arrOfUserPlayDetailData["user_play_detail_id"] = $_objSnowflake->nextId();
            UserPlayDetail::create($_arrOfUserPlayDetailData);
        }

        $_arrOfNewMedal = array();
        $_arrOfNewAchievement = array();

//        正常数据时，判断是否正常
        if ($_jsonData["is_abnormal"] == 0) {
//          用户运动结束后，存储数据，判定是否突破记录
            $_arrOfNewAchievement = UsrUserController::userStopPlayHasNewAchievement($_arrOfUserPlayData, $_user_token, $_language);

//          运动结束后，判定是否获得新徽章
            $_arrOfNewMedal = UsrUserController::userStopPlayHasNewMedal($_arrOfUserPlayData, $_user_token, $_language);
        }

//        摇跑指数相关数据更新，参照 UserController postRunballExponentAdd 方法

        $_abnormal_index = StaticDataController::$_abnormal_index;
        $_arrOfUserPlayData["exponent_molecular"] = $_arrOfUserPlayData["exponent_molecular"] < $_abnormal_index['exponent_molecular'] ? $_arrOfUserPlayData["exponent_molecular"] : 0;
        $_arrOfUserPlayData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"] > $_abnormal_index['exponent_denominator'] ? $_arrOfUserPlayData["exponent_denominator"] : 0;
        $_runball_exponent = $_arrOfUserPlayData["exponent"] < $_abnormal_index['runball_exponent'] ? $_arrOfUserPlayData["exponent"] : 0;

//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_arrOfUserPlayData["user_play_id"]) && $_arrOfUserPlayData["is_abnormal"] == 1) {
            return array(
                "code" => 1,
                "msg" => "success"
            );
        }

        $_arrOfUserAchivement = UserAchievement::where([
            "user_id" => $_arrOfUserPlayData["user_id"]
        ])->select("exponent_molecular", "exponent_denominator", "runball_exponent")->get();

        if (count($_arrOfUserAchivement) == 1) {
            $_arrOfUserAchivementData = array();

//            如果摇跑指数，摇跑指数存在更新，
            if ($_arrOfUserAchivement[0]["runball_exponent"] <= $_runball_exponent) {
                $_arrOfUserAchivementData["runball_exponent"] = $_runball_exponent;
                $_arrOfUserAchivementData["runball_exponent_time"] = $_jsonData["stop_time"];
            }

//            如果指数分子，分母存在更新，（摇跑一分钟）
            if ($_arrOfUserAchivement[0]["exponent_molecular"] <= $_arrOfUserPlayData["exponent_molecular"]) {
                $_arrOfUserAchivementData["exponent_molecular"] = $_arrOfUserPlayData["exponent_molecular"];
                $_arrOfUserAchivementData["exponent_molecular_time"] = $_jsonData["stop_time"];
            }

//            如果半马拉松，半马拉松存在更新，（完成21km花的时间）
            if ($_arrOfUserAchivement[0]["exponent_denominator"] == 0 || $_arrOfUserAchivement[0]["exponent_denominator"] <= $_arrOfUserPlayData["exponent_denominator"]) {
                $_arrOfUserAchivementData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
            }

//            如果存在更新内容
            if (count($_arrOfUserAchivementData) > 0) {
                UserAchievement::where(["user_id" => $_arrOfUserPlayData["user_id"]])->update($_arrOfUserAchivementData);
            }
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "new_medal" => $_arrOfNewMedal,
                "new_achievement" => $_arrOfNewAchievement,
            )
        );

    }


    /**
     * @abstract 本地运动数据上传V2==新版赛事
     * @param Request $request
     * @return array
     */
    public function postUploadLocalPlayV2(Request $request)
    {

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        if (!isset($_FILES["file"])) {
            return SystemErrorController::paramtersError($_language);
        }
        Redis::select(1);
        $_user_token = $request->header('token');
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_file = $_FILES["file"];

//        文件路径处理、移动文件
        $_file_path = fileMoveController::getFilePath("local_play", $_file["name"], $_usr_user['user_id']);
        move_uploaded_file($_file["tmp_name"], $_file_path["file_path"]);

        // $_file_path = "play_data.json";

        $_file = fopen($_file_path["file_path"], "r");

        $_jsonData = json_decode(fread($_file, filesize($_file_path["file_path"])), true);


        if (!isset($_jsonData["created_uid"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_objSnowflake = new Snowflake(StaticDataController::$_workId);
//        如果本次运动没有运动ID，创建新的，
        if (!isset($_jsonData["user_play_id"])) {
            $_jsonData["user_play_id"] = $_objSnowflake->nextId();
        }

//        查询当前用户最后一次运动数据
        $_arrOfUserPlayHis = UserPlay::where([
            "user_id" => $_jsonData["created_uid"],
            "is_abnormal" => 0,
        ])->where("stop_time", "<", $_jsonData["start_time"])->select("distance")->skip(0)->take(1)->get();

        $_compare_last = 0;

        if (count($_arrOfUserPlayHis) == 1 && $_arrOfUserPlayHis[0]["distance"] < $_jsonData["distance"]) {
            $_compare_last = 1;
        } else if (count($_arrOfUserPlayHis) == 1 && $_arrOfUserPlayHis[0]["distance"] > $_jsonData["distance"]) {
            $_compare_last = -1;
        }

        //修复前端不计算摇跑指数bug
        if (!empty($_jsonData["exponent_denominator"])) {
            $_jsonData["exponent"] = round($_jsonData["exponent_molecular"] / ($_jsonData["exponent_denominator"] / 60), 2);
        } else {
            $_jsonData["exponent"] = 0;
        }
        $_jsonData['is_quartets'] = $_jsonData['is_quartets'] ?? 0;
        $_jsonData['sys_match_id'] = $_jsonData['sys_match_id'] ?? 0;
        $_jsonData['sys_sys_match_id'] = $_jsonData['sys_sys_match_id'] ?? 0;

//        存储用户运动数据 is_abnormal
        $_arrOfUserPlayData = array(
            "status" => 1,
            "duration" => $_jsonData["duration"],
            "speed_max" => $_jsonData["speed_max"],
            "circle_count" => $_jsonData["circle_count"],
            "endurance_max" => $_jsonData["endurance_max"],
            "compare_last" => $_compare_last,
            "start_time" => $_jsonData["start_time"],
            "stop_time" => $_jsonData["stop_time"],
            "distance" => $_jsonData["distance"],
            "user_id" => $_jsonData["created_uid"],
            "is_abnormal" => $_jsonData["is_abnormal"],
            "exponent_molecular" => $_jsonData["exponent_molecular"] ?? 0,//摇跑指数，分子
            "exponent_denominator" => $_jsonData["exponent_denominator"] ?? 0,//摇跑指数，分母
            "exponent" => $_jsonData["exponent"] ?? 0,//摇跑指数
            "marathon" => $_jsonData["marathon"] ?? 0,//摇跑马拉松（全马）
            "created_time" => time(),
            "updated_time" => time(),
        );
//dd($_arrOfUserPlayData);

        if (isset($_jsonData["user_pk_list_id"])) {
            $_arrOfUserPlayData["user_pk_list_id"] = $_jsonData["user_pk_list_id"];
        }
        if (isset($_jsonData["matchs_stage_id"])) {
            $_arrOfUserPlayData["matchs_stage_id"] = $_jsonData["matchs_stage_id"];
        }
        if (isset($_jsonData["sys_shake_id"])) {
            $_arrOfUserPlayData["sys_shake_id"] = $_jsonData["sys_shake_id"];
        }

//        查询当前运动是否存在
        $_arrOfUserPlay = UserPlay::where(["user_play_id" => $_jsonData["user_play_id"]])->select("user_id")->get();
//        修改
        if (count($_arrOfUserPlay) == 1) {
            UserPlay::where(["user_play_id" => $_jsonData["user_play_id"]])->update($_arrOfUserPlayData);
            $_arrOfUserPlayData["user_play_id"] = $_jsonData["user_play_id"];
        } else {
//            创建
            $_arrOfUserPlayData["user_play_id"] = $_jsonData["user_play_id"];
            UserPlay::create($_arrOfUserPlayData);
        }


//        获取区间数组
        $_arr_section = array();
        $_speed_section = StaticDataController::$_speed_section;
        for ($_i = 0; $_i < count($_speed_section); $_i++) {
            if ($_i > 0) {
                $_key = $_speed_section[$_i - 1] . "-" . $_speed_section[$_i];
                $_arr_section[$_key] = array(
                    "start_section" => $_speed_section[$_i - 1],
                    "stop_section" => $_speed_section[$_i],
                    "speed_detail" => array(),
                    "section_duration" => 0
                );
            }
        }

//        转速时刻
        $_user_play_detail = array();
//        循环圈数数组，
        for ($_i = 0; $_i < count($_jsonData["speed_detail"]); $_i++) {
//            当前速度 rpm  （当前圈数-上一秒圈数）*60秒*(1000 / 时间间隔 毫秒)，
            $_speed = $_jsonData["speed_detail"][$_i];

            $_moment = $_jsonData["start_time"] * 1000 + $_jsonData["interval"] * $_i;
            array_push($_user_play_detail, array(
                "moment" => $_moment,
                "speed" => $_speed,
            ));

            foreach ($_arr_section as $key => $node) {
                if ($_speed >= $node["start_section"] && $_speed < $node["stop_section"]) {
                    array_push($node["speed_detail"], $_speed);
                }
                $_arr_section[$key] = $node;
            }
        }


        $_max_section_duration = 0;
        foreach ($_arr_section as $key => $value) {
            $value["section_duration"] = round(count($value["speed_detail"]) * $_jsonData["interval"] / 1000);
            unset($value["speed_detail"]);
            $_arr_section[$key] = $value;
            if ($_max_section_duration < $value["section_duration"]) {
                $_max_section_duration = $value["section_duration"];
            }
        }

        foreach ($_arr_section as $key => $value) {
            $_percentage = $_max_section_duration > 0 ? round($value["section_duration"] / $_max_section_duration * 100) : $_max_section_duration;
            $value["percentage"] = $_percentage;
            $_arr_section[$key] = $value;
        }


//        运动详情数据

        $_arrOfUserPlayDetailData = array(
            "status" => 1,
            "speed_interval" => $_jsonData["interval"],
            "user_play_id" => $_jsonData["user_play_id"],
            "section_duration" => json_encode($_arr_section),
            "speed_detail" => json_encode($_user_play_detail),
            "created_time" => time(),
            "updated_time" => time(),
        );


//        修改
        if (count($_arrOfUserPlay) == 1) {
            UserPlayDetail::where(["user_play_id" => $_jsonData["user_play_id"]])->update($_arrOfUserPlayDetailData);
        } else {
//            创建
            $_arrOfUserPlayDetailData["user_play_detail_id"] = $_objSnowflake->nextId();
            UserPlayDetail::create($_arrOfUserPlayDetailData);
        }

        $_arrOfNewMedal = array();
        $_arrOfNewAchievement = array();

//        正常数据时，判断是否正常
        if ($_jsonData["is_abnormal"] == 0) {
//          用户运动结束后，存储数据，判定是否突破记录
            $_arrOfNewAchievement = UsrUserController::userStopPlayHasNewAchievement($_arrOfUserPlayData, $_user_token, $_language);

//          运动结束后，判定是否获得新徽章
            $_arrOfNewMedal = UsrUserController::userStopPlayHasNewMedal($_arrOfUserPlayData, $_user_token, $_language);
        }

//        摇跑指数相关数据更新，参照 UserController postRunballExponentAdd 方法
        $_abnormal_index = StaticDataController::$_abnormal_index;
        $_arrOfUserPlayData["exponent_molecular"] = $_arrOfUserPlayData["exponent_molecular"] < $_abnormal_index['exponent_molecular'] ? $_arrOfUserPlayData["exponent_molecular"] : 0;
        $_arrOfUserPlayData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"] > $_abnormal_index['exponent_denominator'] ? $_arrOfUserPlayData["exponent_denominator"] : 0;
        $_runball_exponent = $_arrOfUserPlayData["exponent"] < $_abnormal_index['runball_exponent'] ? $_arrOfUserPlayData["exponent"] : 0;
        $_arrOfUserPlayData["marathon"] = $_arrOfUserPlayData["marathon"] > $_abnormal_index['marathon'] ? $_arrOfUserPlayData["marathon"] : 0;
//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_arrOfUserPlayData["user_play_id"]) && $_arrOfUserPlayData["is_abnormal"] == 1) {
            return array(
                "code" => 1,
                "msg" => "success"
            );
        }

        $_arrOfUserAchivement = UserAchievement::where([
            "user_id" => $_arrOfUserPlayData["user_id"]
        ])->select("exponent_molecular", "exponent_denominator", "runball_exponent", "marathon")->get();

        if (count($_arrOfUserAchivement) == 1) {
            $_arrOfUserAchivementData = array();
//            如果摇跑指数，摇跑指数存在更新，（摇跑指数）
            if ($_arrOfUserAchivement[0]["runball_exponent"] <= $_runball_exponent) {
                $_arrOfUserAchivementData["runball_exponent"] = $_runball_exponent;
                $_arrOfUserAchivementData["runball_exponent_time"] = $_arrOfUserPlayData["stop_time"];
            }
//            如果指数分子，分母存在更新，（摇跑一分钟）
            if ($_arrOfUserAchivement[0]["exponent_molecular"] <= $_arrOfUserPlayData["exponent_molecular"]) {
                $_arrOfUserAchivementData["exponent_molecular"] = $_arrOfUserPlayData["exponent_molecular"];
                $_arrOfUserAchivementData["exponent_molecular_time"] = $_arrOfUserPlayData["stop_time"];
            }
//            如果半马拉松，半马拉松存在更新，（半马拉松，完成21.098km花的时间）
            /*if (($_arrOfUserAchivement[0]["exponent_denominator"] == 0 || $_arrOfUserAchivement[0]["exponent_denominator"] == null || $_arrOfUserAchivement[0]["exponent_denominator"] >= $_arrOfUserPlayData["exponent_denominator"]) && $_arrOfUserPlayData["exponent_denominator"] > 0) {
                $_arrOfUserAchivementData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
            }*/
            if (!empty($_arrOfUserAchivement[0]["exponent_denominator"])) {
                if ($_arrOfUserAchivement[0]["exponent_denominator"] > $_arrOfUserPlayData["exponent_denominator"] && !empty($_arrOfUserPlayData["exponent_denominator"])) {
                    $_arrOfUserAchivementData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
                }
            } else {
                if (!empty($_arrOfUserPlayData["exponent_denominator"])) {
                    $_arrOfUserAchivementData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
                }
            }
            if (($_arrOfUserAchivement[0]["exponent_denominator"] == 0 || $_arrOfUserAchivement[0]["exponent_denominator"] == null || $_arrOfUserAchivement[0]["exponent_denominator"] >= $_arrOfUserPlayData["exponent_denominator"]) && $_arrOfUserPlayData["exponent_denominator"] > 0) {
                $_arrOfUserAchivementData["exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
            }
            //如果全马拉松，全马拉松存在更新，（全马拉松，完成42.195km花的时间）
            /*if (($_arrOfUserAchivement[0]["marathon"] == 0 || $_arrOfUserAchivement[0] == null || $_arrOfUserAchivement[0]["marathon"] >= $_arrOfUserPlayData["marathon"]) && $_arrOfUserPlayData["marathon"] > 0) {
                $_arrOfUserAchivementData["marathon"] = $_arrOfUserPlayData["marathon"];
                $_arrOfUserAchivementData["marathon_time"] = $_arrOfUserPlayData["stop_time"];
            }*/
            if (!empty($_arrOfUserAchivement[0]["marathon"])) {
                if ($_arrOfUserAchivement[0]["marathon"] > $_arrOfUserPlayData["marathon"] && !empty($_arrOfUserPlayData["marathon"])) {
                    $_arrOfUserAchivementData["marathon"] = $_arrOfUserPlayData["marathon"];
                    $_arrOfUserAchivementData["marathon_time"] = $_arrOfUserPlayData["stop_time"];
                }
            } else {
                if (!empty($_arrOfUserPlayData["marathon"])) {
                    $_arrOfUserAchivementData["marathon"] = $_arrOfUserPlayData["marathon"];
                    $_arrOfUserAchivementData["marathon_time"] = $_arrOfUserPlayData["stop_time"];
                }
            }

            if (($_arrOfUserAchivement[0]["marathon"] == 0 || $_arrOfUserAchivement[0] == null || $_arrOfUserAchivement[0]["marathon"] >= $_arrOfUserPlayData["marathon"]) && $_arrOfUserPlayData["marathon"] > 0) {
                $_arrOfUserAchivementData["marathon"] = $_arrOfUserPlayData["marathon"];
                $_arrOfUserAchivementData["marathon_time"] = $_arrOfUserPlayData["stop_time"];
            }

//            如果存在更新内容
            if (count($_arrOfUserAchivementData) > 0) {
                UserAchievement::where(["user_id" => $_arrOfUserPlayData["user_id"]])->update($_arrOfUserAchivementData);
            }
        }

        //锦标赛事-四项赛事
        if ($_jsonData['is_quartets'] == 1) {
            $_arrOfMatchsUserGrade = MatchsUserGrade::where([
                "matchs_stage.sys_match_id" => $_jsonData['sys_match_id'],
                "matchs_stage.sys_sys_match_id" => $_jsonData['sys_sys_match_id'],
                "matchs_user_grade.is_quartets" => 1,
                "user_id" => $_arrOfUserPlayData["user_id"]
            ])
                ->join("matchs_stage", function ($join) {
                    $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
                })
                ->select("matchs_user_grade.matchs_user_grade_id", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_exponent_denominator", "matchs_user_grade.s_runball_exponent", "matchs_user_grade.s_marathon", "s_duration", "s_speed_max", "s_circle_count", "s_endurance_max", "s_play_count")->get();

            if (count($_arrOfMatchsUserGrade) == 1) {
                $_arrOfMatchsUserGradeData = array();
//            如果摇跑指数，摇跑指数存在更新，（摇跑指数）
                if ($_arrOfMatchsUserGrade[0]["s_runball_exponent"] <= $_runball_exponent) {
                    $_arrOfMatchsUserGradeData["s_runball_exponent"] = $_runball_exponent;
                    $_arrOfMatchsUserGradeData["s_runball_exponent_time"] = $_arrOfUserPlayData["stop_time"];
                }
//            如果指数分子，分母存在更新，（摇跑一分钟）
                if ($_arrOfMatchsUserGrade[0]["s_exponent_molecular"] <= $_arrOfUserPlayData["exponent_molecular"]) {
                    $_arrOfMatchsUserGradeData["s_exponent_molecular"] = $_arrOfUserPlayData["exponent_molecular"];
                    $_arrOfMatchsUserGradeData["s_exponent_molecular_time"] = $_arrOfUserPlayData["stop_time"];
                }
//            如果半马拉松，半马拉松存在更新，（半马拉松，完成21.098km花的时间）
                /*if (($_arrOfMatchsUserGrade[0]["s_exponent_denominator"] == 0 || $_arrOfMatchsUserGrade[0]["s_exponent_denominator"] == null || $_arrOfMatchsUserGrade[0]["s_exponent_denominator"] >= $_arrOfUserPlayData["exponent_denominator"]) && $_arrOfUserPlayData["exponent_denominator"] > 0) {
                    $_arrOfMatchsUserGradeData["s_exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
                }*/
                if (!empty($_arrOfMatchsUserGrade[0]["s_exponent_denominator"])) {
                    if ($_arrOfMatchsUserGrade[0]["s_exponent_denominator"] > $_arrOfUserPlayData["exponent_denominator"] && !empty($_arrOfUserPlayData["exponent_denominator"])) {
                        $_arrOfMatchsUserGradeData["s_exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
                    }
                } else {
                    if (!empty($_arrOfUserPlayData["exponent_denominator"])) {
                        $_arrOfMatchsUserGradeData["s_exponent_denominator"] = $_arrOfUserPlayData["exponent_denominator"];
                    }
                }
                //如果全马拉松，全马拉松存在更新，（全马拉松，完成42.195km花的时间）
                /*if (($_arrOfMatchsUserGrade[0]["s_marathon"] == 0 || $_arrOfMatchsUserGrade[0]["s_marathon"] == null || $_arrOfMatchsUserGrade[0]["s_marathon"] >= $_arrOfUserPlayData["marathon"]) && $_arrOfUserPlayData["marathon"] > 0) {
                    $_arrOfMatchsUserGradeData["s_marathon"] = $_arrOfUserPlayData["marathon"];
                    $_arrOfMatchsUserGradeData["s_marathon_time"] = $_arrOfUserPlayData["stop_time"];
                }*/
                if (!empty($_arrOfMatchsUserGrade[0]["s_marathon"])) {
                    if ($_arrOfMatchsUserGrade[0]["s_marathon"] > $_arrOfUserPlayData["marathon"] && !empty($_arrOfUserPlayData["marathon"])) {
                        $_arrOfMatchsUserGradeData["s_marathon"] = $_arrOfUserPlayData["marathon"];
                        $_arrOfMatchsUserGradeData["s_marathon_time"] = $_arrOfUserPlayData["stop_time"];
                    }
                } else {
                    if (!empty($_arrOfUserPlayData["marathon"])) {
                        $_arrOfMatchsUserGradeData["s_marathon"] = $_arrOfUserPlayData["marathon"];
                        $_arrOfMatchsUserGradeData["s_marathon_time"] = $_arrOfUserPlayData["stop_time"];
                    }
                }
                //持续时间 s
                if ($_arrOfMatchsUserGrade[0]["s_duration"] == 0 || $_arrOfMatchsUserGrade[0]["s_duration"] == null || $_arrOfMatchsUserGrade[0]["s_duration"] < $_arrOfUserPlayData["duration"]) {
                    $_arrOfMatchsUserGradeData["s_duration"] = $_arrOfUserPlayData["duration"];
                }
                //最高转速 rpm
                if ($_arrOfMatchsUserGrade[0]["s_speed_max"] == 0 || $_arrOfMatchsUserGrade[0]["s_speed_max"] == null || $_arrOfMatchsUserGrade[0]["s_speed_max"] < $_arrOfUserPlayData["speed_max"]) {
                    $_arrOfMatchsUserGradeData["s_speed_max"] = $_arrOfUserPlayData["speed_max"];
                    $_arrOfMatchsUserGradeData["s_speed_max_time"] = $_arrOfUserPlayData["stop_time"];
                }
                //圈数  rp
                if ($_arrOfMatchsUserGrade[0]["s_circle_count"] == 0 || $_arrOfMatchsUserGrade[0]["s_circle_count"] == null || $_arrOfMatchsUserGrade[0]["s_circle_count"] < $_arrOfUserPlayData["circle_count"]) {
                    $_arrOfMatchsUserGradeData["s_circle_count"] = $_arrOfUserPlayData["circle_count"];
                }
                //耐力 转速超过1万 rpm 的秒数
                if ($_arrOfMatchsUserGrade[0]["s_endurance_max"] == 0 || $_arrOfMatchsUserGrade[0]["s_endurance_max"] == null || $_arrOfMatchsUserGrade[0]["s_endurance_max"] < $_arrOfUserPlayData["endurance_max"]) {
                    $_arrOfMatchsUserGradeData["s_endurance_max"] = $_arrOfUserPlayData["endurance_max"];
                }
                //累计运动次数 次
                $_arrOfMatchsUserGradeData["s_play_count"] = intval($_arrOfMatchsUserGrade[0]["s_play_count"]) + 1;
                /*                //最高运动米数
                                if ($_arrOfMatchsUserGrade[0]["s_distance_max"] == 0 || $_arrOfMatchsUserGrade[0]["s_distance_max"] == null || $_arrOfMatchsUserGrade[0]["s_distance_max"] < $_arrOfUserPlayData["distance_max"]){
                                    $_arrOfMatchsUserGradeData["s_distance_max"] = $_arrOfUserPlayData["distance_max"];
                                }
                                //摇跑三分钟距离
                                if ($_arrOfMatchsUserGrade[0]["s_thrmin"] == 0 || $_arrOfMatchsUserGrade[0]["s_thrmin"] == null || $_arrOfMatchsUserGrade[0]["s_thrmin"] < $_arrOfUserPlayData["thrmin"]){
                                    $_arrOfMatchsUserGradeData["s_thrmin"] = $_arrOfUserPlayData["thrmin"];
                                }
                                //摇跑半马拉松用时 （秒）
                                if (($_arrOfMatchsUserGrade[0]["s_half_marathon"] == 0 || $_arrOfMatchsUserGrade[0]["s_half_marathon"] == null || $_arrOfMatchsUserGrade[0]["s_half_marathon"] > $_arrOfUserPlayData["half_marathon"]) && $_arrOfUserPlayData["half_marathon"] > 0){
                                    $_arrOfMatchsUserGradeData["s_half_marathon"] = $_arrOfUserPlayData["half_marathon"];
                                }*/

//            如果存在更新内容
                if (count($_arrOfMatchsUserGradeData) > 0) {
                    MatchsUserGrade::where([
                        "user_id" => $_arrOfUserPlayData["user_id"],
                        "matchs_user_grade_id" => $_arrOfMatchsUserGrade[0]['matchs_user_grade_id'],
                    ])->update($_arrOfMatchsUserGradeData);
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "new_medal" => $_arrOfNewMedal,
                "new_achievement" => $_arrOfNewAchievement,
                "matchs_user_grade" => $_arrOfMatchsUserGradeData ?? [],
            )
        );

    }


    /**
     * @abstract 本地运动数据上传V3==新版赛事（优化版+队列）
     * @param PostUploadLocalPlayV3Request $request
     * @param LocalPlayService $service
     * @return JsonResponse
     * @throws BusinessException
     */
    public function postUploadLocalPlayV3(PostUploadLocalPlayV3Request $request, LocalPlayService $service): JsonResponse
    {
        $data = $request->all();
        $map = [];
        $_token = $request->header('token');
        if (empty($_token)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.lack_token'));

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);
        $map['user_id'] = $_usr_user['user_id'];
        $map['status'] = 1;

        //生成本次运动ID
//        $_objSnowflake = new Snowflake(StaticDataController::$_workId);
//        $map["user_play_id"] = $data["user_play_id"] = $_objSnowflake->nextId();
//        $data['user_play_detail_id'] = $_objSnowflake->nextId();

        if (!isset($data["user_play_id"])){
            $_objSnowflake = new SnowFlakeSwooles(StaticDataController::$_workId);
            $map["user_play_id"] = $data["user_play_id"] = $_objSnowflake->getId();
        }else{
            $map["user_play_id"] = $data["user_play_id"];
        }
        if (!isset($data['user_play_detail_id'])){
            $_objSnowflake = new SnowFlakeSwooles(StaticDataController::$_workId);
            $data['user_play_detail_id'] = $_objSnowflake->getId();
        }

        $map['is_quartets'] = $data['is_quartets'];

        if (empty($data['source'])){//补充老版本没source的bug
            if (!empty($data['matchs_stage_id'])){
                $data['source'] = 4;
            }elseif (!empty($data['user_pk_list_id'])){
                $data['source'] = 3;
            }elseif (!empty($data['sys_shake_id'])){
                $data['source'] = 2;
            }else{
                $data['source'] = 1;
            }
        }

        $map['source'] = $data['source'];

        $data['speed_detail'] = json_decode($data['speed_detail'], true);

        //提交参数验证
        $map['json_data'] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        //写入队列记录
        $list = $service->postplaylog($map);
        $this->dispatch(new DataUploadQueue($list));//进入队列

//       $handlePlayLog = $service->handlePlayLog($list);//直接数据操作

        if ($list) {
            return $this->success(true, 'success', ErrorCode::SEVER_SUCCESS);
        } else {
            return $this->error(ErrorCode::SEVER_SUCCESS, 'success', false);
        }
    }

}
