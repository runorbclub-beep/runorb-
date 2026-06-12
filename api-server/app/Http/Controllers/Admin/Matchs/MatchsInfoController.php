<?php


namespace App\Http\Controllers\Admin\Matchs;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\UserGroupAssociated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MatchsInfoController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/26 10:21
     * @abstract _查询赛事用户列表
     */
    public function MatchsStageUserList()
    {

        $_data = request()->input();

        $_token_key = "admin_user_token:" . request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["is_group"]) || !isset($_data["sys_match_id"]) || !isset($_data["matchs_stage_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;


//        查询当前赛段下的用户信息
        $_arrOfMatchUserQuery = MatchsUser::where([
            "matchs_user.status" => 1,
            "matchs_user_grade.matchs_stage_id" => $_data["matchs_stage_id"],
        ])->join("matchs_user_grade", function ($join) {
            $join->on("matchs_user.matchs_user_id", "=", "matchs_user_grade.matchs_user_id");
        });
//            ->where('matchs_user.user_group_finish_time', '!=', null);

//        团队
        if ($_data["is_group"] == 1) {
            $_arrOfMatchUserQuery = $_arrOfMatchUserQuery->join("user_group", function ($join) {
                $join->on("matchs_user.user_group_id", "=", "user_group.user_group_id");
            })->select(
                "matchs_user.matchs_user_id", "user_group.group_title as name", "user_group.group_logo as img"
                , "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "matchs_user.is_group", "matchs_user_grade.matchs_stage_id"
                , "user_group.user_group_id", "matchs_user.user_group_finish_time", "matchs_user.team_tag"
            );

        } else {
//            个人
            $_arrOfMatchUserQuery = $_arrOfMatchUserQuery->join("usr_user", function ($join) {
                $join->on("matchs_user.user_id", "=", "usr_user.user_id");
            })->select(
                "matchs_user.matchs_user_id", "usr_user.user_name as name", "usr_user.user_img as img"
                , "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "matchs_user.is_group", "matchs_user_grade.matchs_stage_id"
                , "usr_user.user_id", "matchs_user.user_group_finish_time", "matchs_user.team_tag"
            );
        }

        $_arrOfMatchUserCount = $_arrOfMatchUserQuery->count();
        $_arrOfMatchUser = $_arrOfMatchUserQuery->orderBy("matchs_user_grade.match_ranking","ASC")->skip($_offset)->take($_limit)->get();
//        $_arrOfMatchUser = $_arrOfMatchUserQuery
//            ->orderBy("matchs_user_grade.match_grade", "ASC")
//            ->orderBy("matchs_user.user_group_finish_time", "DESC")
//            ->skip($_offset)->take($_limit)->get();


        foreach ($_arrOfMatchUser as $key => $value) {
            $value["img"] = StaticDataController::$_server_url . "/" . $value["img"];
            $value['match_ranking'] = $_offset + $key + 1;
            $_arrOfMatchUser[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchUserCount,
                "list" => $_arrOfMatchUser
            )
        );

    }


    /**
     * @author pengjl
     * @time 2021/5/26 14:04
     * @abstract _赛段下用户/团队用时
     */
    public function MatchsStageUserPlayList()
    {
        $_data = request()->input();

        $_token_key = "admin_user_token:" . request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["is_group"]) || !isset($_data["matchs_user_id"]) || !isset($_data["matchs_stage_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_is_group = $_data["is_group"];

        $_arrOfUserId = array();
        $_user_id = "";

        if ($_is_group == 1) {
            $_arrOfGroupUser = UserGroupAssociated::where([
                "user_group_associated.status" => 1,
                "matchs_user.status" => 1,
                "matchs_user.matchs_user_id" => $_data["matchs_user_id"],
            ])->join("matchs_user", function ($join) {
                $join->on("matchs_user.user_group_id", "=", "user_group_associated.user_group_id");
            })->select("user_group_associated.user_id")->get();

            foreach ($_arrOfGroupUser as $value) {
                array_push($_arrOfUserId, $value["user_id"]);
            }
        } else {
            if (!isset($_data["user_id"])) {
                return SystemErrorController::paramtersError($_language);
            }

            $_user_id = $_data["user_id"];
        }

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;


        $_arrOfMatchsUserPlayQuery = MatchsStage::where([
            "matchs_stage.status" => 1,
            "matchs_stage.matchs_stage_id" => $_data["matchs_stage_id"],
        ])->join("user_play", function ($join) {
            $join->on("matchs_stage.matchs_stage_id", "=", "user_play.matchs_stage_id");
        })->join("usr_user", function ($join) {
            $join->on("user_play.user_id", "=", "usr_user.user_id");
        });

        if ($_is_group == 1) {
            $_arrOfMatchsUserPlayQuery = $_arrOfMatchsUserPlayQuery->whereIn("user_play.user_id", $_arrOfUserId);
        } else {
            $_arrOfMatchsUserPlayQuery = $_arrOfMatchsUserPlayQuery->where(["user_play.user_id", "=", $_user_id]);
        }

        $_arrOfMatchsUserPlayQuery = $_arrOfMatchsUserPlayQuery->select(
            "usr_user.user_name", "usr_user.user_img", "user_play.user_play_id", "user_play.duration"
            , "user_play.speed_max", "user_play.circle_count", "user_play.distance", "user_play.start_time", "user_play.stop_time"
            , "user_play.is_abnormal"
        )->orderBy("user_play.start_time", "DESC");

        $_arrOfMatchsUserPlayCount = $_arrOfMatchsUserPlayQuery->count();
        $_arrOfMatchsUserPlay = $_arrOfMatchsUserPlayQuery->skip($_offset)->take($_limit)->get();


        foreach ($_arrOfMatchsUserPlay as $value) {
            $value["distance_format"] = round($value["distance"] / 1000, 3);
            $value["start_time"] = date("Y-m-d H:i:s", $value["start_time"]);
            $value["stop_time"] = date("Y-m-d H:i:s", $value["stop_time"]);
            $value["user_img"] = event("SERVER_URL") . "/" . $value["user_img"];
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchsUserPlayCount,
                "list" => $_arrOfMatchsUserPlay
            )
        );
    }



}
