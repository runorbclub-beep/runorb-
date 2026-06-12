<?php

namespace App\Console\Commands;

use App\Console\Commands\CrontabController\MatchStatusChange;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\SysMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class runball_crontab extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:Runball';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计划任务命令';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        self::runballMatchsStatusChange();

        return true;
    }


    /**
     * @author pengjl
     * @time 2021/5/25 13:43
     * @abstract _计划任务，更新赛事，赛段状态
     */
    public static function runballMatchsStatusChange()
    {
        // Log::info("执行赛事定时任务--------------------------");

//        查询所有未结束的赛事，更新状态
        $_arrOfSysMatch = SysMatch::where([
            "status" => 1,
        ])->whereNull("sys_sys_match_id")->where("match_status", "<", 3)
            ->select("sys_match_id", "match_start_time", "match_stop_time", "match_status")->get();


        foreach ($_arrOfSysMatch as $value) {
//            赛事未开始，且状态值不是未开始状态
            if ($value["match_start_time"] >= time() && $value["match_status"] != 1) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 1]);
            }

//            赛事已开始，且状态不是已开始状态
            if ($value["match_start_time"] <= time() && $value["match_stop_time"] > time() && $value["match_status"] != 2) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 2]);
            }

//            赛事已开始，且状态不是已结束状态
            if ($value["match_stop_time"] <= time() && $value["match_status"] != 3) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 3]);
            }
        }

//        查询所有赛选
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->where("matchs_stage_status", "<", 3)->select(
            "matchs_stage_id", "matchs_stage_status", "match_stage_start_time", "match_stage_stop_time", "sys_match_id", "sys_sys_match_id"
            , "match_promotion_type", "match_promotion_value"
        )->get();

        $_arr = array();

        foreach ($_arrOfMatchStage as $value) {
//            赛段未开始，且状态值不是未开始状态
            if ($value["match_stage_start_time"] >= time() && $value["matchs_stage_status"] != 1) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 1]);
            }

//            赛段已开始，且状态不是已开始状态
            if ($value["match_stage_start_time"] <= time() && $value["match_stage_stop_time"] > time() && $value["matchs_stage_status"] != 2) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 2]);
            }

//            赛段已开始，且状态不是已结束状态
            if ($value["match_stage_stop_time"] <= time() && $value["matchs_stage_status"] != 3) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 3]);

//                赛段结束，计算排名，晋级
                $_status_data = self::MatchStageStop($value["sys_match_id"], $value["matchs_stage_id"], $value["sys_sys_match_id"], $value["match_promotion_type"], $value["match_promotion_value"]);
                array_push($_arr, $_status_data);
            }
        }

        return $_arr;
    }


    /**
     * @author pengjl
     * @time 2021/5/21 20:07
     * @abstract _
     */
    public static function MatchStageStop($_sys_match_id, $_matchs_stage_id, $_sys_sys_match_id, $_match_promotion_type, $_match_promotion_value)
    {
        // Log::info("赛事状态执行变更");

//        查询赛段最终成绩
        $_arrOfMatchUser = MatchsStage::where([
            "matchs_stage.matchs_stage_id" => $_matchs_stage_id,
            "matchs_stage.sys_match_id" => $_sys_match_id,
            "matchs_stage.sys_sys_match_id" => $_sys_sys_match_id
        ])->join("matchs_user", function ($join) {
            $join->on("matchs_user.sys_match_id", "=", "matchs_stage.sys_match_id");
        })->select(
            "matchs_user.user_group_finish_time", "matchs_user.user_group_name", "matchs_user.user_group_id"
            , "matchs_user.user_id"
            , "matchs_stage.match_stage_distance", "matchs_user.matchs_user_id", "matchs_stage.is_exponent"
        )->get();

        $_arrOfMatchUserKey = array();
        foreach ($_arrOfMatchUser as $value) {
            $_arrOfMatchUserKey[$value["matchs_user_id"]] = $value;
        }

        $_arrOfMatchUserGradeValue = MatchsUserGrade::where([
            "matchs_stage_id" => $_matchs_stage_id,
        ])->whereIn("matchs_user_id", array_keys($_arrOfMatchUserKey))->select(
            "matchs_user_id", "matchs_user_grade_id", "is_group", "distinct_grade"
        )->get();

        foreach ($_arrOfMatchUserGradeValue as $value) {
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["matchs_user_grade_id"] = $value["matchs_user_grade_id"];
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["is_group"] = $value["is_group"];
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["distinct_grade"] = $value["distinct_grade"];
        }

        $_arrOfMatchUserGrade = array_values($_arrOfMatchUserKey);

//        如果当前赛事没有用户报名，直接结束
        if (count($_arrOfMatchUserGrade) == 0) {
            return array();
        }

        $_is_exponent = $_arrOfMatchUserGrade[0]['is_exponent'] ?? 0;   //是否为展示摇跑指数

        if ($_is_exponent != 1) {   //非摇跑指数的重新排名，原因不清楚
            $_order_by_type = 'ASC';
            Redis::select(14);
            foreach ($_arrOfMatchUserGrade as $value) {

                if ($value["is_group"] == 1) {
                    $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $value["user_group_id"];
                } else {
                    $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $value["user_id"];
                }

                $_matchsPlayList = Redis::lrange($_redis_key, 0, -1);


                $_distance = 0;
                if (count($_matchsPlayList) > 0) {
                    foreach ($_matchsPlayList as $node) {
                        $_playData = json_decode(Redis::get($node), true);

                        if ($value["user_group_finish_time"] == null) {
                            $_distance += isset($_playData["duration"]) ? $_playData["duration"] : 0;
                        } else {

//                    在结束前的运动数据
                            if (isset($_playData["stop_time"]) && $_playData["stop_time"] <= $value["user_group_finish_time"]) {
                                $_distance += isset($_playData["duration"]) ? $_playData["duration"] : 0;
                            }

//                    结束后运动的数据，取开始到结束间的时间
                            if (isset($_playData["stop_time"]) && $_playData["stop_time"] > $value["user_group_finish_time"]) {
                                $_dd = isset($_playData["start_time"]) ? $value["user_group_finish_time"] - $_playData["start_time"] : 0;
                                $_distance += $_dd;
                            }
                        }

                    }
                }

//            更新用户成绩
                $_MatchsUserGradeQuery = MatchsUserGrade::where([
                    "matchs_stage_id" => $_matchs_stage_id,
                    "matchs_user_id" => $value["matchs_user_id"]
                ]);

                if ($value["is_group"] == 1) {
                    $_MatchsUserGradeQuery = $_MatchsUserGradeQuery->where([
                        "user_group_id" => $value["user_group_id"],
                    ]);

                } else {
                    $_MatchsUserGradeQuery = $_MatchsUserGradeQuery->where([
                        "user_id" => $value["user_id"],
                    ]);
                }

                $_MatchsUserGradeQuery->update(["match_grade" => $_distance]);

            }
        } else {
            $_order_by_type = 'DESC';
        }

//        区分已完成比赛和未完成比赛的用户
        $_finish_group = array();
        $_unfinish_group = array();
        foreach ($_arrOfMatchUserGrade as $value) {
            if ($value["user_group_finish_time"] != null) {
                array_push($_finish_group, $value["matchs_user_id"]);
            }

            if ($value["user_group_finish_time"] == null) {
                array_push($_unfinish_group, $value["matchs_user_id"]);
            }
        }

//        已完成比赛的用户排名
        $_arrOfMatchUserGradeFinish = MatchsUserGrade::where([
            "matchs_stage_id" => $_matchs_stage_id
        ])->whereIn("matchs_user_id", $_finish_group)->select(
            "matchs_user_grade_id", "match_grade", "user_id", "user_group_id", "matchs_user_id", "is_group"
        )->orderBy("match_grade", "ASC")->get();


        $_arrOfMatchUserGradeKey = array();
//        变更赛段最终排名
        foreach ($_arrOfMatchUserGradeFinish as $key => $value) {
            MatchsUserGrade::where([
                "matchs_user_grade_id" => $value["matchs_user_grade_id"]
            ])->update([
                "match_ranking" => $key + 1
            ]);

            $_arrOfMatchUserGradeKey[$value["matchs_user_id"]] = $value;
        }


        if ($_is_exponent != 1) { //非摇跑指数
            //未完成比赛用户排名
            if (count($_unfinish_group) > 0) {
                //        查询赛段最终成绩
                $_arrOfMatchUserGradeUnFinish = MatchsUserGrade::where([
                    "matchs_stage_id" => $_matchs_stage_id
                ])->whereIn("matchs_user_id", $_unfinish_group)->select(
                    "matchs_user_grade_id", "distinct_grade", "user_id", "user_group_id", "matchs_user_id", "is_group"
                )->orderBy("distinct_grade", $_order_by_type)->get();

//        变更赛段最终排名
                foreach ($_arrOfMatchUserGradeUnFinish as $key => $value) {
                    MatchsUserGrade::where([
                        "matchs_user_grade_id" => $value["matchs_user_grade_id"]
                    ])->update([
                        "match_ranking" => $key + 1 + count($_finish_group)
                    ]);
                }
            }
        }

//        晋级的用户报名ID
        $_arrOfmatchUserId = array();
//        晋级的分界名次
        $_arrOfMatchUserIndex = 0;
//        赛段指定人数晋级
        if ($_match_promotion_type == 0) {
            $_arrOfMatchUserIndex = $_match_promotion_value;
        } else if ($_match_promotion_type == 1) {
//            赛段按比例晋级
            $_arrOfMatchUserIndex = round(count($_arrOfMatchUserGrade) * $_match_promotion_value / 100);
        }


        for ($_i = 0; $_i < $_arrOfMatchUserIndex; $_i++) {
            if ($_i < count($_arrOfMatchUserGradeFinish)) {
                array_push($_arrOfmatchUserId, $_arrOfMatchUserGradeFinish[$_i]["matchs_user_id"]);
            }
        }


//        禁止所有用户继续下一赛段
        MatchsUser::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->update(["stage_pass" => 0]);

//        允许晋级用户继续参赛，
        MatchsUser::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->whereIn("matchs_user_id", $_arrOfmatchUserId)->update(["stage_pass" => 1, "user_group_finish_time" => null]);


        $_next_stage_id = "";
        //        查询下一赛段
        $_arrOfNextMatchStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->select("matchs_stage_id", "match_stage_start_time")->orderBy("match_stage_start_time", "ASC")->get();

        $_arrOfNextMatchStageKey = array();
        $_arrOfNextMatchStageTime = array();
        $_this_index = 0;
        foreach ($_arrOfNextMatchStage as $key => $value) {
            array_push($_arrOfNextMatchStageTime, $value["match_stage_start_time"]);
            $_arrOfNextMatchStageKey[$value["match_stage_start_time"]] = $value;

            if ($_matchs_stage_id == $value["matchs_stage_id"]) {
                $_this_index = $key + 1;
            }
        }

        if ($_this_index >= count($_arrOfNextMatchStageKey)) {
            return array(
                "code" => 1,
                "msg" => "没有下一赛段"
            );
        }

        $_next_stage_id = $_arrOfNextMatchStageKey[$_arrOfNextMatchStageTime[$_this_index]]["matchs_stage_id"];

//        为用户创建下一赛段成绩
        $_sno = new Snowflake(StaticDataController::$_workId);
        foreach ($_arrOfmatchUserId as $value) {
            $_arrOfMatchsStageGradeData = array(
                "matchs_user_grade_id" => $_sno->nextId(),
                "matchs_stage_id" => $_next_stage_id,
                "matchs_user_id" => $value,
                "match_grade" => 9999999999,
                "match_ranking" => 0,
                "user_id" => $_arrOfMatchUserGradeKey[$value]["user_id"],
                "is_group" => $_arrOfMatchUserGradeKey[$value]["is_group"],
                "user_group_id" => $_arrOfMatchUserGradeKey[$value]["user_group_id"],
            );

            MatchsUserGrade::create($_arrOfMatchsStageGradeData);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfmatchUserId
        );
    }


}
