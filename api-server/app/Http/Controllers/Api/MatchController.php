<?php


namespace App\Http\Controllers\Api;


use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Requests\Api\Match\MatchGetMyRegularSeasonListRequest;
use App\Http\Requests\Api\Match\MatchGetRegularSeasonListRequest;
use App\Models\BrandRedeemLog;
use App\Models\MatchsBanner;
use App\Models\MatchsEventType;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\ShakeGroupUser;
use App\Models\SysMatch;
use App\Models\SysSetting;
use App\Models\SysShake;
use App\Models\SysTeamTag;
use App\Models\UserAchievement;
use App\Models\UserGroup;
use App\Models\UserGroupAssociated;
use App\Models\UserPlay;
use App\Models\UsrUser;
use App\Services\MatchV2Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MatchController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/24 15:34
     * @abstract _APP赛事轮播图列表
     */
    public function matchBannerList(Request $request)
    {

        $_arrOfMatchsBanner = MatchsBanner::with('sys_match:sys_match_id,matchs_event_type_id')
            ->where([
                "status" => 1,
            ])->select(
                "matchs_banner_id", "img_path", "banner_matchs_id"
            )->get();

        foreach ($_arrOfMatchsBanner as $value) {
            $value["img_path"] = StaticDataController::$_server_url . "/" . $value["img_path"];
            if (($value['sys_match']['matchs_event_type_id'] !== null) && isset($value['sys_match']['matchs_event_type_id'])) {
                $value['sys_match']['matchs_event_type_id'] == SettingMessage::matchs_event_type_id ? $value['is_quartets'] = 1 : $value['is_quartets'] = 0;
            } else {
                $value['is_quartets'] = 0;
            }
            unset($value['sys_match']);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfMatchsBanner),
                "list" => $_arrOfMatchsBanner
            )
        );
    }

    /**
     * @abstract 查询系统比赛项目列表
     * @param Request $request
     * @return array
     */
    public function matchEventList(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        $_token = $request->header('token');

        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
            );
        }

//        列表
        $_arrOfMatchEventQuery = MatchsEventType::where([
            "status" => 1,
        ]);

        if ($_language == "zh-CN") {
            $_arrOfMatchEventQuery = $_arrOfMatchEventQuery->select("matchs_event_type_id as match_event_id", "match_events_type_title as match_event_title");
        } else {
            $_arrOfMatchEventQuery = $_arrOfMatchEventQuery->select("matchs_event_type_id as match_event_id", "match_events_type_title_en as match_event_title");
        }

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
     * @abstract 查询系统赛事项目列表
     * @param Request $request
     * @return array
     */
    public function matchList(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        $_token = $request->header('token');
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
                "data" => array()
            );
        }

        if (!isset($_data['match_event_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
                "data" => array()
            );
        }

//        当前已发布，未结束的赛事列表
        $_arrOfMatchListQuery = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
            "sys_match.matchs_event_type_id" => $_data['match_event_id']
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->select(
            "sys_match.sys_match_id", "sys_match.match_champion_prize_description", "sys_sys_match.match_title"
            , "sys_sys_match.match_user_type_description", "sys_sys_match.match_user_sex_description", "sys_sys_match.match_start_time"
            , "sys_sys_match.match_stop_time", "sys_match.status", "sys_sys_match.match_user_sign_count", "sys_sys_match.match_status"
            , "sys_sys_match.is_group", "sys_sys_match.sys_match_id as sys_sys_match_id", "sys_sys_match.match_image"
            , "sys_sys_match.match_image_list", "sys_sys_match.join_status"
        )->orderBy("sys_sys_match.match_start_time", "DESC");

        $_arrOfMatchListCount = $_arrOfMatchListQuery->count();
        $_arrOfMatchList = $_arrOfMatchListQuery->skip($_offset)->take($_limit)->get();

        if (count($_arrOfMatchList) == 0) {
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "none_data"),
                "data" => array(
                    "count" => 0,
                    "list" => array()
                )
            );
        }

        $_arrOfMatchListKey = array();
        $_arrOfMatchListId = array();
        foreach ($_arrOfMatchList as $key => $value) {
            $value["start_time"] = date("Y.m.d H:i", $value["match_start_time"]);
            $value["stop_time"] = date("Y.m.d H:i", $value["match_stop_time"]);

            $value["pass_join"] = $value["match_start_time"] < time() ? 1 : 0;

            switch ($value["match_status"]) {
                case 1:
                    $value["match_status_title"] = $_language == "zh-CN" ? "未开始" : "Soon";
                    break;
                case 2:
                    $value["match_status_title"] = $_language == "zh-CN" ? "比赛中" : "Runing";
                    break;
                case 3:
                    $value["match_status_title"] = $_language == "zh-CN" ? "已结束" : "End";
                    break;
            }

            $value["user_join_status"] = self::UserIsJoinMatch($value["sys_match_id"], $value["is_group"], $_token);

            $value["matchs_stage_id"] = "";
            $value["view_type"] = 0;

            if (strpos($value["match_image"], 'http') === false) {
                $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                if ($value["match_image_list"] == "" || $value["match_image_list"] == null) {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                } else {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
                }
            }

            if (strpos($value["match_image_list"], 'http') === false) {
                $value["match_image_list"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
            }


            $_arrOfMatchListKey[$value["sys_match_id"]] = $value;
            array_push($_arrOfMatchListId, $value["sys_match_id"]);
        }

//        查询赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->whereIn("sys_match_id", $_arrOfMatchListId)->select(
            "matchs_stage_id", "view_type", "sys_match_id", "match_stage_start_time", "match_stage_stop_time"
        )->get();


        foreach ($_arrOfMatchStage as $key => $value) {
            if ($value["match_stage_start_time"] < time() && $value["match_stage_stop_time"] > time()) {
                $_arrOfMatchListKey[$value["sys_match_id"]]["matchs_stage_id"] = $value["matchs_stage_id"];
                $_arrOfMatchListKey[$value["sys_match_id"]]["view_type"] = $value["view_type"];
            }
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchListCount,
                "list" => array_values($_arrOfMatchListKey)
            )
        );
    }

    /**
     * @abstract 查询系统赛事项目列表 赛事列表v2==新版赛事
     * @param Request $request
     * @return array
     */
    public function matchListV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        $_token = $request->header('token');
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_limit = $_limit + 10;
        $_offset = ($_page - 1) * $_limit;

        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
                "data" => array()
            );
        }

        if (!isset($_data['type']) || !in_array($_data['type'], [1, 2, 3, 4, 5])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
                "data" => array()
            );
        }
        $team_name = SettingMessage::team_name;

//        当前已发布，未结束的赛事列表
        $_arrOfMatchListQuery = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
//            "sys_match.matchs_event_type_id"=>$_data['match_event_id']
//            "sys_match.matchs_event_type_id"=> SettingMessage::matchs_event_type_id,//摇跑四项赛
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->select(
            "sys_match.sys_match_id", "sys_match.match_champion_prize_description", "sys_sys_match.match_title", "sys_sys_match.stage_type"
            , "sys_sys_match.match_user_type_description", "sys_sys_match.match_user_sex_description", "sys_sys_match.match_start_time"
            , "sys_sys_match.match_stop_time", "sys_match.status", "sys_match.external_url", "sys_sys_match.match_user_sign_count", "sys_sys_match.match_status" , "sys_sys_match.is_group", "sys_sys_match.sys_match_id as sys_sys_match_id", "sys_sys_match.match_image" , "sys_sys_match.match_image_list", "sys_sys_match.match_image_list_new","sys_sys_match.is_regular","sys_sys_match.is_practice", "sys_sys_match.join_status", "sys_sys_match.is_hot", "sys_match.matchs_event_type_id","sys_sys_match.team_name"
        );//DB::raw("FROM_UNIXTIME(sys_sys_match.match_start_time,'%Y-%m-%d %T') as match_start_time_sort"),DB::raw("FROM_UNIXTIME(sys_sys_match.match_stop_time,'%Y-%m-%d %T') as match_stop_time_sort")

        switch ($_data['type']) {
            case 4://进行中+待开始
                $_arrOfMatchListQuery = $_arrOfMatchListQuery->where('sys_sys_match.match_status', 1)->orWhere('sys_sys_match.match_status', 2)->where(["sys_sys_match.status" => 1, "sys_match.status" => 1,])->orderBy("sys_sys_match.is_hot", "DESC")->orderBy("sys_sys_match.match_start_time", "ASC");
                break;
            case 5://全部
                $_data['is_regular'] = $_data['is_regular'] ?? 1;//默认包含 为0时屏蔽
                if ($_data['is_regular'] !== 1){
                    $_arrOfMatchListQuery = $_arrOfMatchListQuery->where('sys_sys_match.is_regular',0);
                }
                $_arrOfMatchListQuery = $_arrOfMatchListQuery->orderByRaw("FIELD(sys_sys_match.match_status,2,1,3)")->orderBy("sys_sys_match.is_hot", "DESC")->orderByRaw("IF(sys_sys_match.match_status=3,sys_sys_match.match_stop_time,sys_sys_match.match_start_time) DESC");
                break;
            case 3://按对应查询
                $_arrOfMatchListQuery = $_arrOfMatchListQuery->where('sys_sys_match.match_status', $_data['type'])->orderBy("sys_sys_match.is_hot", "DESC")->orderBy("match_stop_time", "DESC");
                break;
            default://1,2按对应查询
                $_arrOfMatchListQuery = $_arrOfMatchListQuery->where('sys_sys_match.match_status', $_data['type'])->orderBy("sys_sys_match.is_hot", "DESC")->orderBy("sys_sys_match.match_start_time", "DESC");
                break;
        }

        $_arrOfMatchListCount = $_arrOfMatchListQuery->count();
        $_arrOfMatchList = $_arrOfMatchListQuery->skip($_offset)->take($_limit)->get();

        if (count($_arrOfMatchList) == 0) {
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "none_data"),
                "data" => array(
                    "count" => 0,
                    "list" => array()
                )
            );
        }

        $_arrOfMatchListKey = array();
        $_arrOfMatchListId = array();
        foreach ($_arrOfMatchList as $key => $value) {
            $value["start_time"] = date("Y.m.d H:i", $value["match_start_time"]);
            $value["stop_time"] = date("Y.m.d H:i", $value["match_stop_time"]);

            $value["pass_join"] = $value["match_start_time"] < time() ? 1 : 0;

            if ($value['matchs_event_type_id'] == SettingMessage::matchs_event_type_id) {//是否为四项赛事
                $value['is_quartets'] = 1;
//                $value["team_name"] = $team_name;
            } else {
                $value['is_quartets'] = 0;
                $value["team_name"] = '';
            }

            switch ($value["match_status"]) {
                case 1:
                    $value["match_status_title"] = $_language == "zh-CN" ? "未开始" : "Soon";
                    break;
                case 2:
                    $value["match_status_title"] = $_language == "zh-CN" ? "比赛中" : "Runing";
                    break;
                case 3:
                    $value["match_status_title"] = $_language == "zh-CN" ? "已结束" : "End";
                    break;
            }

            $value["user_join_status"] = self::UserIsJoinMatch($value["sys_match_id"], $value["is_group"], $_token);

            $value["matchs_stage_id"] = "";
            $value["view_type"] = 0;

            if (strpos($value["match_image"], 'http') === false) {
                $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                if ($value["match_image_list"] == "" || $value["match_image_list"] == null) {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                    if(empty($value["match_image"])) {
                        $value["match_image"] = '';
                    }
                } else {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
                    if(empty($value["match_image_list"])) {
                        $value["match_image"] = '';
                    }
                }
            }

            if (strpos($value["match_image_list"], 'http') === false) {
                $value["match_image_list"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
            }

            if (strpos($value["match_image_list_new"], 'http') === false) {
                $value["match_image_list_new"] = empty($value["match_image_list_new"]) ? '' : StaticDataController::$_server_url . "/" . $value["match_image_list_new"];
                
            }


            $_arrOfMatchListKey[$value["sys_match_id"]] = $value;
            array_push($_arrOfMatchListId, $value["sys_match_id"]);
        }

//        查询赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->whereIn("sys_match_id", $_arrOfMatchListId)->select(
            "matchs_stage_id", "view_type", "sys_match_id", "match_stage_start_time", "match_stage_stop_time"
        )->get();


        foreach ($_arrOfMatchStage as $key => $value) {
            if ($value["match_stage_start_time"] < time() && $value["match_stage_stop_time"] > time()) {
                $_arrOfMatchListKey[$value["sys_match_id"]]["matchs_stage_id"] = $value["matchs_stage_id"];
                $_arrOfMatchListKey[$value["sys_match_id"]]["view_type"] = $value["view_type"];
            }
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchListCount,
                "list" => array_values($_arrOfMatchListKey)
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

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }
        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
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
            , "sys_match.match_phone", "sys_match.match_email", "sys_match.match_description", "sys_match.match_title", "sys_match.speed_duration", "sys_match.matchs_type_id"
            , "sys_match.match_phone_prefix", "sys_match.match_image", "matchs_type.matchs_type_title", "sys_match.match_user_type_description"
            , "sys_match.match_user_sex_description", "sys_match_node.match_champion_prize_description", "sys_match_node.matchs_event_type_id"
            , "sys_match_node.sys_match_id", "sys_match.match_user_sign_count", "sys_match.is_group", "sys_match.match_status"
            , "sys_match.external_url", "sys_match_node.match_champion_prize_description", "sys_match.match_start_time", "sys_match.match_stop_time", "sys_match.join_status"
        )->get();


//        找到数据
        if (count($_arrOfSysMatch) == 1) {
            $_arrOfSysMatchInfo = $_arrOfSysMatch[0];

//            用户查询赛事数据
            $_arrOfMatchInfo = self::SysMatchUserInfo($_arrOfSysMatchInfo, $_language, $_data['sys_match_id'], $_token);

            $_arrOfMatchStage = MatchsStage::where([
                "status" => 1,
                "sys_match_id" => $_data['sys_match_id']
            ])->select(
                "matchs_stage_id", "match_stage_title", "match_stage_start_time", "match_stage_stop_time", "max_integral", "sub_integral", "get_integral_type", "get_integral_value"
                , "match_promotion_type", "match_promotion_value", "sys_sys_match_id", "match_stage_distance", "view_type", "matchs_stage_status"
            )->get();


            $_arrOfMatchInfo["matchs_stage_id"] = "";
            $_arrOfMatchInfo["view_type"] = 0;

            foreach ($_arrOfMatchStage as $key => $value) {
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
            $_arrOfMatchInfo["join_status"] = $_arrOfSysMatchInfo["join_status"];


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
     * @abstract 查询赛事详情 赛事详情v2==新版赛事
     * @param Request $request
     * @return array
     */
    public function matchInfoV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        $limit = $_data['limit'] ?? 7;

        if (!isset($_data['sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }
        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        //记录浏览人数
        MatchV2Service::setMatchBrowseNum($_data['sys_match_id'],$_usr_user['user_id']);

        $_arrOfSysMatch = SysMatch::where([
            "sys_match.status" => 1,
            "sys_match_node.sys_match_id" => $_data['sys_match_id']
        ])->join("matchs_type", function ($join) {
            $join->on("sys_match.matchs_type_id", "=", "matchs_type.matchs_type_id");
        })->join("sys_match as sys_match_node", function ($join) {
            $join->on("sys_match_node.sys_sys_match_id", "=", "sys_match.sys_match_id");
        })->join("matchs_event_type", function ($join) {
            $join->on("sys_match_node.matchs_event_type_id", "=", "matchs_event_type.matchs_event_type_id");
        })->select(
            "sys_match.sys_match_id as sys_sys_match_id", "sys_match.match_user_sex", "sys_match.match_user_type", "sys_match.match_start_time", "sys_match.match_stop_time"
            , "sys_match.match_phone", "sys_match.team_name", "sys_match.quartets_icon", "sys_match.match_email", "sys_match.match_description", "sys_match.match_description_en", "sys_match.match_title", "sys_match.match_title_en", "sys_match.stage_type", "sys_match.is_regular", "sys_match.is_practice", "sys_match.matchs_type_id", "sys_match.ranking_type_list","sys_match.show_name"
            , "sys_match.match_phone_prefix", "sys_match.match_image", "matchs_type.matchs_type_title", "sys_match.match_user_type_description"
            , "sys_match.match_user_sex_description", "sys_match_node.match_champion_prize_description", "sys_match_node.match_champion_prize_description_en", "sys_match_node.matchs_event_type_id"
            , "sys_match_node.sys_match_id", "sys_match.match_user_sign_count", "sys_match.is_group", "sys_match.match_status"
            , "sys_match.external_url", "sys_match_node.match_champion_prize_description", "sys_match.match_start_time", "sys_match.match_stop_time", "sys_match.join_status", "matchs_event_type.match_events_type_title", "matchs_event_type.match_events_type_title_en", "sys_match.browse_num", "sys_match.organizer_introduce", "sys_match.douyin_json"
        )
            ->get();


//        找到数据
        if (count($_arrOfSysMatch) == 1) {
            $_arrOfSysMatchInfo = $_arrOfSysMatch[0];
            $_arrOfSysMatchInfo['sys_match']['match_image_list_new'] = empty($_arrOfSysMatchInfo['sys_match']['match_image_list_new']) ? '' : StaticDataController::$_server_url . "/" .$_arrOfSysMatchInfo['sys_match']['match_image_list_new'];
            $_arrOfSysMatchInfo['sys_match']['match_image'] = empty($_arrOfSysMatchInfo['sys_match']['match_image']) ? $_arrOfSysMatchInfo['sys_match']['match_image'] : StaticDataController::$_server_url . "/" .$_arrOfSysMatchInfo['sys_match']['match_image'];
            if(empty($_arrOfSysMatchInfo['sys_match']['match_image_list_new'])) {
                $_arrOfSysMatchInfo['sys_match']['match_image_list_new'] = $_arrOfSysMatchInfo['sys_match']['match_image'];
            }
            $_arrOfSysMatchInfo['sys_match']['quartets_icon'] = empty($_arrOfSysMatchInfo['sys_match']['quartets_icon']) ? null : StaticDataController::$_server_url . "/" .$_arrOfSysMatchInfo['sys_match']['quartets_icon'];
            $_arrOfSysMatchInfo['sys_match']['match_image_list'] = empty($_arrOfSysMatchInfo['sys_match']['match_image_list']) ? null : StaticDataController::$_server_url . "/" .$_arrOfSysMatchInfo['sys_match']['match_image_list'];

            if (!empty($_arrOfSysMatchInfo['sys_match']['douyin_json'])){
                try {
                    $_arrOfSysMatchInfo['sys_match']['douyin_json'] = json_decode($_arrOfSysMatchInfo['sys_match']['douyin_json'],true);
                }catch (\Throwable $ex){//异常不处理
                    $_arrOfSysMatchInfo['sys_match']['douyin_json'] = $_arrOfSysMatchInfo['sys_match']['douyin_json'];
                }
            }

            //项目有成绩人数 TODO ...
            $_arrOfSysMatchInfo['sys_match']['four_score'] = MatchsUserGrade::whereHas('matchs_stage', function ($query) use ($_arrOfSysMatchInfo) {
                $query->where(['status' => 1,'sys_sys_match_id' => $_arrOfSysMatchInfo['sys_match']['sys_match_id']]);
            })->where('is_join',1)
                ->selectRaw("COUNT(IF(team_tag != '',TRUE,NULL)) AS team_tag_count,COUNT(IF(s_speed_max > 0,TRUE,NULL)) AS s_speed_max_count,COUNT(IF(s_marathon > 0,TRUE,NULL)) AS s_marathon_count,COUNT(IF(s_exponent_molecular > 0,TRUE,NULL)) AS s_exponent_molecular_count,COUNT(IF(s_runball_exponent > 0,TRUE,NULL)) AS s_runball_exponent_count")
                ->first();

            //获取报名用户前七人
            $_arrOfSysMatchInfo['sys_match']['matchs_user_list'] = MatchsUser::with(['usr_user_one' => function($query){
                $query->select('user_id','user_name',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',user_img) as user_img"));
            }])->select('user_id')
                ->where(['sys_sys_match_id' => $_arrOfSysMatchInfo['sys_match']['sys_match_id'],'status' => 1,'is_join' =>1])
                ->orderBy('created_time', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($item){
                    return $item = $item->usr_user_one;
                });

//            用户查询赛事数据
            $_arrOfMatchInfo = self::SysMatchUserInfo($_arrOfSysMatchInfo, $_language, $_data['sys_match_id'], $_token);

            $_arrOfMatchStage = MatchsStage::where([
                "status" => 1,
                "sys_match_id" => $_data['sys_match_id']
            ])->select(
                "matchs_stage_id", "match_stage_title", "match_stage_start_time", "match_stage_stop_time", "max_integral", "sub_integral", "get_integral_type", "get_integral_value"
                , "match_promotion_type", "match_promotion_value", "sys_sys_match_id", "match_stage_distance", "view_type", "matchs_stage_status"
            )->get();

            $team_info = MatchsUser::where([
                "sys_match_id" => $_data['sys_match_id'],
                "is_join" => 1,
                "user_id" => $_usr_user['user_id']
            ])->select('matchs_user_id', 'team_tag')->first();
            if (!empty($team_info)) {
                $team_info['join_sum'] = MatchsUser::where([
                    "sys_match_id" => $_data['sys_match_id'],
                    "is_join" => 1,
                    "team_tag" => $team_info['team_tag']
                ])->count();
            }

            $_arrOfMatchInfo["matchs_stage_id"] = '';
            $_arrOfMatchInfo["view_type"] = 0;

            foreach ($_arrOfMatchStage as $key => $value) {
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
            $_arrOfMatchInfo["join_status"] = $_arrOfSysMatchInfo["join_status"];
            $_arrOfMatchInfo["team_info"] = $team_info;
            if (empty($_arrOfMatchInfo['matchs_stage_id'])) {
                $_arrOfMatchInfo["matchs_stage_id"] = MatchsStage::where(["status" => 1, "sys_match_id" => $_data['sys_match_id']])->orderBy("match_stage_stop_time", "DESC")->value('matchs_stage_id');
            }

/*            //TODO 已更新加入字段 ...
            if ($_arrOfMatchInfo['is_quartets'] == 1) {
                $_arrOfMatchInfo["team_name"] = SettingMessage::team_name;
                $_arrOfMatchInfo["quartets_icon"] = StaticDataController::$_server_url . "/" . SettingMessage::quartets_icon;
            } else {
                $_arrOfMatchInfo["team_name"] = '';
                $_arrOfMatchInfo["quartets_icon"] = '';
            }*/

            $_arrOfMatchInfo["team_name"] = empty($_arrOfMatchInfo["team_name"]) ? '' : $_arrOfMatchInfo["team_name"];
            $_arrOfMatchInfo["quartets_icon"] = empty($_arrOfMatchInfo["quartets_icon"]) ? '' : StaticDataController::$_server_url . "/" . $_arrOfMatchInfo["quartets_icon"];

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
     * 获取赛段截止时间v2==新版赛事
     * @param Request $request
     * @return array
     * User: zxw
     * Date: 2021/10/14 18:30
     */
    public function matchStageStopTimeV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }
        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
            );
        }

        //        查询赛事赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_data["sys_match_id"],
            "sys_sys_match_id" => $_data["sys_sys_match_id"],
            "matchs_stage_status" => 2
        ])->select(
            "matchs_stage_id", "match_stage_start_time", "match_stage_stop_time"
        )->orderBy("match_stage_start_time", "ASC")->get();

        if (count($_arrOfMatchStage) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_not_fount"),
                "data" => array()
            );
        }

        $_first_stage = $_arrOfMatchStage[0];
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_first_stage
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/12 17:56
     * @abstract _用户输入团队编号查询团队
     */
    public function postUserGroupInfo(Request $request)
    {

        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        if (!isset($_data["group_num"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserGroup = UserGroup::where([
            "status" => 1,
            "group_num" => $_data["group_num"]
        ])->select("user_group_id", "group_title")->get();

        if (count($_arrOfUserGroup) == 0) {
            return array(
                "code" => 0,
                "msg" => $_language == "zh-CN" ? "您输入的ID没有找到对应的团队，请重新输入。" : "Your ID  is not found, Please input again"
            );
        } else {
            return array(
                "code" => 1,
                "msg" => $_language == "zh-CN" ? "你输入的ID对应的是" . $_arrOfUserGroup[0]["group_title"] : "Your ID  is " . $_arrOfUserGroup[0]["group_title"],
                "data" => array(
                    "user_group_id" => $_arrOfUserGroup[0]["user_group_id"]
                )
            );
        }
    }


    /**
     * @abstract 用户报名
     * @param Request $request
     * @return array
     */
    public function matchUserSign(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id']) || (isset($_data['is_group']) && $_data["is_group"] == 1 && !isset($_data["user_group_id"]))) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

//        查询赛事赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_data["sys_match_id"]
        ])->select(
            "matchs_stage_id", "match_stage_start_time", "match_stage_stop_time"
        )->orderBy("match_stage_start_time", "ASC")->get();


        if (count($_arrOfMatchStage) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_not_fount"),
                "data" => array()
            );
        }

        $_first_stage = $_arrOfMatchStage[0];


//        如果是团队赛
        if (isset($_data['is_group']) && $_data["is_group"] == 1) {
//            查询团队用户列表
            $_arrOfUserGroupAssociated = UserGroupAssociated::where([
                "user_group_associated.status" => 1,
                "user_group.user_group_id" => $_data["user_group_id"],
            ])->join("user_group", function ($join) {
                $join->on("user_group_associated.user_group_id", "=", "user_group.user_group_id");
            })->select(
                "user_group_associated.user_group_id", "user_group_associated.user_id", "user_group.group_title"
            )->get();


            $_arrOfUserId = array();
            foreach ($_arrOfUserGroupAssociated as $value) {
                array_push($_arrOfUserId, $value["user_id"]);
            }

            if (count($_arrOfUserGroupAssociated) >= 12 && !in_array($_usr_user["user_id"], $_arrOfUserId)) {
                return array(
                    "code" => 0,
                    "msg" => "团队已满员"
                );
            }


            $_sno = new Snowflake(StaticDataController::$_workId);
//            用户未加入团队,先将用户添加到团队
            if (!in_array($_usr_user["user_id"], $_arrOfUserId)) {
                $_arrOfUserGroupAssociatedData = array(
                    "user_group_associated_id" => $_sno->nextId(),
                    "user_id" => $_usr_user["user_id"],
                    "user_group_id" => $_data["user_group_id"],
                    "status" => 1
                );
                UserGroupAssociated::where(["status" => 1, "user_id" => $_usr_user["user_id"]])->update(["status" => 0, "audit_description" => "加入其它团队"]);
                UserGroupAssociated::create($_arrOfUserGroupAssociatedData);
            }

//            判断团队是否已加入赛事
            $_arrOfMatchUser = MatchsUser::where([
                "status" => 1,
                "is_group" => 1,
                "sys_match_id" => $_data["sys_match_id"],
                "sys_sys_match_id" => $_data["sys_sys_match_id"],
                "user_group_id" => $_data["user_group_id"]
            ])->select("matchs_user_id")->get();


            if (count($_arrOfMatchUser) == 0) {
                $_MatchsUserData = array(
                    "matchs_user_id" => $_sno->nextId(),
                    "sys_match_id" => $_data["sys_match_id"],
                    "user_group_id" => $_data["user_group_id"],
                    "user_group_name" => $_arrOfUserGroupAssociated[0]["group_title"],
                    "sys_sys_match_id" => $_data["sys_sys_match_id"],
                    "is_group" => 1,
                    "status" => 1,
                    "is_join" => 1,
                    "stage_pass" => 1,
                );
                MatchsUser::create($_MatchsUserData);

//                创建初始成绩
                MatchsUserGrade::create([
                    "matchs_user_grade_id" => $_sno->nextId(),
                    "matchs_stage_id" => $_first_stage["matchs_stage_id"],
                    "is_group" => 1,
                    "match_ranking" => 0,
                    "match_grade" => 9999999999,
                    "matchs_user_id" => $_MatchsUserData["matchs_user_id"],
                    "user_group_id" => $_data["user_group_id"]
                ]);


                $_join_match_count = isset($_usr_user["join_match_count"]) ? $_usr_user["join_match_count"] + 1 : 1;
                $_usr_user["join_match_count"] = $_join_match_count;
                Redis::hset("usr_user", $_token, json_encode($_usr_user));
                UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["join_match_count" => $_join_match_count]);

            }

            $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();

            SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);

            return array(
                "code" => 1,
                "msg" => "报名成功",
                "data" => array(
                    "user_group_id" => $_data["user_group_id"]
                )
            );
        }

//        个人赛逻辑

//        查询当前用户在当前赛事中的各项目报名信息
        $_arrOfMatchUser = MatchsUser::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"],
            "sys_sys_match_id" => $_data["sys_sys_match_id"],
        ])->select("matchs_user_id", "sys_match_id", "is_join")->get();

//        return $_arrOfMatchUser;


        $_arrOfMatchUserKey = array();
        $_arrOfMatchUserSign = array();
        foreach ($_arrOfMatchUser as $value) {
//            如果已报名
            if ($value["is_join"] == 1) {
                array_push($_arrOfMatchUserSign, $value["sys_match_id"]);
            }

//            如果当前报名的项目已经报名，终止
            if ($value["sys_match_id"] == $_data["sys_match_id"] && $value["is_join"] == 1) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_sign_exit")
                );
            }

            $_arrOfMatchUserKey[$value["sys_match_id"]] = $value;
        }

//        如果已报名的项目数大于限制的项目数，返回
        if (count($_arrOfMatchUserSign) >= StaticDataController::$_match_max_sign_count) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_max_count")
            );
        }

//        如果当前项目数据已存在，变更状态为已报名
        if (array_key_exists($_data["sys_match_id"], $_arrOfMatchUserKey)) {
            MatchsUser::where([
                "user_id" => $_usr_user["user_id"],
                "sys_match_id" => $_data["sys_match_id"]
            ])->update([
                "is_join" => 1
            ]);

            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "match_user_join_success")
            );
        } else {
//            不存在数据，创建
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfMatchUserData = array(
                "matchs_user_id" => $_sno->nextId(),
                "user_id" => $_usr_user["user_id"],
                "user_name" => $_usr_user["user_name"],
                "sys_match_id" => $_data["sys_match_id"],
                "sys_sys_match_id" => $_data["sys_sys_match_id"],
                "status" => 1,
                "is_join" => 1,
                "stage_pass" => 1,
            );

            MatchsUser::create($_arrOfMatchUserData);

//                创建初始成绩
            MatchsUserGrade::create([
                "matchs_user_grade_id" => $_sno->nextId(),
                "matchs_stage_id" => $_first_stage["matchs_stage_id"],
                "is_group" => 0,
                "match_ranking" => 0,
                "match_grade" => 9999999999,
                "matchs_user_id" => $_arrOfMatchUserData["matchs_user_id"],
                "user_id" => $_usr_user["user_id"],
            ]);
            $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();

            SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);


            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "match_user_join_success")
            );
        }
    }

    /**
     * @abstract 用户报名  用户报名v2==新版赛事
     * @param Request $request
     * @return array
     */
    public function matchUserSignV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id']) || (isset($_data['is_group']) && $_data["is_group"] == 1 && !isset($_data["user_group_id"])) && !isset($_data['is_quartets'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        if (!isset($_usr_user['sys_user_type_id']) || $_usr_user['sys_user_type_id'] !== "1809649560981504") {//验证游客不能参赛
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "please_log_in_to_enter"),
                "data" => array()
            );
        }

        //判断赛事是否结束
        $sysMatch = SysMatch::where('sys_match_id', $_data['sys_sys_match_id'])->first();
        if ($sysMatch['match_status'] == 3) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "the_match_is_over"),
                "data" => array()
            );
        }

        //报名状态判断===是否关闭报名
        if ($sysMatch['join_status'] == 2) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_user_join_sign_error"),
                "data" => array()
            );
        }
        //报名状态判断===是否只允许会员报名
        if ($sysMatch['join_status'] == 3) {
            if (empty($_usr_user['is_members'])){
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_join_sign_members_error"),
                    "data" => array()
                );
            }
            if ($_usr_user['is_members'] !== 1){
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_join_sign_members_error"),
                    "data" => array()
                );
            }
        }

//        查询赛事赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_data["sys_match_id"]
        ])->select(
            "matchs_stage_id", "match_stage_start_time", "match_stage_stop_time"
        )->orderBy("match_stage_start_time", "ASC")->get();


        if (count($_arrOfMatchStage) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_not_fount"),
                "data" => array()
            );
        }

        $_first_stage = $_arrOfMatchStage[0];


//        如果是团队赛
        if (isset($_data['is_group']) && $_data["is_group"] == 1) {
//            查询团队用户列表

            $_arrOfUserGroupAssociated = UserGroupAssociated::where([
                "user_group_associated.status" => 1,
                "user_group.user_group_id" => $_data["user_group_id"],
            ])->join("user_group", function ($join) {
                $join->on("user_group_associated.user_group_id", "=", "user_group.user_group_id");
            })->select(
                "user_group_associated.user_group_id", "user_group_associated.user_id", "user_group.group_title"
            )->get();
            $_arrOfUserId = array();
            foreach ($_arrOfUserGroupAssociated as $value) {
                array_push($_arrOfUserId, $value["user_id"]);
            }

            if (count($_arrOfUserGroupAssociated) >= 12 && !in_array($_usr_user["user_id"], $_arrOfUserId)) {
                return array(
                    "code" => 0,
                    "msg" => "团队已满员"
                );
            }


            $_sno = new Snowflake(StaticDataController::$_workId);
//            用户未加入团队,先将用户添加到团队
            if (!in_array($_usr_user["user_id"], $_arrOfUserId)) {
                $_arrOfUserGroupAssociatedData = array(
                    "user_group_associated_id" => $_sno->nextId(),
                    "user_id" => $_usr_user["user_id"],
                    "user_group_id" => $_data["user_group_id"],
                    "status" => 1
                );
                UserGroupAssociated::where(["status" => 1, "user_id" => $_usr_user["user_id"]])->update(["status" => 0, "audit_description" => "加入其它团队"]);
                UserGroupAssociated::create($_arrOfUserGroupAssociatedData);
            }

//            判断团队是否已加入赛事
            $_arrOfMatchUser = MatchsUser::where([
                "status" => 1,
                "is_group" => 1,
                "sys_match_id" => $_data["sys_match_id"],
                "sys_sys_match_id" => $_data["sys_sys_match_id"],
                "user_group_id" => $_data["user_group_id"]
            ])->select("matchs_user_id")->get();


            if (count($_arrOfMatchUser) == 0) {
                $_MatchsUserData = array(
                    "matchs_user_id" => $_sno->nextId(),
                    "sys_match_id" => $_data["sys_match_id"],
                    "user_group_id" => $_data["user_group_id"],
                    "user_group_name" => $_arrOfUserGroupAssociated[0]["group_title"],
                    "sys_sys_match_id" => $_data["sys_sys_match_id"],
                    "is_group" => 1,
                    "status" => 1,
                    "is_join" => 1,
                    "stage_pass" => 1,
                );
                MatchsUser::create($_MatchsUserData);

//                创建初始成绩
                MatchsUserGrade::create([
                    "matchs_user_grade_id" => $_sno->nextId(),
                    "matchs_stage_id" => $_first_stage["matchs_stage_id"],
                    "is_group" => 1,
                    "match_ranking" => 0,
                    "match_grade" => 9999999999,
                    "matchs_user_id" => $_MatchsUserData["matchs_user_id"],
                    "user_group_id" => $_data["user_group_id"]
                ]);


                $_join_match_count = isset($_usr_user["join_match_count"]) ? $_usr_user["join_match_count"] + 1 : 1;
                $_usr_user["join_match_count"] = $_join_match_count;
                Redis::hset("usr_user", $_token, json_encode($_usr_user));
                UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["join_match_count" => $_join_match_count]);

            }

            $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();

            SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);

            return array(
                "code" => 1,
                "msg" => "报名成功",
                "data" => array(
                    "user_group_id" => $_data["user_group_id"]
                )
            );
        }

//        个人赛逻辑

//        查询当前用户在当前赛事中的各项目报名信息
        $_arrOfMatchUser = MatchsUser::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"],
            "sys_sys_match_id" => $_data["sys_sys_match_id"],
        ])->select("matchs_user_id", "sys_match_id", "is_join")->get();

//        return $_arrOfMatchUser;


        $_arrOfMatchUserKey = array();
        $_arrOfMatchUserSign = array();
        foreach ($_arrOfMatchUser as $value) {
//            如果已报名
            if ($value["is_join"] == 1) {
                array_push($_arrOfMatchUserSign, $value["sys_match_id"]);
            }

//            如果当前报名的项目已经报名，终止
            if ($value["sys_match_id"] == $_data["sys_match_id"] && $value["is_join"] == 1) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_sign_exit")
                );
            }

            $_arrOfMatchUserKey[$value["sys_match_id"]] = $value;
        }

//        如果已报名的项目数大于限制的项目数，返回
        if (count($_arrOfMatchUserSign) >= StaticDataController::$_match_max_sign_count) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "match_max_count")
            );
        }

        $join_match_count = UserAchievement::where('user_id', $_usr_user["user_id"])->value('join_match_count');
//        如果当前项目数据已存在，变更状态为已报名
        if (array_key_exists($_data["sys_match_id"], $_arrOfMatchUserKey)) {
            //获取赛段ID
            $matchsStageId = MatchsStage::where([
                "sys_match_id" => $_data["sys_match_id"],
                "sys_sys_match_id" => $_data["sys_sys_match_id"],
            ])->value('matchs_stage_id');
            $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();
            try {
                DB::transaction(function () use ($_arrOfSysMatch, $join_match_count, $matchsStageId, $_usr_user, $_data) {
                    MatchsUser::where([
                        "user_id" => $_usr_user["user_id"],
                        "sys_match_id" => $_data["sys_match_id"]
                    ])->update([
                        "is_join" => 1,
                        'team_tag' => $_data['team_tag'] ?? 0,
                    ]);
                    MatchsUserGrade::where([
                        "user_id" => $_usr_user["user_id"],
                        "matchs_stage_id" => $matchsStageId,
                    ])->update([
                        "is_join" => 1,
                        'team_tag' => $_data['team_tag'] ?? 0,
                    ]);
                    SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);
                    UserAchievement::where('user_id', $_usr_user["user_id"])->update(["join_match_count" => $join_match_count + 1]);
                }, 5);
            } catch (\Throwable $ex) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_join_error")
                );
            }
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "match_user_join_success")
            );
        } else {
            if (empty($_data["team_tag"]) && $_data['is_quartets'] == 1) {//判断四项赛事是否有传团队标签
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
                );
            }
            $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();
//            不存在数据，创建
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfMatchUserData = array(
                "matchs_user_id" => $_sno->nextId(),
                "user_id" => $_usr_user["user_id"],
                "user_name" => $_usr_user["user_name"],
                "sys_match_id" => $_data["sys_match_id"],
                "sys_sys_match_id" => $_data["sys_sys_match_id"],
                "status" => 1,
                "is_join" => 1,
                "stage_pass" => 1,
                "team_tag" => $_data["team_tag"],
                "is_quartets" => $_data["is_quartets"],
            );

            try {
                DB::transaction(function () use ($join_match_count, $_arrOfMatchUserData, $_sno, $_first_stage, $_usr_user, $_data, $_arrOfSysMatch) {
                    MatchsUser::create($_arrOfMatchUserData);
                    //                创建初始成绩
                    MatchsUserGrade::create([
                        "matchs_user_grade_id" => $_sno->nextId(),
                        "matchs_stage_id" => $_first_stage["matchs_stage_id"],
                        "is_group" => 0,
                        "match_ranking" => 0,
                        "match_grade" => 9999999999,
                        "matchs_user_id" => $_arrOfMatchUserData["matchs_user_id"],
                        "user_id" => $_usr_user["user_id"],
                        "team_tag" => $_data["team_tag"],
                        "is_quartets" => $_data["is_quartets"],
                        "is_join" => 1,
                        "s_duration" => 0,
                        "s_speed_max" => 0,
                        "s_circle_count" => 0,
                        "s_endurance_max" => 0,
                        "s_play_count" => 0,
                        "s_distance_max" => 0,
                        "s_thrmin" => 0,
                        "s_half_marathon" => 0,
                        "s_marathon" => 0,
                        "s_exponent_denominator" => 0,
                        "s_exponent_molecular" => 0,
                        "s_runball_exponent" => 0,
                        "s_speed_max_time" => 0,
                        "s_runball_exponent_time" => 0,
                        "s_exponent_molecular_time" => 0,
                        "s_marathon_time" => 0,
                    ]);
                    SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);
                    UserAchievement::where('user_id', $_usr_user["user_id"])->update(["join_match_count" => $join_match_count + 1]);
                }, 5);
            } catch (\Throwable $ex) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "match_user_join_error")
                );
            }
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "match_user_join_success")
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/5/13 13:32
     * @abstract _开始比赛前查询赛事目前状态
     */
    public function beforMatchPlayStart(Request $request)
    {

        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $request->header('token')), true);


        if (!isset($_data['sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

//        查询当前赛段

        $_show_all = isset($_data["show_all"]) ? $_data["show_all"] : 0;

        $_user_group_id = isset($_data["user_group_id"]) ? $_data["user_group_id"] : "";

        $_arrData = self::MatchPlayInfo($_language, $_data["sys_match_id"], $_user_group_id, $_usr_user, $_show_all, "before_play");

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrData
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/14 16:57
     * @abstract _赛事运动数据
     */
    public static function MatchPlayInfo($_language, $_sys_match_id, $_user_group_id, $_usr_user, $_show_all, $_type)
    {

//        查询当前赛事、赛段信息
        $_arrOfMathcStatge = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id
        ])->where("match_stage_start_time", "<=", time())
            ->where("match_stage_stop_time", ">=", time())->select(
                "matchs_stage_id", "match_stage_distance", "match_stage_stop_time"
            )->get();

//        当前没有赛段信息
        if (count($_arrOfMathcStatge) != 1) {
            Redis::select(1);
            $_matchs_end_tips = $_language == "zh-CN" ? "比赛结束" : "Match Stop";


//            查询当前时间前的第一个赛段
            $_arrOfLastMatchStage = MatchsStage::where([
                "status" => 1,
                "sys_match_id" => $_sys_match_id
            ])->select(
                "matchs_stage_id", "match_stage_distance", "match_stage_stop_time"
            )->orderBy("match_stage_stop_time", "DESC")->get();

            $_matchStageInfo = $_arrOfLastMatchStage[0];

            $_all_distince_value = $_matchStageInfo["match_stage_distance"];

            $_match_stage_stop_time = date("Y-m-d H:i", $_matchStageInfo["match_stage_stop_time"]);
            $_final_result_time = $_language == "zh-CN" ? "比赛将于" . $_match_stage_stop_time . "公布最终结果" : "Final results will be announced on " . $_match_stage_stop_time;


            $_match_user = MatchsUserGrade::where([
                "matchs_stage_id" => $_matchStageInfo["matchs_stage_id"]
            ])->select("matchs_user_grade_id")->get();


            return array(
                "code" => 1,
                "is_end" => 1,
                "matchs_end_tips" => $_matchs_end_tips,
                "final_result_time" => $_final_result_time,
                "match_user_join_num" => count($_match_user),
                "all_distince_value" => $_all_distince_value,
                "all_distince_value_format" => round($_all_distince_value / 1000, 2)
            );
        }

//        赛段信息
        $_matchStageInfo = $_arrOfMathcStatge[0];

        $_residue_time = $_matchStageInfo["match_stage_stop_time"] - time();

//        当前赛段需要运动的距离
        $_all_distince_value = $_matchStageInfo["match_stage_distance"];


//        查询当前赛事报名信息
        $_arrOfSysMatch = SysMatch::where([
            "sys_match.sys_match_id" => $_sys_match_id,
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->join("matchs_user", function ($join) {
            $join->on("sys_match.sys_match_id", "=", "matchs_user.sys_match_id");
        })->where("matchs_user.stage_pass", "=", 1)->select(
            "matchs_user.sys_match_id", "matchs_user.user_id", "matchs_user.user_group_id", "matchs_user.is_group", "matchs_user.user_name"
            , "matchs_user.user_group_name", "matchs_user.user_group_finish_time", "matchs_user.stage_pass"
        )->get();

        if (count($_arrOfSysMatch) == 0) {
            Redis::select(1);
            $_matchs_end_tips = $_language == "zh-CN" ? Redis::hget("sys_setting", "match_stop_tips_zh") : Redis::hget("sys_setting", "match_stop_tips_en");


            $_match_stage_stop_time = date("Y-m-d H:i", $_matchStageInfo["match_stage_stop_time"]);
            $_final_result_time = $_language == "zh-CN" ? "比赛将于" . $_match_stage_stop_time . "公布最终结果" : "Final results will be announced on " . $_match_stage_stop_time;

            return array(
                "code" => 1,
                "is_end" => 1,
                "matchs_end_tips" => $_matchs_end_tips,
                "final_result_time" => $_final_result_time,
                "match_user_join_num" => count($_arrOfSysMatch),
                "all_distince_value" => $_all_distince_value,
                "all_distince_value_format" => round($_all_distince_value / 1000, 2)
            );
        }

        $_is_group = $_arrOfSysMatch[0]["is_group"];

        Redis::select(14);

        $_my_duration = 0;
        $_my_distance = 0;
        $_arrOfThisGroupData = array();
        $_arrOfAllGroup = array();

        foreach ($_arrOfSysMatch as $value) {

            $_redis_key = "";
//            团队赛
            if ($_is_group == 1) {
                $_redis_key = $_sys_match_id . ":" . $_matchStageInfo["matchs_stage_id"] . ":" . $value["user_group_id"];
            } else {
                $_redis_key = $_sys_match_id . ":" . $_matchStageInfo["matchs_stage_id"] . ":" . $value["user_id"];
            }
//            当前用户、团队的所有运动数据
            $_matchsPlayList = Redis::lrange($_redis_key, 0, -1);

//            $_user_
            $_arrOfReadyPlayDistince = 0;
            $_is_end = 0;
            if (count($_matchsPlayList) > 0) {
                foreach ($_matchsPlayList as $node) {
                    $_playData = json_decode(Redis::get($node), true);
                    $_arrOfReadyPlayDistince += $_playData["distance"];

//                未开始运动
                    if ($_playData["distance"] === 0 && isset($_playData["circle_detail"]) && count($_playData["circle_detail"]) > 0) {
                        $_arrOfReadyPlayDistince += round($_playData["circle_detail"][count($_playData["circle_detail"]) - 1] * StaticDataController::$_circle_distance / 100, 2);
                    }

                    if ($_playData["created_uid"] == $_usr_user["user_id"]) {

                        $_my_duration += isset($_playData["duration"]) ? $_playData["duration"] : 0;
                        $_my_distance += isset($_playData["distance"]) ? $_playData["distance"] : 0;
                    }
                }

                $_distance_poor = $_arrOfReadyPlayDistince - $_all_distince_value >= 0 ? "0km" : round(($_all_distince_value - $_arrOfReadyPlayDistince) / 1000, 2) . "km";
                $_distance_percentage = round($_arrOfReadyPlayDistince / $_all_distince_value, 2) >= 1 ? 1 : round($_arrOfReadyPlayDistince / $_all_distince_value, 2);

//            已运动的距离大于 运动的总距离，结束，
//              如果当前赛段结束时间，已超过当前时间，判定结束
                if ($_arrOfReadyPlayDistince >= $_all_distince_value || $_matchStageInfo["match_stage_stop_time"] < time()) {
                    $_is_end = 1;
//                  当前用户运动后，已触发赛事结束
                    //self::MatchsStagePlayStop($_sys_match_id,$value["user_group_id"],$_usr_user,$_matchStageInfo["matchs_stage_id"],$_is_group);
                    self::MatchsStagePlayStop($_sys_match_id, $value["user_group_id"], $_matchStageInfo["matchs_stage_id"], $_usr_user);
                }


            } else {
                $_distance_poor = round($_all_distince_value / 1000, 2) . "km";
                $_distance_percentage = 0;
            }

            $_arrOfAllGroupNode = array(
                "distince" => $_arrOfReadyPlayDistince,
                "distince_format" => round($_arrOfReadyPlayDistince / 1000, 2),
                "distance_percentage" => $_distance_percentage,
                "distance_poor" => $_distance_poor,
                "all_distince_value" => $_all_distince_value,
                "all_distince_value_format" => round($_all_distince_value / 1000, 2),
                "user_group_id" => $value["user_group_id"] == null ? "" : $value["user_group_id"],
                "user_group_name" => $value["user_group_name"] == null ? "" : $value["user_group_name"],
                "user_id" => $value["user_id"] == null ? "" : $value["user_id"],
                "user_name" => $value["user_name"] == null ? "" : $value["user_name"],
                "is_group" => $value["is_group"],
                "matchs_stage_id" => $_matchStageInfo["matchs_stage_id"],
                "this_user_group" => 0,
                "is_end" => $_is_end,
            );

            if (($_is_group == 1 && $value["user_group_id"] == $_user_group_id) || ($_is_group == 0 && $value["user_id"] == $_usr_user["user_id"])) {
                $_arrOfAllGroupNode["this_user_group"] = 1;
                if ($value["user_group_finish_time"] != null && $value["user_group_finish_time"] < time()) {
                    $_arrOfAllGroupNode["is_end"] = 1;
                }
                $_arrOfThisGroupData = $_arrOfAllGroupNode;
            }

            array_push($_arrOfAllGroup, $_arrOfAllGroupNode);
        }


//        当前用户所属团队没有数据，判定无权限进入本赛段
        if (!isset($_arrOfThisGroupData["is_end"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "no_join_match_stage")
            );
        }


//        完成挑战提示
        Redis::select(1);
        $_matchs_end_tips = $_language == "zh-CN" ? Redis::hget("sys_setting", "match_stop_tips_zh") : Redis::hget("sys_setting", "match_stop_tips_en");


        $_match_stage_stop_time = date("Y-m-d H:i", $_matchStageInfo["match_stage_stop_time"]);
        $_final_result_time = $_language == "zh-CN" ? "比赛将于" . $_match_stage_stop_time . "公布最终结果" : "Final results will be announced on " . $_match_stage_stop_time;

        if ($_matchStageInfo["match_stage_stop_time"] <= time()) {

            return array(
                "code" => 1,
                "is_end" => 1,
                "matchs_end_tips" => $_matchs_end_tips,
                "final_result_time" => $_final_result_time,
                "match_user_join_num" => "",
                "distince_format" => ""
            );
        }


//        排名当前所处排名计算
        $_arrOfMatchUserGrade = MatchsUserGrade::where([
            "matchs_stage_id" => $_matchStageInfo["matchs_stage_id"]
        ])->select(
            "match_ranking", "user_id", "user_group_id", "is_group"
        )->get();

//        总数
        $_count = count($_arrOfMatchUserGrade);
        $_count = $_count == 0 ? count($_arrOfSysMatch) : $_count;

//        排名
        $_ranking_index = 0;
        foreach ($_arrOfMatchUserGrade as $value) {
            if ($_is_group == 1 && $_user_group_id == $value["user_group_id"]) {
                $_ranking_index = $value["match_ranking"];
            }

            if ($_is_group == 0 && $_usr_user["user_id"] == $value["user_id"]) {
                $_ranking_index = $value["match_ranking"];
            }
        }
        $_ranking_index = $_ranking_index == 0 ? $_count : $_ranking_index;


        return array(
            "code" => 1,
            "count" => $_count,
            "index" => $_ranking_index,
            "ranking" => $_ranking_index . " / " . $_count,
            "distince" => $_arrOfThisGroupData["distince"],
            "distince_format" => $_arrOfThisGroupData["distince_format"],
            "distance_poor" => $_arrOfThisGroupData["distance_poor"],
            "distance_percentage" => $_arrOfThisGroupData["distance_percentage"],
            "all_distince_value" => $_all_distince_value,
            "all_distince_value_format" => $_arrOfThisGroupData["all_distince_value_format"],
            "matchs_stage_id" => $_arrOfThisGroupData["matchs_stage_id"],
            "is_end" => $_arrOfThisGroupData["is_end"],
            "matchs_end_tips" => $_matchs_end_tips,
            "final_result_time" => $_final_result_time,
            "user_group_name" => $_arrOfThisGroupData["user_group_name"],
            "match_user_join_num" => $_count,
            "my_duration" => round($_my_duration),
            "my_distance" => round($_my_distance / 1000, 2),
            "residue_time" => $_residue_time,
            "all_group" => $_show_all == 1 ? $_arrOfAllGroup : array()
        );
    }

    /**
     * @abstract 取消报名
     * @param Request $request
     * @return array
     */
    public function matchUserSignOut(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        MatchsUser::where([
            "user_id" => $_usr_user["user_id"],
            "sys_match_id" => $_data["sys_match_id"],
            "status" => 1
        ])->update([
            "is_join" => 0
        ]);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "update_success")
        );
    }

    /**
     * @abstract 取消报名  用户取消报名v2==新版赛事
     * @param Request $request
     * @return array
     */
    public function matchUserSignOutV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        if (!isset($_usr_user['sys_user_type_id']) || $_usr_user['sys_user_type_id'] !== "1809649560981504") {//验证游客不能参赛
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "please_log_in_to_enter"),
                "data" => array()
            );
        }

        //判断赛事是否结束
        $sysMatch = SysMatch::where('sys_match_id', $_data['sys_sys_match_id'])->first();
        if ($sysMatch['match_status'] == 2) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "cancel_failed_the_match_has_started"),
                "data" => array()
            );
        }
        if ($sysMatch['match_status'] == 3) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "the_match_is_over"),
                "data" => array()
            );
        }

        //获取赛段ID
        $matchsStageId = MatchsStage::where([
            "sys_match_id" => $_data["sys_match_id"],
            "sys_sys_match_id" => $_data["sys_sys_match_id"],
        ])->value('matchs_stage_id');
        $join_match_count = UserAchievement::where('user_id', $_usr_user["user_id"])->value('join_match_count');
        $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();
        try {
            DB::transaction(function () use ($_arrOfSysMatch, $join_match_count, $matchsStageId, $_usr_user, $_data) {
                MatchsUser::where([
                    "user_id" => $_usr_user["user_id"],
                    "sys_match_id" => $_data["sys_match_id"]
                ])->update([
                    "is_join" => 0
                ]);
                MatchsUserGrade::where([
                    "user_id" => $_usr_user["user_id"],
                    "matchs_stage_id" => $matchsStageId,
                ])->update([
                    "is_join" => 0
                ]);
                SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] - 1]);
                UserAchievement::where('user_id', $_usr_user["user_id"])->update(["join_match_count" => $join_match_count - 1]);
            }, 5);
        } catch (\Throwable $ex) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "cancel_error")
            );
        }
        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "cancel_success")
        );
    }

    /**
     * 排行榜v2（个人排行榜）==新版赛事
     * @return array
     * User: zxw
     * Date: 2021/10/15 9:17
     */
    public function matchPersonalLeaderboardV2(Request $request)
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;

        //修复iOS传参custom_distance错误的bug
        if ($_data['ranking_type'] == 'custom_distance'){
            $_data['ranking_type'] = 'marathon';
        }

//        用户年龄类型，0：成年榜，1：青年榜
//        $_user_age_type = isset($_data["user_age_type"]) ? $_data["user_age_type"] : 0;
        $_user_age_type = $_data["user_age_type"] ?? '';

        //个人与团队
        $_user_type = $_data['user_type'] ?? '';

        //城市
        $_address = $_data['address'] ?? '';

        //性别
        $_sys_sex_id = $_data['sys_sex_id'] ?? '';

        $_return_arr = RankController::matchPersonalLeaderboardV3($_user_age_type, $_data["ranking_type"], $_user_type, $_address, "app", $_page, $_limit, $_usr_user["user_id"], $_sys_sex_id, $_data['sys_match_id'], $_data['sys_sys_match_id']);

        return $_return_arr;
    }

    /**
     * 排行榜v2（团队标签列表排行榜）==新版赛事
     * @param Request $request
     * User: zxw
     * Date: 2021/10/15 10:29
     */
    public function matchTeamListLeaderboardV2(Request $request)
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;
        //修复iOS传参custom_distance错误的bug
        if ($_data['ranking_type'] == 'custom_distance'){
            $_data['ranking_type'] = 'marathon';
        }

        $_arrOfMatchsUserGradeQuery = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $_data['sys_match_id'],
            "matchs_stage.sys_sys_match_id" => $_data['sys_sys_match_id'],
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
        })->join("usr_user", function ($join) {
            $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
        });

        switch ($_data['ranking_type']) {
            case "exponent"://摇跑指数榜单
//                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->where('s_runball_exponent','<>',0)
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_img", "usr_user.user_name", "usr_user.user_id", "usr_user.address", "usr_user.sys_sex_id", 'matchs_user_grade.team_tag', 's_runball_exponent_time', DB::raw("AVG(IF(s_runball_exponent > 0,s_runball_exponent,null)) AS s_runball_exponent,SUM(IF(is_join=1,true,false)) AS join_sum"))
                    ->groupBy('matchs_user_grade.team_tag')
                    ->orderBy("s_runball_exponent", "DESC")
                    ->orderBy("s_runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
//                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->where('s_speed_max','<>',0)
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_img", "usr_user.user_name", "usr_user.user_id", "usr_user.address", "usr_user.sys_sex_id", 'matchs_user_grade.team_tag', 's_speed_max_time', DB::raw("AVG(IF(s_speed_max > 0,s_speed_max,null)) AS s_speed_max,SUM(IF(is_join=1,true,false)) AS join_sum"))
                    ->groupBy('matchs_user_grade.team_tag')
                    ->orderBy("s_speed_max", "DESC")
                    ->orderBy("s_speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
//                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->where('s_exponent_molecular','<>',0)
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_img", "usr_user.user_name", "usr_user.user_id", "usr_user.address", "usr_user.sys_sex_id", 'matchs_user_grade.team_tag', 's_exponent_molecular_time', DB::raw("AVG(IF(s_exponent_molecular>0,s_exponent_molecular,null)) AS s_exponent_molecular,SUM(IF(is_join=1,true,false)) AS join_sum"))
                    ->groupBy('matchs_user_grade.team_tag')
                    ->orderBy("s_exponent_molecular", "DESC")
                    ->orderBy("s_exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
//                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->where('s_marathon','<>',0)
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_img", "usr_user.user_name", "usr_user.user_id", "usr_user.address", "usr_user.sys_sex_id", 'matchs_user_grade.team_tag', 's_marathon_time', DB::raw("1/AVG(IF(s_marathon > 0,s_marathon,null)) AS s_marathon_asc,AVG(IF(s_marathon > 0,s_marathon,null)) AS s_marathon,SUM(IF(is_join=1,true,false)) AS join_sum"))
                    ->groupBy('matchs_user_grade.team_tag')
                    ->orderBy("s_marathon_asc", "DESC")
                    ->orderBy("s_marathon_time", "ASC");
                break;
        }

        $_arrOfMatchsUserGradeCount = $_arrOfMatchsUserGradeQuery->get();

        //TODO 修复前端分页bug...
        if ($_page == 1){
            $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->get();
        }else{
            $_arrOfMatchsUserGrade = [];
        }
//        $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->skip($_offset)->take($_limit)->get();


        $_return_arr = array();
        foreach ($_arrOfMatchsUserGrade as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";
            switch ($_data['ranking_type']) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)round($value["s_runball_exponent"], 2);
                    $_time = $_value > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                    break;
                case "max_speed"://个人最高速度
                    $_unit = "rpm";
                    $_value = (int)$value["s_speed_max"];
                    $_time = $_value > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                    break;
                case "onemin"://个人1分钟，m
//                    $_unit = "m";
//                    $_value = (string)$value["exponent_molecular"];
                    $_unit = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                    $_value = (string)round($value["s_exponent_molecular"] / 1000, 2);
                    $_time = $_value > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                    break;
                case "marathon"://个人马拉松
                    $_unit = "";
                    $_value = empty($value["s_marathon"]) ? 0 : (string)RankController::timeFormat($value["s_marathon"]);
                    $_time = $_value !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                    break;
            }
//
            array_push($_return_arr, array(
                "index" => $_offset + $key + 1,
                "team_tag" => $value['team_tag'],
                "value" => $_value,
                "unit" => $_unit,
                "time" => $_time,
                "join_sum" => $value['join_sum'],
            ));
        }

        //查询自己所在团队标签
        $teamTagData = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $_data['sys_match_id'],
            "matchs_stage.sys_sys_match_id" => $_data['sys_sys_match_id'],
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            "matchs_user_grade.user_id" => $_usr_user['user_id'],
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
        })->select('matchs_user_grade.team_tag', 'matchs_user_grade.user_id')
            ->first();

        $_my_ranking = 0;
        $my_ranking_info = null;
        if (empty($teamTagData)) {
            $my_ranking_info = null;
        } else {
            if ($_usr_user['user_id'] != null && $_usr_user['user_id'] != "") {
                foreach ($_arrOfMatchsUserGradeCount as $key => $value) {
                    if ($value["team_tag"] === $teamTagData['team_tag']) {
                        $_my_ranking = $key + 1;
                        $my_ranking_info['index'] = $_my_ranking;
                        $my_ranking_info['user_img'] = StaticDataController::$_server_url . "/" . $value['user_img'];
                        $my_ranking_info['user_name'] = $value['user_name'];
                        $my_ranking_info['user_id'] = $value['user_id'];
                        $my_ranking_info['address'] = $value['address'];
                        $my_ranking_info['sys_sex_id'] = $value['sys_sex_id'];
                        $my_ranking_info['team_tag'] = $value['team_tag'];
                        $my_ranking_info['join_sum'] = $value['join_sum'];

                        //            数据单位
                        $my_ranking_info['unit'] = "";
                        $my_ranking_info['value'] = "";
                        $my_ranking_info['time'] = "";
                        $_format = "Y-m-d H:i:s";
                        switch ($_data['ranking_type']) {
                            case "exponent"://摇跑指数
                                $my_ranking_info['unit'] = "";
                                $my_ranking_info['value'] = (string)round($value["s_runball_exponent"], 2);
                                $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_runball_exponent_time"]) : '';
                                break;
                            case "max_speed"://个人最高速度
                                $my_ranking_info['unit'] = "rpm";
                                $my_ranking_info['value'] = (int)$value["s_speed_max"];
                                $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_speed_max_time"]) : '';
                                break;
                            case "onemin"://个人1分钟，m
                                $my_ranking_info['unit'] = "km";
//                    $_value = (string)bcdiv($value["exponent_molecular"], 1000, 2);
                                $my_ranking_info['value'] = (string)round($value["s_exponent_molecular"] / 1000, 2);
                                $my_ranking_info['time'] = $my_ranking_info['value'] > 0 ? date($_format, $value["s_exponent_molecular_time"]) : '';
                                break;
                            case "marathon"://个人马拉松
                                $my_ranking_info['unit'] = "";
                                $my_ranking_info['value'] = empty($value["s_marathon"]) ? 0 : (string)RankController::timeFormat($value["s_marathon"]);
                                $my_ranking_info['time'] = $my_ranking_info['value'] !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                                break;
                        }
                    }
                }
            }
        }

        // 620128159022452736 625811269110206464 629938219168829440 629950601647624192
        // 初始化团队标签数组
        $teamTagArr = [];
        $specialMatchIds = [
            "620128159022452736",
            "625811269110206464",
            "629938219168829440",
            "629950601647624192",
            "629954686853582848",
            "629960528059437056",
            "629958758683906048",
            "629955522484768768",
            "629953394957619200",
            "629957567702896640",
            "663452139380543488"
        ];
        // 定义目标语言常量
        $targetLanguage = "zh-CN";

        // 检查是否为特定比赛且语言为中文
        if (in_array($_data['sys_sys_match_id'], $specialMatchIds) && $_language === $targetLanguage) {
            // 用团队标签作为键构建关联数组
            foreach ($_return_arr as $item) {
                $teamTagArr[$item['team_tag']] = $item;
            }
        
            // 定义需要展示的标签列表及顺序
            $tags = [
                '银发男士(60岁以上)', 
                '银发女士(55岁以上)', 
                '青年男士(18-59岁)', 
                '青年女士(18-54岁)', 
                '少年男生(12-17岁)', 
                '少年女生(12-17岁)'
            ];
            
            // 确保所有标签都有数据，缺失的用默认值填充
            foreach ($tags as $index => $tagName) {
                if (isset($teamTagArr[$tagName])) {
                    // 补充索引信息
                    $teamTagArr[$tagName]['index'] = $index + 1;
                } else {
                    // 添加默认数据
                    $teamTagArr[] = [
                        "index" => $index + 1,
                        "team_tag" => $tagName,
                        "value" => 0,
                        "unit" => "rpm",
                        "time" => date('Y-m-d H:i:s'),
                        "join_sum" => "0"
                    ];
                }
            }
            
            // 将数组重新索引为连续数字键
            $teamTagArr = array_values($teamTagArr);
        } else {
            $teamTagArr = $_return_arr;
        }
        
        return [
            "code" => 1,
            "msg" => "success",
            "data" => [
                "my_ranking" => $_my_ranking,
                "my_ranking_info" => empty($my_ranking_info) ? null : $my_ranking_info,
                "count" => count($_arrOfMatchsUserGradeCount),
                "list" => $teamTagArr
            ]
        ];
    }

    /**
     * 排行榜v2（团队标签详情排行榜）==新版赛事
     * @param Request $request
     * User: zxw
     * Date: 2021/10/15 10:29
     */
    public function matchTeamDetailsLeaderboardV2(Request $request)
    {
        Redis::select(1);
        $_data = request()->input();
        $first = null;
        $team_avg = null;
        $_unit = '';

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id']) || !isset($_data["team_tag"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }
        
        // sys_sys_match_id

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;
        //修复iOS传参custom_distance错误的bug
        if ($_data['ranking_type'] == 'custom_distance'){
            $_data['ranking_type'] = 'marathon';
        }

        $_arrOfMatchsUserGradeQuery = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $_data['sys_match_id'],
            "matchs_stage.sys_sys_match_id" => $_data['sys_sys_match_id'],
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            "matchs_user_grade.team_tag" => $_data["team_tag"],
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
        })->join("usr_user", function ($join) {
            $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
        });

        $_arrOfMatchsUserGradeQuery2 = $_arrOfMatchsUserGradeQuery;//用于求团队平均值

        switch ($_data['ranking_type']) {
            case "exponent"://摇跑指数榜单
                $first = $_arrOfMatchsUserGradeQuery2->first(DB::raw("AVG(IF(s_runball_exponent > 0,s_runball_exponent,null)) AS team_avg,SUM(IF(is_join=1,true,false)) AS join_sum"));
                $team_avg = (string)round($first['team_avg'], 2);
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.team_tag", "matchs_user_grade.s_runball_exponent"
                    , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
                    , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
                    , "matchs_user_grade.s_marathon_time", "usr_user.address", "usr_user.sys_sex_id", DB::raw("1000/matchs_user_grade.s_runball_exponent AS s_runball_exponent_asc")
                )->orderBy("s_runball_exponent", "DESC")->orderBy("s_runball_exponent_time", "DESC");
                break;
            case "max_speed"://个人最高速度
                $first = $_arrOfMatchsUserGradeQuery2->first(DB::raw("AVG(IF(s_speed_max > 0,s_speed_max,null)) AS team_avg,SUM(IF(is_join=1,true,false)) AS join_sum"));
                $team_avg = (int)$first['team_avg'];
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.team_tag", "matchs_user_grade.s_runball_exponent"
                    , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
                    , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
                    , "matchs_user_grade.s_marathon_time", "usr_user.address", "usr_user.sys_sex_id", DB::raw("10000/matchs_user_grade.s_speed_max AS s_speed_max_asc")
                )->orderBy("s_speed_max", "DESC")->orderBy("s_speed_max_time", "DESC");
                break;
            case "onemin"://个人1分钟数据
                $first = $_arrOfMatchsUserGradeQuery2->first(DB::raw("AVG(IF(s_exponent_molecular > 0,s_exponent_molecular,null)) AS team_avg,SUM(IF(is_join=1,true,false)) AS join_sum"));
                $team_avg = (string)round($first['team_avg'] / 1000, 2);
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.team_tag", "matchs_user_grade.s_runball_exponent"
                    , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
                    , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
                    , "matchs_user_grade.s_marathon_time", "usr_user.address", "usr_user.sys_sex_id", DB::raw("100/matchs_user_grade.s_exponent_molecular AS s_exponent_molecular_asc")
                )->orderBy("s_exponent_molecular", "DESC")->orderBy("s_exponent_molecular_time", "DESC");
                break;
            case "marathon"://个人全马
                $first = $_arrOfMatchsUserGradeQuery2->first(DB::raw("AVG(IF(s_marathon > 0,s_marathon,null)) AS team_avg,SUM(IF(is_join=1,true,false)) AS join_sum"));
                $team_avg = (int)$first['team_avg'] == 0 ? 0 : (string)RankController::timeFormat((int)$first['team_avg']);
                $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->select("usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "matchs_user_grade.team_tag", "matchs_user_grade.s_runball_exponent"
                    , "matchs_user_grade.s_speed_max", "matchs_user_grade.s_exponent_molecular", "matchs_user_grade.s_marathon"
                    , "matchs_user_grade.s_speed_max_time", "matchs_user_grade.s_runball_exponent_time", "matchs_user_grade.s_exponent_molecular_time"
                    , "matchs_user_grade.s_marathon_time", "usr_user.address", "usr_user.sys_sex_id", DB::raw("10000/matchs_user_grade.s_marathon AS s_marathon_asc")
                )->orderBy("s_marathon_asc", "DESC")->orderBy("s_marathon_time", "ASC");
                break;
        }

        $_arrOfMatchsUserGradeCount = $_arrOfMatchsUserGradeQuery->get();
        //TODO 修复前端分页bug...
//        if ($_page == 0) {
//            $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->get();
//        }else{
//            $_arrOfMatchsUserGrade = [];
//        }
        $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->skip($_offset)->take($_limit)->get();

        $_return_arr = array();
        foreach ($_arrOfMatchsUserGrade as $key => $value) {
//            数据单位
            $_unit = "";
            $_value = "";
            $_time = "";
            $_format = "Y-m-d H:i:s";

            switch ($_data['ranking_type']) {
                case "exponent"://摇跑指数
                    $_unit = "";
                    $_value = (string)round($value["s_runball_exponent"], 2);
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
                    $_value = empty($value["s_marathon"]) ? 0 : (string)RankController::timeFormat($value["s_marathon"]);
                    $_time = $_value !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                    $_time_unix = $_value !== 0 ? $value["s_marathon_time"] : '';
                    break;
            }
//
            array_push($_return_arr, array(
                "team_tag" => $value['team_tag'],
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
                "team_avg" => $team_avg,
                "s_marathon_asc" => $value['s_marathon_asc']
            ));
        }

        //查询自己所在团队标签
        $teamTagData = MatchsUserGrade::where([
            "matchs_stage.sys_match_id" => $_data['sys_match_id'],
            "matchs_stage.sys_sys_match_id" => $_data['sys_sys_match_id'],
            "matchs_user_grade.is_quartets" => 1,
            "matchs_user_grade.is_join" => 1,
            "matchs_user_grade.user_id" => $_usr_user['user_id'],
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_user_grade.matchs_stage_id", "=", "matchs_stage.matchs_stage_id");
        })->select('matchs_user_grade.team_tag', 'matchs_user_grade.user_id')
            ->first();

        $_my_ranking = 0;
        $my_ranking_info = null;
        if (empty($teamTagData)) {
            $my_ranking_info = null;
        } else {
            if ($_usr_user['user_id'] != null && $_usr_user['user_id'] != "") {
                foreach ($_arrOfMatchsUserGradeCount as $key => $value) {
                    if ($value["user_id"] == $_usr_user['user_id']) {
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
                        switch ($_data['ranking_type']) {
                            case "exponent"://摇跑指数
                                $my_ranking_info['unit'] = "";
                                $my_ranking_info['value'] = (string)round($value["s_runball_exponent"], 2);
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
                                $my_ranking_info['value'] = empty($value["s_marathon"]) ? 0 : (string)RankController::timeFormat($value["s_marathon"]);
                                $my_ranking_info['time'] = $my_ranking_info['value'] !== 0 ? date($_format, $value["s_marathon_time"]) : '';
                                $my_ranking_info['time_unix'] = $my_ranking_info['value'] > 0 ? $value["s_marathon_time"] : '';
                                break;
                        }
                    }
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "my_ranking" => $_my_ranking,
                "team_avg" => $team_avg,
                "unit" => $_unit,
                "join_sum" => empty($first['join_sum']) ? null : $first['join_sum'],
                "my_ranking_info" => empty($my_ranking_info) ? null : $my_ranking_info,
                "count" => count($_arrOfMatchsUserGradeCount),
                "list" => $_return_arr,
            )
        );

    }

    /**
     * @abstract 用户关注赛事，取消关注
     * @param Request $request
     * @return array
     */
    public function matchUserLike(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_arrOfMatchUser = MatchsUser::where([
            "user_id" => $_usr_user["user_id"],
            "sys_match_id" => $_data["sys_match_id"],
            "status" => 1,
        ])->select("matchs_user_id", "is_like")->get();

        if (count($_arrOfMatchUser) == 1) {
            $_is_like = 1;
            $_msg = LanguageController::getLanguage($_language, "like_success");
            if ($_arrOfMatchUser[0]["is_like"] == 1) {
                $_is_like = 0;
                $_msg = LanguageController::getLanguage($_language, "unlike_success");
            }

            MatchsUser::where([
                "matchs_user_id" => $_arrOfMatchUser[0]["matchs_user_id"]
            ])->update(["is_like" => $_is_like]);


            return array(
                "code" => 1,
                "msg" => $_msg,
                "data" => $_is_like
            );
        } else {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "like_error")
            );
        }

    }


    /**
     * @author pengjl
     * @time 2021/5/12 16:56
     * @abstract _用户查询赛事详情
     */
    public static function SysMatchUserInfo($_arrOfSysMatchInfo, $_language, $_sys_match_id, $_token)
    {

        $_arrOfSysMatchInfo["user_join_status"] = self::UserIsJoinMatch($_sys_match_id, $_arrOfSysMatchInfo["is_group"], $_token);

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
                "value" => date("Y.m.d", $_arrOfSysMatchInfo["match_start_time"]) . " - " . date("Y.m.d", $_arrOfSysMatchInfo["match_stop_time"]),
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/time.png",
                "is_html" => 0,
                "value_start_unix" => $_arrOfSysMatchInfo["match_start_time"],
                "value_stop_unix" => $_arrOfSysMatchInfo["match_stop_time"]
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "event_rewards"),
                "value" => $_arrOfSysMatchInfo["match_champion_prize_description"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/bonus.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "entry_type"),
                "value" => $_arrOfSysMatchInfo["is_group"] == 0 ? ($_language == "zh-CN" ? ($_sys_match_id == "519615335549112320" ? "班级组队" : "个人参赛") : "Person") : ($_language == "zh-CN" ? "团队参赛" : "Group"),
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/entrytype.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_group"),
                "value" => "<div style='color:#767779'>" . $_arrOfSysMatchInfo["join_status"] . "</div>",
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/require.png",
                "is_html" => 1
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_user"),
                "value" => $_arrOfSysMatchInfo["match_user_sign_count"],
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/join.png",
                "is_html" => 0
            ),
            array(
                "label" => LanguageController::getLanguage($_language, "match_description"),
                "value" => "<div style='color:#767779'>" . $_arrOfSysMatchInfo["match_description"] . "</div>",
                "icon" => StaticDataController::$_server_url . "/matchs_image/matchs_sources/info.png",
                "is_html" => 1
            ),
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

        if ($_arrOfSysMatchInfo['matchs_event_type_id'] == SettingMessage::matchs_event_type_id) {//是否为四项赛事
            $_arrOfSysMatchInfo['is_quartets'] = 1;
        } else {
            $_arrOfSysMatchInfo['is_quartets'] = 0;
        }

        $_arrOfMatchInfo = array(
            "sys_sys_match_id" => $_arrOfSysMatchInfo["sys_sys_match_id"],
            "sys_match_id" => $_arrOfSysMatchInfo["sys_match_id"],
            "matchs_event_type_id" => $_arrOfSysMatchInfo["matchs_event_type_id"],
            "match_events_type_title" => $_arrOfSysMatchInfo["match_events_type_title"],
            "match_start_time" => $_arrOfSysMatchInfo["match_start_time"],
            "match_stop_time" => $_arrOfSysMatchInfo["match_stop_time"],
            "is_quartets" => $_arrOfSysMatchInfo['is_quartets'],
            "match_title" => $_arrOfSysMatchInfo["match_title"],
            "sys_match" => $_arrOfSysMatchInfo['sys_match'],
            "match_status" => $_arrOfSysMatchInfo["match_status"],
            "match_status_title" => $_match_status_title,
            "user_join_status" => $_arrOfSysMatchInfo["user_join_status"],
            "match_join_pass" => $_match_join_pass,
            "is_group" => $_arrOfSysMatchInfo["is_group"],
            "match_image" => StaticDataController::$_server_url . "/" . $_arrOfSysMatchInfo["match_image"],
            "form_array" => array_values($_form_array),
            "team_name" => $_arrOfSysMatchInfo['team_name'],
            "quartets_icon" => $_arrOfSysMatchInfo["quartets_icon"],
            "ranking_type_list" => $_arrOfSysMatchInfo["ranking_type_list"],
            "speed_duration" => $_arrOfSysMatchInfo["speed_duration"],
            "external_url" => $_arrOfSysMatchInfo["external_url"] ?? "",
        );

        return $_arrOfMatchInfo;

    }


    /**
     * @author pengjl
     * @time 2021/5/12 17:00
     * @abstract _验证用户是否已加入比赛
     */
    public static function UserIsJoinMatch($_sys_match_id, $_is_group, $_token)
    {

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);


//        个人参赛
        if ($_is_group == 0) {

//        查询用户报名表
            $_arrOfUserMatch = MatchsUser::where([
                "matchs_user.user_id" => $_usr_user["user_id"],
                "matchs_user.sys_match_id" => $_sys_match_id,
                "matchs_user.status" => 1,
                "matchs_user.is_join" => 1
            ])->join("sys_match", function ($join) {
                $join->on("matchs_user.sys_match_id", "=", "sys_match.sys_match_id");
            })->select("matchs_user.sys_match_id", "matchs_user.matchs_user_id")->get();


//            已报名
            if (count($_arrOfUserMatch) == 1) {
                return array(
                    "is_join" => 1,
                    "user_group_id" => "",
                    "group_title" => "",
                    "group_num" => "",
                );
            } else {
                return array(
                    "is_join" => 0,
                    "user_group_id" => "",
                    "group_title" => "",
                    "group_num" => "",
                );
            }

        } else {
//            团队参赛

//            查询已报名的团队
            $_arrOfMatchUser = MatchsUser::where([
                "matchs_user.status" => 1,
                "user_group_associated.status" => 1,
                "matchs_user.is_group" => 1,
                "matchs_user.is_join" => 1,
                "matchs_user.sys_match_id" => $_sys_match_id,
                "user_group_associated.user_id" => $_usr_user["user_id"],
            ])->join("user_group_associated", function ($join) {
                $join->on("matchs_user.user_group_id", "=", "user_group_associated.user_group_id");
            })->join("user_group", function ($join) {
                $join->on("user_group_associated.user_group_id", "=", "user_group.user_group_id");
            })->select("matchs_user.matchs_user_id", "user_group.user_group_id", "user_group.group_title", "user_group.group_num")
                ->distinct("user_group.user_group_id")->get();

//            return $_arrOfMatchUser;

//            已报名
            if (count($_arrOfMatchUser) >= 1) {
                return array(
                    "is_join" => 1,
                    "user_group_id" => $_arrOfMatchUser[0]["user_group_id"],
                    "group_title" => $_arrOfMatchUser[0]["group_title"],
                    "group_num" => $_arrOfMatchUser[0]["group_num"],
                );
            } else {
                return array(
                    "is_join" => 0,
                    "user_group_id" => "",
                    "group_title" => "",
                    "group_num" => "",
                );
            }
        }

    }


    /**
     * @author pengjl
     * @time 2021/5/20 16:22
     * @abstract _用户运动到赛段结束
     */
    public static function MatchStageEnd($_sys_match_id, $_user_group_id, $_usr_user, $_is_group)
    {

        if ($_is_group == 1) {
            MatchsUser::where([
                "sys_match_id" => $_sys_match_id,
                "user_group_id" => $_user_group_id
            ])->whereNull("user_group_finish_time")
                ->update([
                    "user_group_finish_time" => time()
                ]);
        } else {
            MatchsUser::where([
                "sys_match_id" => $_sys_match_id,
                "user_id" => $_usr_user["user_id"]
            ])->whereNull("user_group_finish_time")
                ->update([
                    "user_group_finish_time" => time()
                ]);
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/20 17:07
     * @abstract _参与赛事的运动结束
     */
    public static function MatchsStagePlayStop($_sys_match_id, $_user_group_id, $_matchs_stage_id, $_usr_user)
    {


//        "matchs_stage_stop:52210494135603200:52210838106279936"
        Log::info("matchs_stage_stop:" . $_sys_match_id . "_" . $_matchs_stage_id);
        Redis::select(14);

        $_is_group = $_user_group_id != "" ? 1 : 0;
        $_redis_key = "";
//            团队赛
        if ($_is_group == 1) {
            $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $_user_group_id;
        } else {
            $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $_usr_user["user_id"];
        }
//            当前用户、团队的所有运动数据
        $_matchsPlayList = Redis::lrange($_redis_key, 0, -1);

        $_duration = 0;
        if (count($_matchsPlayList) > 0) {
            foreach ($_matchsPlayList as $node) {
                $_playData = json_decode(Redis::get($node), true);
                $_duration += isset($_playData["duration"]) ? $_playData["duration"] : 0;
            }
        }

        Log::info("redis_key:" . $_redis_key);
        Log::info("match_stage_result:" . $_duration);


        if ($_is_group == 1) {
            MatchsUserGrade::where([
                "matchs_stage_id" => $_matchs_stage_id,
                "user_group_id" => $_user_group_id
            ])->update([
                "match_grade" => $_duration,
                "match_play_data" => json_encode($_matchsPlayList)
            ]);

        } else {
            MatchsUserGrade::where([
                "matchs_stage_id" => $_matchs_stage_id,
                "user_id" => $_usr_user["user_id"]
            ])->update([
                "match_grade" => $_duration,
                "match_play_data" => json_encode($_matchsPlayList)
            ]);

        }

        $_arrOfMatchUserGradeData = MatchsUserGrade::where([
            "matchs_user_grade.matchs_stage_id" => $_matchs_stage_id,
            "matchs_stage.status" => 1,
            "matchs_stage.matchs_stage_status" => 2,
        ])->join("matchs_stage", function ($join) {
            $join->on("matchs_stage.matchs_stage_id", "=", "matchs_user_grade.matchs_stage_id");
        })->select("matchs_user_grade.match_grade", "matchs_user_grade.matchs_user_grade_id")
            ->orderBy("matchs_user_grade.match_grade", "ASC")->get();


        foreach ($_arrOfMatchUserGradeData as $key => $value) {
            MatchsUserGrade::where([
                "matchs_user_grade_id" => $value["matchs_user_grade_id"]
            ])->update([
                "match_ranking" => $key + 1
            ]);
        }

        return array();

    }


    /**
     * 获取GO页面赛事hot轮播
     * @param Request $request
     * @return array
     * User: zxw
     * Date: 2021/10/16 18:13
     */
    public function getGoMatchHot(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

//        if( $_token == null ){
//            return array(
//                "code"=>0,
//                "msg"=>LanguageController::getLanguage($_language,"lack_token"),
//                "data"=>array()
//            );
//        }

        $sysMatch = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
//            "sys_match.matchs_event_type_id"=>$_data['match_event_id']
//            "sys_match.matchs_event_type_id"=> SettingMessage::matchs_event_type_id,//摇跑四项赛
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->select(
            "sys_match.sys_match_id", "sys_sys_match.sys_match_id as sys_sys_match_id", "sys_sys_match.match_title"
        )
            ->where('sys_sys_match.match_status', 2)
            ->orderBy("sys_sys_match.is_hot", "DESC")
            ->orderBy("sys_sys_match.match_start_time", "DESC")
            ->limit(15)
            ->get();

        return [
            "code" => 1,
            "msg" => "success",
            "data" => [
                "count" => count($sysMatch),
                "list" => [],// $sysMatch
            ]
        ];
    }

    /**
     * 赛事列表统计个状态的数量
     * @param Request $request
     * @return array
     * User: zxw
     * Date: 2021/10/17 16:00
     */
    public function getMatchNum(Request $request)
    {
//        当前已发布，未结束的赛事列表
        $matchNum = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
//            "sys_match.matchs_event_type_id"=>$_data['match_event_id']
//            "sys_match.matchs_event_type_id"=> SettingMessage::matchs_event_type_id,//摇跑四项赛
        ])->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->select(DB::raw("COUNT(IF(sys_sys_match.match_status=1,true,null)) AS type1,COUNT(IF(sys_sys_match.match_status=2,true,null)) AS type2,COUNT(IF(sys_sys_match.match_status=3,true,null)) AS type3")
        )->first();
        $matchNum = $matchNum ?? ["type1" => 0, "type2" => 0, "type3" => 0,];
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $matchNum,
        );
    }

    /**
     * 我的报名赛事列表v2==新版赛事
     * @return array
     * User: zxw
     * Date: 2021/10/17 16:01
     */
    public function matchUserListV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';


        $_token = $request->header('token');
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token"),
                "data" => array()
            );
        }
        $team_name = SettingMessage::team_name;

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

//        当前已发布，未结束的赛事列表
        $_arrOfMatchListQuery = SysMatch::where([
            "sys_sys_match.status" => 1,
            "sys_match.status" => 1,
            "user_id" => $_usr_user['user_id']
//            "sys_match.matchs_event_type_id"=>$_data['match_event_id']
//            "sys_match.matchs_event_type_id"=> SettingMessage::matchs_event_type_id,//摇跑四项赛
        ])
            ->join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })->join("matchs_user", function ($join) {
            $join->on("sys_match.sys_match_id", "=", "matchs_user.sys_match_id");
            $join->on("sys_match.sys_sys_match_id", "=", "matchs_user.sys_sys_match_id");
        })->select(
            "sys_match.sys_match_id", "sys_match.match_champion_prize_description", "sys_sys_match.match_title"
            , "sys_sys_match.match_user_type_description", "sys_sys_match.match_user_sex_description", "sys_sys_match.match_start_time"
            , "sys_sys_match.match_stop_time", "sys_match.status", "sys_sys_match.match_user_sign_count", "sys_sys_match.match_status"
            , "sys_sys_match.is_group", "sys_sys_match.sys_match_id as sys_sys_match_id", "sys_sys_match.match_image"
            , "sys_sys_match.match_image_list", "sys_sys_match.join_status", "sys_sys_match.is_hot", "sys_match.matchs_event_type_id"
        )->orderBy("sys_sys_match.is_hot", "DESC")
            ->orderBy("sys_sys_match.match_start_time", "DESC");

        $_arrOfMatchListCount = $_arrOfMatchListQuery->count();
        $_arrOfMatchList = $_arrOfMatchListQuery->skip($_offset)->take($_limit)->get();

        if (count($_arrOfMatchList) == 0) {
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "none_data"),
                "data" => array(
                    "count" => 0,
                    "list" => array()
                )
            );
        }

        $_arrOfMatchListKey = array();
        $_arrOfMatchListId = array();
        foreach ($_arrOfMatchList as $key => $value) {
            $value["start_time"] = date("Y.m.d H:i", $value["match_start_time"]);
            $value["stop_time"] = date("Y.m.d H:i", $value["match_stop_time"]);

            $value["pass_join"] = $value["match_start_time"] < time() ? 1 : 0;

            if ($value['matchs_event_type_id'] == SettingMessage::matchs_event_type_id) {//是否为四项赛事
                $value['is_quartets'] = 1;
                $value["team_name"] = $team_name;
            } else {
                $value['is_quartets'] = 0;
                $value["team_name"] = "";
            }

            switch ($value["match_status"]) {
                case 1:
                    $value["match_status_title"] = $_language == "zh-CN" ? "未开始" : "Soon";
                    break;
                case 2:
                    $value["match_status_title"] = $_language == "zh-CN" ? "比赛中" : "Runing";
                    break;
                case 3:
                    $value["match_status_title"] = $_language == "zh-CN" ? "已结束" : "End";
                    break;
            }

            $value["user_join_status"] = self::UserIsJoinMatch($value["sys_match_id"], $value["is_group"], $_token);

            $value["matchs_stage_id"] = "";
            $value["view_type"] = 0;

            if (strpos($value["match_image"], 'http') === false) {
                $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                if ($value["match_image_list"] == "" || $value["match_image_list"] == null) {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];
                } else {
                    $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
                }
            }

            if (strpos($value["match_image_list"], 'http') === false) {
                $value["match_image_list"] = StaticDataController::$_server_url . "/" . $value["match_image_list"];
            }


            $_arrOfMatchListKey[$value["sys_match_id"]] = $value;
            array_push($_arrOfMatchListId, $value["sys_match_id"]);
        }

//        查询赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->whereIn("sys_match_id", $_arrOfMatchListId)->select(
            "matchs_stage_id", "view_type", "sys_match_id", "match_stage_start_time", "match_stage_stop_time"
        )->get();


        foreach ($_arrOfMatchStage as $key => $value) {
            if ($value["match_stage_start_time"] < time() && $value["match_stage_stop_time"] > time()) {
                $_arrOfMatchListKey[$value["sys_match_id"]]["matchs_stage_id"] = $value["matchs_stage_id"];
                $_arrOfMatchListKey[$value["sys_match_id"]]["view_type"] = $value["view_type"];
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchListCount,
                "list" => array_values($_arrOfMatchListKey)
            )
        );
    }

    /**
     * 获取报名团队标签
     * @param Request $request
     * @return JsonResponse
     * User: zxw
     * Date: 2021/10/13 17:18
     */
    public function getSignUpTeamTag(Request $request): JsonResponse
    {
        $data = $request->all();
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        $data['sys_match_id'] = $data['sys_match_id'] ?? 0;
        if ($data['language'] != 'zh-CN') {
            $list = SysTeamTag::where('sys_match_id', $data['sys_match_id'])->pluck('en_team_tag');
        } else {
            $list = SysTeamTag::where('sys_match_id', $data['sys_match_id'])->pluck('team_tag');
        }
        count($list) > 0 ? '' : $list = SettingMessage::TEAM_TAG_ZH_CN;
        return $this->success($list);
    }

    /**
     * 例赛==获取例赛列表
     * @param MatchGetRegularSeasonListRequest $request
     * @param MatchV2Service $service
     * @return JsonResponse
     * @throws BusinessException
     */
    public function getRegularSeasonList(MatchGetRegularSeasonListRequest $request, MatchV2Service $service): JsonResponse
    {
        $data = $request->all();
        $data['token'] = $request->header('token');
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $data['token'] == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $data['token']), true);
        $data['user_id'] = $_usr_user['user_id'];

        switch ($data['s_type']){
            case 1://获取例赛列表
                $list = $service->getRegularSeasonList($data);
                break;
            case 2://获取我的勛章列表
                $list = $service->getMatchsAwardList($data);
                break;
            case 3://获取我的賽點列表
                $list = $service->getMatchPointList($data);
                break;
            default:
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }

        return $this->success(data_list_format($list));
    }

    /**
     * 赛事==获取我的赛事列表
     * @param MatchGetMyRegularSeasonListRequest $request
     * @param MatchV2Service $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/8 14:15
     * @throws BusinessException
     */
    public function getMyMatchList(MatchGetMyRegularSeasonListRequest $request, MatchV2Service $service): JsonResponse
    {
        $data = $request->all();
        $data['token'] = $request->header('token');
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $data['token'] == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $data['token']), true);
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->getMyAllMatchList($data);

        return $this->success(data_list_format($list));
    }


    /**
     * 数据上传接口切换v2/v3
     * @return array
     */
    public function getPalyUrl()
    {

        $redisName = 'SHAKEINFO-148578263902457856';
        Redis::select(14);
        $rediss = json_decode(Redis::get($redisName));
//        Redis::Expire($redisName, 100000);
        dd($rediss);
        $rediss['start_time'] = 1645182000;
        $rediss['stop_time'] = 1645245000;
        dd($rediss);
        Redis::set($redisName, json_encode($rediss));
        Redis::Expire($redisName, 100000);
        return $this->success($rediss);



//        phpinfo();die();
//        $usr = UsrUser::get();
//return $this->success($usr);
//        ini_set('date.timezone','America/New_York');
        $a = date('Y-m-d H:i:s');
        $b = date('Y-m-d H:i:s', 1645048800);
        $c = time();

        $time1 = strtotime(date('Y-m-d 06:00:00'));
//        date_default_timezone_set(env('APP_TIMEZONE'));
        $time2 = strtotime(date('Y-m-d H:i:s',time()));
        $time3 = date('Y-m-d H:i:s');
        $time4 = Carbon::now()->toDateTimeString();
        dd($a,$b,$c,$time1,$time2,$time3,$time4);
        return SettingMessage::GET_APP_V_URL;
    }




}
