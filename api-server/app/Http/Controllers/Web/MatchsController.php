<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\MatchsEventType;
use App\Models\MatchsStage;
use App\Models\MatchsUserGrade;
use App\Models\SysMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * @author pengjl
 * @time 2021/5/9 17:33
 * Class MatchsController
 * @package App\Http\Controllers\Web
 * @abstract _官网赛事相关接口
 */
class MatchsController extends Controller
{


    /**
     * @abstract 查询系统比赛项目列表
     * @param Request $request
     * @return array
     */
    public function matchEventList(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

//        列表
        $_arrOfMatchEventQuery = MatchsEventType::where([
            "status" => 1,
        ]);

//        if($_language=="zh-CN"){
//            $_arrOfMatchEventQuery = $_arrOfMatchEventQuery->select("matchs_event_type_id as match_event_id","match_events_type_title as match_event_title");
//        }else{
//            $_arrOfMatchEventQuery = $_arrOfMatchEventQuery->select("matchs_event_type_id as match_event_id","match_events_type_title_en as match_event_title");
//        }
        $_arrOfMatchEventQuery = $_arrOfMatchEventQuery->select("matchs_event_type_id as match_event_id", "match_events_type_title_en as match_event_title_en", "match_events_type_title as match_event_title");

        $_arrOfMatchEvent = $_arrOfMatchEventQuery->orderBy("index", "ASC")->get();

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfMatchEvent),
                "list" => $_arrOfMatchEvent
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/28 13:04
     * @abstract _赛事列表
     */
    public function matchList()
    {
        $_data = request()->input();

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';


//        当前已发布，未结束的赛事列表
        $_arrOfMatchListQuery = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->join("matchs_event_type", function ($join) {
            $join->on("sys_match.matchs_event_type_id", "=", "matchs_event_type.matchs_event_type_id");
        });


//        赛事状态
        if (isset($_data["match_status"])) {
            $_arrOfMatchListQuery = $_arrOfMatchListQuery->where([
                "sys_sys_match.match_status" => $_data["match_status"]
            ]);
        }

//        项目类型
        if (isset($_data["match_event_id"]) && $_data["match_event_id"] != "") {
            $_arrOfMatchListQuery = $_arrOfMatchListQuery->where([
                "sys_match.matchs_event_type_id" => $_data["match_event_id"]
            ]);
        }

        $_arrOfMatchListQuery = $_arrOfMatchListQuery->select(
            "sys_match.sys_match_id", "sys_match.match_champion_prize_description", "sys_match.match_champion_prize_description_en", "sys_sys_match.match_title", "sys_sys_match.match_title_en"
            , "sys_sys_match.match_start_time"
            , "sys_sys_match.match_stop_time", "sys_match.status", "sys_sys_match.match_user_sign_count", "sys_sys_match.match_status"
            , "sys_sys_match.is_group", "sys_sys_match.sys_match_id as sys_sys_match_id", "sys_sys_match.match_image"
            , "matchs_event_type.match_events_type_title", "matchs_event_type.match_events_type_title_en"
        )->orderBy("sys_sys_match.match_start_time", "DESC");

        $_arrOfMatchListCount = $_arrOfMatchListQuery->count();
        $_arrOfMatchList = $_arrOfMatchListQuery->skip($_offset)->take($_limit)->get();


        foreach ($_arrOfMatchList as $key => $value) {
            $value["start_time"] = date("Y.m.d H:i", $value["match_start_time"]);
            $value["stop_time"] = date("Y.m.d H:i", $value["match_stop_time"]);

            switch ($value["match_status"]) {
                case 1:
                    $value["match_status_title"] = "未开始";
                    $value["match_status_title_en"] = "Soon";
                    break;
                case 2:
                    $value["match_status_title"] = "比赛中";
                    $value["match_status_title_en"] = "Runing";
                    break;
                case 3:
                    $value["match_status_title"] = "已结束";
                    $value["match_status_title_en"] = "End";
                    break;
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchListCount,
                "list" => $_arrOfMatchList
            )
        );
    }

    /**
     * @abstract 查询赛事详情
     * @param Request $request
     * @return array
     */
    public function matchInfo(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data['sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }


        $_arrOfSysMatch = SysMatch::where([
            "sys_match.status" => 1,
            "sys_match_node.sys_match_id" => $_data['sys_match_id']
        ])->join("matchs_type", function ($join) {
            $join->on("sys_match.matchs_type_id", "=", "matchs_type.matchs_type_id");
        })->join("sys_match as sys_match_node", function ($join) {
            $join->on("sys_match_node.sys_sys_match_id", "=", "sys_match.sys_match_id");
        })->select(
            "sys_match.sys_match_id as sys_sys_match_id", "sys_match.match_user_sex", "sys_match.match_user_type", "sys_match.match_start_time", "sys_match.match_stop_time"
            , "sys_match.match_phone", "sys_match.match_email", "sys_match.match_description", "sys_match.match_description_en", "sys_match.match_title", "sys_match.match_title_en"
            , "sys_match.matchs_type_id", "sys_match.match_phone_prefix", "sys_match.match_image", "matchs_type.matchs_type_title", "sys_match.match_user_type_description"
            , "sys_match.match_user_sex_description", "sys_match_node.match_champion_prize_description", "sys_match_node.matchs_event_type_id"
            , "sys_match_node.sys_match_id", "sys_match.match_user_sign_count", "sys_match.is_group", "sys_match.match_status"
            , "sys_match_node.match_champion_prize_description_en", "sys_match.match_start_time", "sys_match.match_stop_time"
        )->get();


//        找到数据
        if (count($_arrOfSysMatch) == 1) {
            $_arrOfSysMatchInfo = $_arrOfSysMatch[0];

//            用户查询赛事数据
            $_arrOfMatchInfo = self::SysMatchUserInfo($_arrOfSysMatchInfo, $_language, $_data['sys_match_id']);

            $_arrOfMatchStage = MatchsStage::where([
                "status" => 1,
                "sys_match_id" => $_data['sys_match_id']
            ])->select(
                "matchs_stage_id", "match_stage_title", "match_stage_title_en", "match_stage_start_time", "match_stage_stop_time", "max_integral", "sub_integral", "get_integral_type", "get_integral_value"
                , "match_promotion_type", "match_promotion_value", "sys_sys_match_id", "match_stage_distance", "view_type", "matchs_stage_status"
            )->get();


            $_arrOfMatchInfo["matchs_stage_id"] = "";
            $_arrOfMatchInfo["view_type"] = 0;

            foreach ($_arrOfMatchStage as $key => $value) {
                $value["match_stage_title"] = $_language == "zh-CN" ? $value["match_stage_title"] : $value["match_stage_title_en"];
                unset($value["match_stage_title_en"]);

                $value["this_stage"] = 0;
                $value["start_time"] = date("Y.m.d H:i", $value["match_stage_start_time"]);
                $value["stop_time"] = date("Y.m.d H:i", $value["match_stage_stop_time"]);

                if ($value["match_stage_start_time"] < time() && $value["match_stage_stop_time"] > time()) {
                    $value["this_stage"] = 1;
                    $_arrOfMatchInfo["matchs_stage_id"] = $value["matchs_stage_id"];
                    $_arrOfMatchInfo["view_type"] = $value["view_type"];
                }

                if ($_language == "zh-CN") {
                    $_type = $value["match_promotion_type"] == 0 ? " 人" : "% ";
                    $value["match_stage_promotion_rule"] = "<div style='color:#767779'>" . "前 " . $value["match_promotion_value"] . $_type . " 晋级" . "</div>";
                } else {
                    $_type = $value["match_promotion_type"] == 0 ? " person" : "% ";
                    $value["match_stage_promotion_rule"] = "<div style='color:#767779'>" . "top " . $value["match_promotion_value"] . $_type . " rise" . "</div>";
                }
            }

            $_arrOfMatchInfo["stage"] = $_arrOfMatchStage;


            return array(
                "code" => 1,
                "msg" => "success",
                "data" => $_arrOfMatchInfo
            );

        } else {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_not_fount"),
                "data" => array()
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/5/22 17:13
     * @abstract _查询赛事下的赛段
     */
    public function matchStage(Request $request)
    {

        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["sys_match_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfMatchsStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_data["sys_match_id"]
        ])->select(
            "matchs_stage_id", "match_stage_title", "match_stage_start_time", "match_stage_stop_time"
        )->orderBy("match_stage_start_time", "ASC")->get();

        foreach ($_arrOfMatchsStage as $value) {
            $value["start_time"] = date("Y-m-d H:i", $value["match_stage_start_time"]);
            $value["stop_time"] = date("Y-m-d H:i", $value["match_stage_stop_time"]);

            $value["this_stage"] = 0;

            if ($value["match_stage_start_time"] < time() && time() < $value["match_stage_stop_time"]) {
                $value["this_stage"] = 1;
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfMatchsStage),
                "list" => $_arrOfMatchsStage
            )
        );
    }

    /**
     * @abstract 我的赛事
     * @param Request $request
     * @return array
     */
    public function matchStageRanking(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["sys_match_id"]) || !isset($_data["matchs_stage_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_is_group = isset($_data["is_group"]) ? $_data["is_group"] : 0;

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 50;
        $_offset = ($_page - 1) * $_limit;

        $_is_exponent = MatchsStage::where([
            "status" => 1,
            "matchs_stage_id" => $_data["matchs_stage_id"]
        ])->value('is_exponent');
        if ($_is_exponent == 1) {
            $_order_by_type = 'DESC';
            $_unit = '';
        } else {
            $_order_by_type = 'ASC';
            $_unit = 's';
        }

//        $_arrOfMatchsUserGradeQuery = MatchsUserGrade::where([
//            "matchs_user_grade.is_group" => $_is_group,
//            "matchs_stage_id" => $_data["matchs_stage_id"]
//        ])->where("matchs_user_grade.match_grade", '!=', 10000000000)
//            ->where("matchs_user_grade.match_grade", '>', 0);

        $_arrOfMatchsUserGradeQuery = MatchsUserGrade::where([
            "matchs_user_grade.is_group" => $_is_group,
            "matchs_stage_id" => $_data["matchs_stage_id"]
        ])->join("matchs_user", function ($join) use ($_is_group) {
            if ($_is_group == 1) {
                $join->on("matchs_user.user_group_id", "=", "matchs_user_grade.user_group_id");
            } else {
                $join->on("matchs_user.user_id", "=", "matchs_user_grade.user_id");
            }
        })
            ->where("matchs_user_grade.match_grade", '!=', 10000000000)
            ->where("matchs_user_grade.match_grade", '>', 0)
            ->where("matchs_user.sys_match_id", $_data["sys_match_id"])
            ->whereRaw(" matchs_user.stage_pass = 1 OR matchs_user.user_group_finish_time != null ");


        if ($_is_group == 1) {
            $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->join("user_group", function ($join) {
                $join->on("matchs_user_grade.user_group_id", "=", "user_group.user_group_id");
            })->select(
                "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "user_group.group_title as name", "user_group.group_logo as image"
                , "matchs_user_grade.matchs_user_grade_id"
            );

        } else {
            $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->join("usr_user", function ($join) {
                $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
            })->select(
                "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "usr_user.user_name as name", "usr_user.user_img as image"
                , "matchs_user_grade.matchs_user_grade_id"
            );
        }

        $_arrOfMatchsUserGradeCount = $_arrOfMatchsUserGradeQuery->count();
        $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->orderBy("matchs_user_grade.match_ranking", $_order_by_type)->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfMatchsUserGrade as $key => $value) {
            if ($value["image"] == "" || $value["image"] == null) {
                $value["image"] = "wx_sources/default_user.png";
            }

            $value["image"] = StaticDataController::$_server_url . "/" . $value["image"];

            if ($value["match_grade"] == 10000000000) {
                $value["match_grade"] = 0;
            }

            $value['unit'] = $_unit;

            $_arrOfMatchsUserGrade[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchsUserGradeCount,
                "list" => $_arrOfMatchsUserGrade,
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/12 16:56
     * @abstract _用户查询赛事详情
     */
    public static function SysMatchUserInfo($_arrOfSysMatchInfo, $_language, $_sys_match_id)
    {

        $_match_join_pass = $_arrOfSysMatchInfo["match_start_time"] >= time() ? 1 : 0;
        $_match_status_title = "";

        switch ($_arrOfSysMatchInfo["match_status"]) {
            case 1:
                $_match_status_title = $_language == "zh-CN" ? "未开始" : "Soon";
                break;
            case 2:
                $_match_status_title = $_language == "zh-CN" ? "比赛中" : "Runing";
                break;
            case 3:
                $_match_status_title = $_language == "zh-CN" ? "已结束" : "End";
                break;
        }


        $_form_array = array(
            array(
                "label" => LanguageController::getLanguage($_language, "match_time"),
                "value" => date("Y.m.d H:i", $_arrOfSysMatchInfo["match_start_time"]) . " - " . date("Y.m.d H:i", $_arrOfSysMatchInfo["match_stop_time"]),
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/time.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_bouns"),
                "value" => $_language == "zh-CN" ? $_arrOfSysMatchInfo["match_champion_prize_description"] : $_arrOfSysMatchInfo["match_champion_prize_description_en"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/bonus.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_user"),
                "value" => $_arrOfSysMatchInfo["match_user_sign_count"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/join.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_group"),
                "value" => $_arrOfSysMatchInfo["is_group"] == 0 ? ($_language == "zh-CN" ? "个人参赛" : "Person") : ($_language == "zh-CN" ? "团队参赛" : "Group"),
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/require.png",
                "is_html" => 0
            ),
//            array(
//                "label" => LanguageController::getLanguage($_language, "match_description"),
////                "value"=>"<div style='color:#767779'>".$_arrOfSysMatchInfo["match_description"]."</div>",
//                "value" => "<div style='color:#767779'>" . $_language == "zh-CN" ? $_arrOfSysMatchInfo["match_description"] : $_arrOfSysMatchInfo["match_description_en"] . "</div>",
//                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/info.png",
//                "is_html" => 1
//            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_phone"),
                "value" => $_arrOfSysMatchInfo["match_phone"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/phone.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_email"),
                "value" => $_arrOfSysMatchInfo["match_email"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/email.png",
                "is_html" => 0
            )
        );

        if ($_form_array[1]["value"] === "") {
            unset($_form_array[1]);
        }

        $_arrOfMatchInfo = array(
            "sys_sys_match_id" => $_arrOfSysMatchInfo["sys_sys_match_id"],
            "sys_match_id" => $_arrOfSysMatchInfo["sys_match_id"],
            "match_title" => $_language == "zh-CN" ? $_arrOfSysMatchInfo["match_title"] : $_arrOfSysMatchInfo["match_title_en"],
            "match_description" => $_language == "zh-CN" ? $_arrOfSysMatchInfo["match_description"] : $_arrOfSysMatchInfo["match_description_en"],
            "match_status" => $_arrOfSysMatchInfo["match_status"],
            "match_status_title" => $_match_status_title,
            "match_join_pass" => $_match_join_pass,
            "is_group" => $_arrOfSysMatchInfo["is_group"],
            "match_image" => StaticDataController::$_server_url . "/" . $_arrOfSysMatchInfo["match_image"],
            "form_array" => array_values($_form_array)
        );

        return $_arrOfMatchInfo;

    }

}
