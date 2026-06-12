<?php


namespace App\Http\Controllers\Admin;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\fileMoveController;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\MatchsBanner;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\SysMatch;
use App\Models\SysSex;
use App\Models\SysTeamTag;
use App\Models\SysUserType;
use App\Models\UserAchievement;
use App\Models\UsrUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MatchsController extends Controller
{

    /**
     * @abstract 上传赛事相关图片
     * @return array
     */
    public function getMatchsUpload(): array
    {
        if (!isset($_FILES["file"])) {
            return array(
                "code" => 0,
                "msg" => "未获取到文件"
            );
        }
        $_file = $_FILES["file"];

//        文件路径处理、移动文件
        $_file_path = fileMoveController::getFilePath("matchs_image", $_file["name"]);
        move_uploaded_file($_file["tmp_name"], $_file_path["file_path"]);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "matchs_img_path" => StaticDataController::$_server_url . '/' . $_file_path["file_path"],
                'file_path' => $_file_path
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/24 15:34
     * @abstract _APP赛事轮播图列表
     */
    public function matchBannerList(Request $request)
    {

        $_data = $request->input();

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_arrOfMatchsBannerQuery = MatchsBanner::where([
            "status" => 1,
        ])->select(
            "matchs_banner_id", "img_path", "banner_matchs_id"
        );

        $_arrOfMatchsBannerCount = $_arrOfMatchsBannerQuery->count();
        $_arrOfMatchsBanner = $_arrOfMatchsBannerQuery->skip($_offset)->take($_limit)->get();

        $_sys_matchs_id = array();
        foreach ($_arrOfMatchsBanner as $key => $value) {
            $value["match_title"] = "";
            array_push($_sys_matchs_id, $value["banner_matchs_id"]);
        }

        $_arrOfMatchs = SysMatch::where("sys_match.status", ">", -1)
            ->join("sys_match as sys_sys_match", function ($join) {
                $join->on("sys_match.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
            })->join("matchs_event_type", function ($join) {
                $join->on("sys_match.matchs_event_type_id", "=", "matchs_event_type.matchs_event_type_id");
            })
            ->whereIn("sys_match.sys_match_id", $_sys_matchs_id)
            ->select("sys_match.sys_match_id", "sys_sys_match.match_title", "matchs_event_type.match_events_type_title")->get();
        $_arrOfMatchsKey = array();
        foreach ($_arrOfMatchs as $value) {
            $_arrOfMatchsKey[$value["sys_match_id"]] = $value;
        }


        foreach ($_arrOfMatchsBanner as $key => $value) {
            if ($value["banner_matchs_id"] != "" && $value["banner_matchs_id"] != null && array_key_exists($value["banner_matchs_id"], $_arrOfMatchsKey)) {
                $value["match_title"] = $_arrOfMatchsKey[$value["banner_matchs_id"]]["match_title"] . "_" . $_arrOfMatchsKey[$value["banner_matchs_id"]]["match_events_type_title"];
            }

            $value["img_path"] = StaticDataController::$_server_url . "/" . $value["img_path"];

            $_arrOfMatchsBanner[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchsBannerCount,
                "list" => $_arrOfMatchsBanner
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/24 15:59
     * @abstract _创建，编辑
     */
    public function matchBannerAdd(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["img_path"])) {
            return SystemErrorController::paramtersError($_language);
        }
        $_img_path = $_data["img_path"];
        $_base_url = StaticDataController::$_server_url . "/";
        $_img_path = str_replace($_base_url, "", $_img_path);

        $_arrOfMatchBannerData = array(
            "status" => 1,
            "img_path" => $_img_path
        );

        if (isset($_data["banner_matchs_id"])) {
            $_arrOfMatchBannerData["banner_matchs_id"] = $_data["banner_matchs_id"];
        }


        if (isset($_data["matchs_banner_id"])) {
            $_arrOfMatchBannerData["updated_uid"] = $_admin_user["admin_user_id"];

            MatchsBanner::where(["matchs_banner_id" => $_data["matchs_banner_id"]])->update($_arrOfMatchBannerData);

            return array(
                "code" => 1,
                "msg" => "编辑成功"
            );
        } else {
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfMatchBannerData["created_uid"] = $_admin_user["admin_user_id"];

            $_arrOfMatchBannerData["matchs_banner_id"] = $_sno->nextId();

            MatchsBanner::create($_arrOfMatchBannerData);

            return array(
                "code" => 1,
                "msg" => "创建成功"
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/5/24 16:01
     * @abstract _删除轮播图
     */
    public function matchBannerDelete(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';


        MatchsBanner::where([
            "matchs_banner_id" => $_data["matchs_banner_id"]
        ])->update([
            "status" => 0,
            "updated_uid" => $_admin_user["admin_user_id"]
        ]);


        return array(
            "code" => 1,
            "msg" => "删除成功"
        );
    }

    /**
     * @author pengjl
     * @time 2021/5/11 14:58
     * @abstract _用户创建赛事
     */
    public function getMatchsAdd(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        $_start_date = isset($_data['matchs_start_date']) ? strtotime($_data['matchs_start_date']) : time();
        $_start_time = isset($_data['matchs_start_time']) ? strtotime($_data['matchs_start_time']) : time();
        $_stop_date = isset($_data['matchs_stop_date']) ? strtotime($_data['matchs_stop_date']) : time();
        $_stop_time = isset($_data['matchs_stop_time']) ? strtotime($_data['matchs_stop_time']) : time();
        $_join_date = isset($_data['matchs_join_date']) ? strtotime($_data['matchs_join_date']) : time();
        $_join_time = isset($_data['matchs_join_time']) ? strtotime($_data['matchs_join_time']) : time();


//        赛事开始时间
        $_matchs_start_time = strtotime(date("Y-m-d", $_start_date) . " " . date("H:i:s", $_start_time));
//        赛事结束时间
        $_matchs_stop_time = strtotime(date("Y-m-d", $_stop_date) . " " . date("H:i:s", $_stop_time));
//        赛事开始报名时间
        $_matchs_join_time = strtotime(date("Y-m-d", $_join_date) . " " . date("H:i:s", $_join_time));


//        赛事说明
        $_matchs_content = isset($_data["matchs_content"]) ? $_data["matchs_content"] : "";
//        赛事说明-英文
        $_matchs_content_en = isset($_data["matchs_content_en"]) ? $_data["matchs_content_en"] : "";
//        联系电话
        $_matchs_phone = isset($_data["matchs_phone"]) ? $_data["matchs_phone"] : "";
//        联系电话前缀
        $_match_phone_prefix = isset($_data["prefix"]) ? $_data["prefix"] : '86';
//        邮箱
        $_matchs_email = isset($_data["matchs_email"]) ? $_data["matchs_email"] : '';
//        赛事标题
        $_matchs_title = isset($_data["match_title"]) ? $_data["match_title"] : "";
//        赛事标题-英文
        $_matchs_title_en = isset($_data["match_title_en"]) ? $_data["match_title_en"] : "";
//        允许参赛的用户类型
        $_matchs_user_type = isset($_data["sys_user_type_list_value"]) ? $_data["sys_user_type_list_value"] : array();
//        允许参赛的用户性别
        $_matchs_user_sex = isset($_data["sys_user_sex_list_value"]) ? $_data["sys_user_sex_list_value"] : array();
//        赛事宣传图
        $_match_image = isset($_data["match_image"]) ? $_data["match_image"] : "";
//        赛事宣传图
        $_match_image_list = isset($_data["match_image_list"]) ? $_data["match_image_list"] : "";
//        赛事类型
        $_matchs_type_id = isset($_data['matchs_type_id']) ? $_data["matchs_type_id"] : "";

//        赛事ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

//        是否为团队赛
        $_is_group = isset($_data["is_group"]) ? $_data["is_group"] : 0;

//        报名限制条件
        $_join_status = isset($_data["join_status"]) ? $_data["join_status"] : 0;
//        是否在官网显示
        $_website_show = isset($_data["website_show"]) ? $_data["website_show"] : 1;
//        赛事项目类型
        $_matchs_event_type_id = $_data["matchs_event_type_id"] ?? 102231304350732288;

        $_base_url = StaticDataController::$_server_url . "/";
        $_match_image = str_replace($_base_url, "", $_match_image);
        $_match_image_list = str_replace($_base_url, "", $_match_image_list);


        // 查询用户类型，性别数据
        $_arrOfSysUserSex = SysSex::where(["status" => 1])->select("sys_sex_id", "sex_name")->get();
        $_arrOfSysUserType = SysUserType::where(["status" => 1])->select("sys_user_type_id", "user_type_name")->get();
        $_arrOfSysUserSexKey = array();
        foreach ($_arrOfSysUserSex as $value) {
            $_arrOfSysUserSexKey[$value["sys_sex_id"]] = $value["sex_name"];
        }
        $_arrOfSysUserTypeKey = array();
        foreach ($_arrOfSysUserType as $value) {
            $_arrOfSysUserTypeKey[$value["sys_user_type_id"]] = $value["user_type_name"];
        }

        $_match_status = 1;
        if ($_matchs_start_time < time()) {
            $_match_status = 2;
        }

        $_arrOfMatchs = array(
            "match_title" => $_matchs_title,
            "match_title_en" => $_matchs_title_en,
            "match_description" => $_matchs_content,
            "match_description_en" => $_matchs_content_en,
            "match_phone" => $_matchs_phone,
            "match_email" => $_matchs_email,
            "match_status" => $_match_status,
            "is_group" => $_is_group,
            "match_start_time" => $_matchs_start_time,
            "match_stop_time" => $_matchs_stop_time,
            "match_join_time" => $_matchs_join_time,
            "matchs_type_id" => $_matchs_type_id,
            "match_image" => $_match_image,
            "match_image_list" => $_match_image_list,
            "match_phone_prefix" => $_match_phone_prefix,
            "join_status" => $_join_status,
            "website_show" => $_website_show,
            "match_user_type" => json_encode($_matchs_user_type),
            "match_user_sex" => json_encode($_matchs_user_sex),
        );

        $_arrOfUserTypePass = array();
        foreach ($_matchs_user_type as $node) {
            if (isset($_arrOfSysUserTypeKey[$node])) {
                array_push($_arrOfUserTypePass, $_arrOfSysUserTypeKey[$node]);
            }
        }
        $_arrOfMatchs["match_user_type_description"] = count($_arrOfUserTypePass) == count($_arrOfSysUserTypeKey) ? LanguageController::getLanguage($_language, "all") : implode("、", $_arrOfUserTypePass);

        $_arrOfUserSexPass = array();
        foreach ($_matchs_user_sex as $node) {
            if (isset($_arrOfSysUserSexKey[$node])) {
                array_push($_arrOfUserSexPass, $_arrOfSysUserSexKey[$node]);
            }
        }
        $_arrOfMatchs["match_user_sex_description"] = count($_arrOfUserSexPass) == count($_arrOfSysUserSexKey) ? LanguageController::getLanguage($_language, "all") : implode("、", $_arrOfUserSexPass);


//        编辑
        if ($_sys_match_id != "") {
            $_arrOfMatchs["created_uid"] = $_admin_user["admin_user_id"];

            SysMatch::where(["sys_match_id" => $_sys_match_id])->update($_arrOfMatchs);
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "update_success"),
            );
        } else {
//            新增
            $_snowflake = new Snowflake(StaticDataController::$_workId);
            $_rand_id = $_snowflake->nextId();


            $_arrOfMatchs["sys_match_id"] = $_rand_id;
            $_arrOfMatchs["status"] = 0;
            $_arrOfMatchs["updated_uid"] = $_admin_user["admin_user_id"];
            SysMatch::create($_arrOfMatchs);
            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "create_success")
            );
        }
    }

    /**
     * @author pengjl
     * @time 2021/5/11 15:00
     * @abstract _查询赛事列表
     */
    public function getMatchsList(Request $request)
    {
        $_data = $request->input();

        $_search = isset($_data["search"]) ? $_data["search"] : '';
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';


        $_arrOfSysMatchQuery = SysMatch::where([
            "matchs_type.status" => 1,
        ])->where("sys_match.status", ">", -1)->whereNull("sys_match.sys_sys_match_id")->join("matchs_type", function ($join) {
            $join->on("sys_match.matchs_type_id", "=", "matchs_type.matchs_type_id");
        });

        //        如果存在搜索筛选
        if ($_search != '') {
            $_arrOfSysMatchQuery = $_arrOfSysMatchQuery->where(function ($query) use ($_search) {
                $query->where("sys_match.match_title", "like", '%' . $_search . "%");
//                    ->orwhere("store.store_number","like",'%'.$_search."%")
//                    ->orwhere("performance.performance_id","=",$_search)
//                    ->orwhere("store.store_id","=",$_search);
            });
        }
        $_arrOfSysMatchQuery = $_arrOfSysMatchQuery->select(
            "sys_match.sys_match_id", "sys_match.status", "sys_match.match_title", "sys_match.match_description", "sys_match.match_phone", "sys_match.match_email"
            , "sys_match.match_start_time", "sys_match.match_stop_time", "sys_match.match_user_type", "sys_match.match_user_sex", "sys_match.match_user_sign_count"
            , "matchs_type.matchs_type_id", "matchs_type.matchs_type_title", "sys_match.is_group", "sys_match.match_status"
//            ,"sys_match.match_user_type_description","sys_match.match_user_sex_description"
        )->orderBy("sys_match.created_time", "DESC");//sys_match.sys_match_id  DESC

        $_arrOfSysMatchCount = $_arrOfSysMatchQuery->get();
        $_arrOfSysMatch = $_arrOfSysMatchQuery->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfSysMatch as $key => $value) {
            switch ($value["match_status"]) {
                case 0:
                    $value["match_state"] = LanguageController::getLanguage($_language, "wait_release");
                    break;
                case 1:
                    $value["match_state"] = LanguageController::getLanguage($_language, "befor_start");
                    break;
                case 2:
                    $value["match_state"] = LanguageController::getLanguage($_language, "starting");
                    break;
                case 3:
                    $value["match_state"] = LanguageController::getLanguage($_language, "stop");
                    break;
            }

            $_arrOfMatchUserType = json_decode($value["match_user_type"], true);
            $_arrOfMatchUserSex = json_decode($value["match_user_sex"], true);
            $value["start_time"] = date("Y-m-d H:i:s", $value["match_start_time"]);
            $value["stop_time"] = date("Y-m-d H:i:s", $value["match_stop_time"]);

            $value["person_group"] = $value["is_group"] == 0 ? "个人赛" : "团队赛";

            $_arrOfSysMatch[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfSysMatchCount),
                "list" => $_arrOfSysMatch,
            )
        );
    }

    /**
     * @abstract 查询赛事详情
     * @param Request $request
     * @return array
     */
    public function getMatchsInfo(Request $request)
    {
        $_data = $request->input();

        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';


        $_arrOfSysMatch = SysMatch::where([
            "sys_match.sys_match_id" => $_sys_match_id
        ])->join("matchs_type", function ($join) {
            $join->on("sys_match.matchs_type_id", "=", "matchs_type.matchs_type_id");
        })->whereNull("sys_match.sys_sys_match_id")->select(
            "sys_match.sys_match_id", "sys_match.match_user_sex", "sys_match.match_user_type", "sys_match.match_start_time", "sys_match.match_stop_time"
            , "sys_match.match_phone", "sys_match.match_email", "sys_match.match_description", "sys_match.match_description_en", "sys_match.match_title", "sys_match.match_title_en", "sys_match.matchs_type_id"
            , "sys_match.match_phone_prefix", "sys_match.match_image", "sys_match.match_image_list", "matchs_type.matchs_type_title", "sys_match.match_user_type_description"
            , "sys_match.match_user_sex_description", "sys_match.is_group", "sys_match.join_status", "sys_match.website_show"
        )->get();


        $_arrOfSysMatchKey = array();
        $_arrOfSysMatchId = array();
        foreach ($_arrOfSysMatch as $key => $value) {
            $arrOfMatchImage = explode("/", $value["match_image"]);
            $arrOfMatchImageList = explode("/", $value["match_image_list"]);
            $_arrOfSysMatchKeyNode = array(
                "server_url" => StaticDataController::$_server_url,
                "image_list" => array(
                    array(
                        "uid" => -1 - $key,
                        "name" => $arrOfMatchImage[count($arrOfMatchImage) - 1],
                        "status" => "done",
                        "url" => StaticDataController::$_server_url . "/" . $value["match_image"]
                    )
                ),
                "image_list_list" => array(
                    array(
                        "uid" => -1 - $key,
                        "name" => $arrOfMatchImageList[count($arrOfMatchImageList) - 1],
                        "status" => "done",
                        "url" => StaticDataController::$_server_url . "/" . $value["match_image_list"]
                    )
                ),
                "event" => array(),
                "arrOfUserType" => json_decode($value["match_user_type"], true),
                "arrOfUserSex" => json_decode($value["match_user_sex"], true),
                "start_time" => date("Y-m-d H:i:s", $value["match_start_time"]),
                "stop_time" => date("Y-m-d H:i:s", $value["match_stop_time"]),
                "sys_match_id" => $value["sys_match_id"],
                "match_user_sex" => $value["match_user_sex"],
                "match_user_type" => $value["match_user_type"],
                "match_start_time" => $value["match_start_time"],
                "match_stop_time" => $value["match_stop_time"],
                "match_phone" => $value["match_phone"],
                "match_email" => $value["match_email"],
                "match_description" => $value["match_description"],
                "match_title" => $value["match_title"],
                "match_description_en" => $value["match_description_en"],
                "match_title_en" => $value["match_title_en"],
                "matchs_type_id" => $value["matchs_type_id"],
                "match_phone_prefix" => $value["match_phone_prefix"],
                "match_image" => StaticDataController::$_server_url . "/" . $value["match_image"],
                "match_image_list" => StaticDataController::$_server_url . "/" . $value["match_image_list"],
                "matchs_type_title" => $value["matchs_type_title"],
                "join_status" => $value["join_status"],
                "website_show" => $value["website_show"],
//                "match_user_type_description"=>$value["match_user_type_description"],
//                "match_user_sex_description"=>$value["match_user_sex_description"],
                "is_group" => $value["is_group"],
            );
            $_arrOfSysMatchKey[$value["sys_match_id"]] = $_arrOfSysMatchKeyNode;
            array_push($_arrOfSysMatchId, $value["sys_match_id"]);
        }


//        查询赛事项目
        $_arrOfSysMatchNode = SysMatch::where([
            "sys_match.status" => 1
        ])->whereIn("sys_match.sys_sys_match_id", $_arrOfSysMatchId)
            ->join("matchs_event_type", function ($join) {
                $join->on("sys_match.matchs_event_type_id", "=", "matchs_event_type.matchs_event_type_id");
            })->select(
                "sys_match.sys_match_id", "sys_match.sys_sys_match_id", "sys_match.match_champion_prize_description", "sys_match.match_champion_prize_description_en", "matchs_event_type.match_events_type_title"
            )->get();

        $_arrOfSysMatchNodeKey = array();
        $_arrOfSysMatchNodeId = array();
        foreach ($_arrOfSysMatchNode as $value) {
            array_push($_arrOfSysMatchNodeId, $value["sys_match_id"]);
            $value["stage"] = array();
            $_arrOfSysMatchNodeKeyNode = array(
                "stage" => array(),
                "sys_match_id" => $value["sys_match_id"],
                "sys_sys_match_id" => $value["sys_sys_match_id"],
                "match_champion_prize_description" => $value["match_champion_prize_description"],
                "match_champion_prize_description_en" => $value["match_champion_prize_description_en"],
                "match_events_type_title" => $value["match_events_type_title"],
            );
            $_arrOfSysMatchNodeKey[$value["sys_match_id"]] = $_arrOfSysMatchNodeKeyNode;
        }

//        查询项目赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1
        ])->whereIn("sys_match_id", $_arrOfSysMatchNodeId)->select(
            "match_stage_title", "match_stage_title_en", "match_stage_start_time", "match_stage_stop_time", "matchs_stage_id"
            , "max_integral", "sub_integral", "get_integral_type", "get_integral_value"
            , "match_promotion_type", "match_promotion_value", "sys_sys_match_id", "sys_match_id"
            , "match_stage_distance", "view_type", "matchs_stage_status", "is_exponent"
        )->get();
        foreach ($_arrOfMatchStage as $value) {
            $value["start_time"] = date("Y-m-d H:i:s", $value["match_stage_start_time"]);
            $value["stop_time"] = date("Y-m-d H:i:s", $value["match_stage_stop_time"]);

            switch ($value["matchs_stage_status"]) {
                case 1:
                    $value["matchs_stats_title"] = "未开始";
                    break;
                case 2:
                    $value["matchs_stats_title"] = "进行中";
                    break;
                case 3:
                    $value["matchs_stats_title"] = "已结束";
                    break;
            }

            array_push($_arrOfSysMatchNodeKey[$value["sys_match_id"]]["stage"], $value);
        }

        foreach ($_arrOfSysMatchNodeKey as $key => $value) {
            array_push($_arrOfSysMatchKey[$value["sys_sys_match_id"]]["event"], $value);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array_values($_arrOfSysMatchKey),
        );
    }

    /**
     * @abstract 删除赛事
     * @param Request $request
     * @return array
     */
    public function postMatchsDelete(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

        //        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

        $_arrOfSysMatch = SysMatch::where([
            "sys_match_id" => $_sys_match_id
        ])->select("status")->get();

        foreach ($_arrOfSysMatch as $value) {
            if ($value["status"] == 0) {
                SysMatch::where([
                    "sys_match_id" => $_sys_match_id
                ])->update([
                    "status" => -1,
                    "updated_uid" => $_admin_user["admin_user_id"]
                ]);

                SysMatch::where([
                    "sys_sys_match_id" => $_sys_match_id
                ])->update([
                    "status" => -1,
                    "updated_uid" => $_admin_user["admin_user_id"]
                ]);
                return array(
                    "code" => 1,
                    "msg" => LanguageController::getLanguage($_language, "delete_success")
                );
            } else if ($value["status"] == 1) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "delete_error_release_match")
                );
            } else if ($value["status"] == 2) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "delete_error_end_match")
                );
            } else {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "delete_error")
                );
            }
        }

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

    /**
     * @abstract 发布赛事
     * @param Request $request
     * @return array
     */
    public function postMatchsRelease(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

        //        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
//        赛事ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

        $_arrOfSysMatch = SysMatch::where([
            "sys_match_id" => $_sys_match_id
        ])->select("match_start_time", "match_stop_time")->get();

        $_arrOfUpdatedData = [
            "status" => 1,
            "updated_uid" => $_admin_user["admin_user_id"]
        ];

        if (count($_arrOfSysMatch) == 1 && $_arrOfSysMatch[0]["match_start_time"] < time()) {
            $_arrOfUpdatedData["match_status"] = 2;

            if ($_arrOfSysMatch[0]["match_stop_time"] < time()) {
                $_arrOfUpdatedData["match_status"] = 3;
            }
        }

        SysMatch::where([
            "sys_match_id" => $_sys_match_id
        ])->update($_arrOfUpdatedData);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "release_success")
        );
    }

    /**
     * @abstract 取消发布赛事
     * @param Request $request
     * @return array
     */
    public function postMatchsUnRelease(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

        //        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
//        赛事ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

//        赛事状态变更
        SysMatch::where(["sys_match_id" => $_sys_match_id])->update(["status" => 0, "updated_uid" => $_admin_user["admin_user_id"]]);
////        赛事项目状态变更
//        SysMatch::where(["sys_sys_match_id"=>$_sys_match_id])->update(["status"=>0,"updated_uid"=>$_admin_user["admin_user_id"]]);
////        报名表状态变更
//        MatchsUser::where(["sys_sys_match_id"=>$_sys_match_id])->update(["status"=>0,"updated_uid"=>$_admin_user["admin_user_id"]]);
////        赛段状态变更
//        MatchsStage::where(["sys_sys_match_id"=>$_sys_match_id])->update(["status"=>0,"updated_uid"=>$_admin_user["admin_user_id"]]);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "unrelease_success")
        );
    }


    /**
     * @abstract 获取赛事标题列表
     * @param Request $request
     * @return array
     */
    public function postMatchTitleList(Request $request)
    {

        $_arrOfMatch = SysMatch::whereIn("status", array(0, 1))->select("match_title", "sys_match_id")
            ->whereNull("sys_sys_match_id")->orderBy("match_start_time", "DESC")->get();

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfMatch
        );
    }


    /**
     * @abstract 获取赛事标题列表
     * @param Request $request
     * @return array
     */
    public function postMatchBannerTitleList(Request $request)
    {

        $_arrOfMatch = SysMatch::whereIn("status", array(0, 1))->select("match_title", "sys_match_id")
            ->whereNull("sys_sys_match_id")->orderBy("match_start_time", "DESC")->get();

        $_arrOfMatchKey = array();
        foreach ($_arrOfMatch as $value) {
            $_arrOfMatchKey[$value["sys_match_id"]] = $value["match_title"];
        }

        $_arrOfSysMatch = SysMatch::where([
            "sys_match.status" => 1,
            "matchs_event_type.status" => 1,
        ])->join("matchs_event_type", function ($join) {
            $join->on("sys_match.matchs_event_type_id", "=", "matchs_event_type.matchs_event_type_id");
        })->whereIn("sys_match.sys_sys_match_id", array_keys($_arrOfMatchKey))->select(
            "sys_match.sys_match_id", "sys_match.sys_sys_match_id", "matchs_event_type.match_events_type_title"
        )->get();

        foreach ($_arrOfSysMatch as $value) {
            $value["match_title"] = $_arrOfMatchKey[$value["sys_sys_match_id"]] . "-" . $value["match_events_type_title"];
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfSysMatch
        );
    }


    /**
     * @abstract 创建赛事比赛项目
     * @param Request $request
     * @return array
     */
    public function postMatchEventAdd(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
//        赛事ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";
        $_match_event_type_value = isset($_data["matchs_event_type_id"]) ? $_data["matchs_event_type_id"] : "";
        $_match_champion_prize_description = isset($_data["match_champion_prize_description"]) ? $_data["match_champion_prize_description"] : "";
        $_match_champion_prize_description_en = isset($_data["match_champion_prize_description_en"]) ? $_data["match_champion_prize_description_en"] : "";
        $_matchs_type_id = isset($_data["matchs_type_id"]) ? $_data["matchs_type_id"] : "";

        if ($_sys_match_id == "" || $_match_event_type_value == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }

        $_snowflake = new Snowflake(StaticDataController::$_workId);
        $_rand_id = $_snowflake->nextId();

        $_arrOfSysMatchData = array(
            "sys_sys_match_id" => $_sys_match_id,
            "match_champion_prize_description" => $_match_champion_prize_description,
            "match_champion_prize_description_en" => $_match_champion_prize_description_en,
            "matchs_event_type_id" => $_match_event_type_value,
            "matchs_type_id" => $_matchs_type_id,
            "status" => 1,
            "join_status" => isset($_data["need_audit"]) ? $_data["need_audit"] : 0,
            "sys_match_id" => $_rand_id,
            "created_uid" => $_admin_user["admin_user_id"],
        );


        SysMatch::create($_arrOfSysMatchData);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "create_success")
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/19 20:55
     * @abstract _删除比赛项目
     */
    public function postMatchEventDelete(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

//        赛事项目ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";

        if (!isset($_data["sys_match_id"])) {
            return SystemErrorController::paramtersError($_language);
        }


        SysMatch::where(["sys_match_id" => $_data["sys_match_id"]])->update(["status" => 0, "updated_uid" => $_admin_user["admin_user_id"]]);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "delete_success")
        );
    }


    /**
     * @anstract 创建赛段
     * @param Request $request
     * @return array
     */
    public function postMatchEventStageAdd(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';


        $_arrOfMatchStage = array(
            "sys_match_id" => isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : null,
            "match_stage_title" => isset($_data["match_stage_title"]) ? $_data["match_stage_title"] : null,
            "match_stage_title_en" => isset($_data["match_stage_title_en"]) ? $_data["match_stage_title_en"] : null,
            "match_stage_start_time" => isset($_data["match_stage_start_time"]) ? $_data["match_stage_start_time"] : null,
            "match_stage_stop_time" => isset($_data["match_stage_stop_time"]) ? $_data["match_stage_stop_time"] : null,
            "match_promotion_type" => isset($_data["match_promotion_type"]) ? $_data["match_promotion_type"] : null,
            "match_promotion_value" => isset($_data["match_promotion_value"]) ? $_data["match_promotion_value"] : null,
            "sys_sys_match_id" => isset($_data["sys_sys_match_id"]) ? $_data["sys_sys_match_id"] : null,
            "match_stage_distance" => isset($_data["match_stage_distance"]) ? $_data["match_stage_distance"] : null,
            "view_type" => isset($_data["view_type"]) ? $_data["view_type"] : null,
            "is_exponent" => isset($_data["is_exponent"]) ? $_data["is_exponent"] : null,
            "matchs_stage_status" => 1,
            "status" => 1
        );

//        所有字段不允许为空
        foreach ($_arrOfMatchStage as $key => $value) {
            if ($value === null && $key != 'match_stage_title_en') {
                return SystemErrorController::paramtersError($_language);
            }
        }

//        如果定义积分
        if (isset($_data["max_integral"])) {
            $_arrOfMatchStage["max_integral"] = $_data["max_integral"];
            $_arrOfMatchStage["sub_integral"] = $_data["sub_integral"];
            $_arrOfMatchStage["get_integral_type"] = $_data["get_integral_type"];
            $_arrOfMatchStage["get_integral_value"] = $_data["get_integral_value"];
        }

        if (isset($_data["matchs_stage_id"])) {
            $_arrOfMatchStage["updated_uid"] = $_admin_user["admin_user_id"];
            MatchsStage::where(["matchs_stage_id" => $_data["matchs_stage_id"]])->update($_arrOfMatchStage);

            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "update_success")
            );
        } else {
            $_snowflake = new Snowflake(StaticDataController::$_workId);
            $_rand_id = $_snowflake->nextId();
            $_arrOfMatchStage["matchs_stage_id"] = $_rand_id;
            $_arrOfMatchStage["created_uid"] = $_admin_user["admin_user_id"];
            MatchsStage::create($_arrOfMatchStage);

            return array(
                "code" => 1,
                "msg" => LanguageController::getLanguage($_language, "create_success")
            );
        }
    }


    /**
     * @abstract 删除赛段
     * @param Request $request
     * @return array
     */
    public function postMatchEventStageDelete(Request $request)
    {
        $_data = $request->input();

        $_token_key = "admin_user_token:" . $request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
//        赛事ID
        $_sys_match_id = isset($_data["sys_match_id"]) ? $_data["sys_match_id"] : "";
        $_sys_sys_match_id = isset($_data["sys_sys_match_id"]) ? $_data["sys_sys_match_id"] : "";
        $_matchs_stage_id = isset($_data["matchs_stage_id"]) ? $_data["matchs_stage_id"] : "";

        $_arrOfSysMatch = SysMatch::where([
            "sys_match_id" => $_sys_sys_match_id
        ])->select('status')->get();

        if (count($_arrOfSysMatch) == 1 && $_arrOfSysMatch[0]['status'] != 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "delete_error_release_match")
            );
        }

        MatchsStage::where([
            "matchs_stage_id" => $_matchs_stage_id
        ])->update([
            "status" => 0,
            "updated_uid" => $_admin_user["admin_user_id"]
        ]);

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, 'delete_success')
        );
    }

    /**
     * 新版赛事==用户报名==后台管理
     * @param Request $request
     * @return array
     */
    public function addMatchUserSign(Request $request): array
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $phoneArr = [];
        $where = "usr_user.phone";
        $setKey = '';

        if (!isset($_data['sys_match_id']) || !isset($_data['sys_sys_match_id']) || (isset($_data['is_group']) && $_data["is_group"] == 1 && !isset($_data["user_group_id"])) && !isset($_data['is_quartets']) || !isset($_data['team_tag'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
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

        if (!empty($_data['user_id'])) {
            $phoneArr = explode(',', $_data["user_id"]);
            Redis::select(1);
            Redis::set('match-' . $_data["user_id"], LanguageController::getLanguage($_language, "none_user"));
            $where = "usr_user.user_id";
            $setKey = $_data["user_id"];
        }

        if (!empty($_data["phone"])) {
            $phoneArr = explode(',', $_data["phone"]);
            Redis::select(1);
            Redis::set('match-' . $_data["phone"], LanguageController::getLanguage($_language, "none_user"));
            $where = "usr_user.phone";
            $setKey = $_data["phone"];
        }

        foreach ($phoneArr as $k => $v) {
            //获取用户token
            //        查询当前手机号是否存在绑定用户
            $_arrOfUsrUser = UsrUser::where([
                $where => $v,
                "usr_user.status" => 1,
//            "usr_user.phone_prefix" => $_phone_sms_check["phone_prefix"],
            ])->join("sys_user_type", function ($join) {
                $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
            })->join("sys_sex", function ($join) {
                $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
            })->join("sys_country", function ($join) {
                $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
            })->select(
                "usr_user.user_id", "usr_user.is_group", "usr_user.status", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change"
                , "usr_user.user_img_change", "usr_user.user_img", "usr_user.access_token as token", "sys_sex.sys_sex_id", "sys_sex.sex_name"
                , "usr_user.device_uid", "sys_country.sys_country_id", "sys_country.name_cn", "sys_user_type.sys_user_type_id"
                , "sys_user_type.user_type_name", "usr_user.phone", "usr_user.phone_prefix", "usr_user.access_token"
                , "usr_user.is_members", "usr_user.members_status", "usr_user.members_exptime", "usr_user.share_code", "usr_user.integral", "usr_user.birthday"
                , "usr_user.address", "usr_user.version", "usr_user.device_model", "usr_user.channel", "usr_user.sys_sex_id_change", "usr_user.photo_text"
            )->get();

            if (count($_arrOfUsrUser) == 1) {
                $_arrOfUsrUser = $_arrOfUsrUser[0];

                //验证用户如果当前项目数据已存在，变更状态为已报名
                $matchsUser = MatchsUser::where([
                    'status' => 1,
                    'sys_match_id' => $_data["sys_match_id"],
                    'sys_sys_match_id' => $_data["sys_sys_match_id"],
                    'user_id' => $_arrOfUsrUser["user_id"]
                ])->first();
                //获取赛段ID
                $matchsStageId = MatchsStage::where([
                    "sys_match_id" => $_data["sys_match_id"],
                    "sys_sys_match_id" => $_data["sys_sys_match_id"],
                ])->value('matchs_stage_id');
                $join_match_count = UserAchievement::where('user_id', $_arrOfUsrUser["user_id"])->value('join_match_count');
                $_arrOfSysMatch = SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->select("match_user_sign_count")->get();

                if (!empty($matchsUser)) {
                    if ($matchsUser['is_join'] !== 1) {
                        try {
                            DB::transaction(function () use ($_arrOfSysMatch, $join_match_count, $matchsStageId, $_arrOfUsrUser, $_data) {
                                MatchsUser::where([
                                    "user_id" => $_arrOfUsrUser["user_id"],
                                    "sys_match_id" => $_data["sys_match_id"]
                                ])->update([
                                    "is_join" => 1,
                                    'team_tag' => $_data['team_tag'] ?? 0,
                                ]);
                                MatchsUserGrade::where([
                                    "user_id" => $_arrOfUsrUser["user_id"],
                                    "matchs_stage_id" => $matchsStageId,
                                ])->update([
                                    "is_join" => 1,
                                    'team_tag' => $_data['team_tag'] ?? 0,
                                ]);
                                SysMatch::where(["sys_match_id" => $_data["sys_sys_match_id"]])->update(["match_user_sign_count" => $_arrOfSysMatch[0]["match_user_sign_count"] + 1]);
                                UserAchievement::where('user_id', $_arrOfUsrUser["user_id"])->update(["join_match_count" => $join_match_count + 1]);
                            }, 5);
                        } catch (\Throwable $ex) {
                            Redis::select(1);
                            $msgs = Redis::get('match-' . $_data["phone"]);
                            Redis::set('match-' . $_data["phone"], $msgs . ';' . $v . ',' . LanguageController::getLanguage($_language, "match_user_join_error"));
                        }
                    } else {
                        Redis::select(1);
                        $msgs = Redis::get('match-' . $setKey);
                        Redis::set('match-' . $setKey, $msgs . ';' . $v . ',' . "已报名;");
                    }
                } else {//不存在，创建初始报名数据
//            不存在数据，创建
                    $_sno = new Snowflake(StaticDataController::$_workId);
                    $_arrOfMatchUserData = array(
                        "matchs_user_id" => $_sno->nextId(),
                        "user_id" => $_arrOfUsrUser["user_id"],
                        "user_name" => $_arrOfUsrUser["user_name"],
                        "sys_match_id" => $_data["sys_match_id"],
                        "sys_sys_match_id" => $_data["sys_sys_match_id"],
                        "status" => 1,
                        "is_join" => 1,
                        "stage_pass" => 1,
                        "team_tag" => $_data["team_tag"],
                        "is_quartets" => $_data["is_quartets"],
                    );

                    try {
                        DB::transaction(function () use ($join_match_count, $_arrOfMatchUserData, $_sno, $matchsStageId, $_arrOfUsrUser, $_data, $_arrOfSysMatch) {
                            MatchsUser::create($_arrOfMatchUserData);
                            //                创建初始成绩
                            MatchsUserGrade::create([
                                "matchs_user_grade_id" => $_sno->nextId(),
                                "matchs_stage_id" => $matchsStageId,
                                "is_group" => 0,
                                "match_ranking" => 0,
                                "match_grade" => 9999999999,
                                "matchs_user_id" => $_arrOfMatchUserData["matchs_user_id"],
                                "user_id" => $_arrOfUsrUser["user_id"],
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
                            UserAchievement::where('user_id', $_arrOfUsrUser["user_id"])->update(["join_match_count" => $join_match_count + 1]);
                        }, 5);
                    } catch (\Throwable $ex) {
                        Redis::select(1);
                        $msgs = Redis::get('match-' . $setKey);
                        Redis::set('match-' . $setKey, $msgs . ';' . $v . ',' . LanguageController::getLanguage($_language, "match_user_join_error"));
                    }

                }
            } else {//记录为报名成功的账户
                Redis::select(1);
                $msgs = Redis::get('match-' . $setKey);
                Redis::set('match-' . $setKey, $v . ',' . $msgs);
            }

        }
        Redis::select(1);
        $msgs = Redis::get('match-' . $setKey);
        if ($msgs !== LanguageController::getLanguage($_language, "none_user")) {
            return array(
                "code" => 0,
                "msg" => $msgs,
                "data" => array()
            );
        }

        return array(
            "code" => 1,
            "msg" => LanguageController::getLanguage($_language, "match_user_join_success"),
            "data" => array()
        );
    }

    /**
     * 根据赛事ID获取赛事团队列表
     * @param Request $request
     * @return
     */
    public function getTeamTag(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data['sys_sys_match_id'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        $list = SysTeamTag::where('sys_match_id', $_data['sys_sys_match_id'])->get();

        return $this->success($list);
    }

    /**
     * 根据赛事ID添加赛事团队列表
     * @param Request $request
     * @return
     */
    public function addTeamTag(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data['sys_sys_match_id']) || !isset($_data['team_tag'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        $teamTag = explode(',', $_data['team_tag']);
        $insert = [];
        foreach ($teamTag as $k => $v) {
            $insert[] = ['sys_match_id' => $_data['sys_sys_match_id'], 'team_tag' => $v];
        }

        try {
            SysTeamTag::insert($insert);
        } catch (\Throwable $ex) {
            return $this->error();
        }

        return $this->success();
    }


}
