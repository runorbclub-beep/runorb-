<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\TimeFormatController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Controllers\PublicFunction\WebsocketController;
use App\Models\MatchsEventType;
use App\Models\PkGroupBlue;
use App\Models\PkGroupRed;
use App\Models\PkRoom;
use App\Models\UserPkList;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class PkController extends Controller
{


    /**
     * @abstract 获取、刷新房间号
     * @param Request $request
     * @return array
     */
    public function pkRoomNumber(Request $request)
    {
        Redis::select(1);

        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["pk_type"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_room_index = Redis::get("room_number_index");
//        没有数据，或达到临界值，重置redis
        if (!$_room_index || $_room_index >= 999999) {
            Redis::set("room_number_index", 0);
        }

//        拼接0
        $_str_zero = "";
        for ($_i = 0; $_i < 6 - strlen($_room_index); $_i++) {
            $_str_zero .= '0';
        }

//        自增
        Redis::INCR("room_number_index");

        $_pk_time = $_data["pk_type"] == 0 ? Redis::hget("sys_setting", "pk_person_time") : Redis::hget("sys_setting", "pk_group_time");
        $_pk_max_person = $_data["pk_type"] == 0 ? 1 : Redis::hget("sys_setting", "pk_group_user");

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "pk_room_number" => $_str_zero . $_room_index,
                "pk_time_long" => (int)$_pk_time,
                "pk_max_person" => (int)$_pk_max_person,
            )
        );
    }

    /**
     * @anstract 用户创建PK房间
     * @param Request $request
     * @return array
     */
    public function pkCreatedRoom(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        房间号，PK类型，结果判定类型
        if (!isset($_data["pk_room_number"]) || !isset($_data["pk_result_type"]) || !isset($_data["pk_type"])) {
            return SystemErrorController::paramtersError($_language);
        }

//        多人PK,未设置最多人数、队伍名
        if ($_data["pk_type"] == 1 && !isset($_data["pk_max_person"]) || $_data["pk_type"] == 1 && !isset($_data["group_red_title"]) || $_data["pk_type"] == 1 && !isset($_data["group_blue_title"])) {
            return SystemErrorController::paramtersError($_language);
        }

//        结果判定方式参数验证
        if (($_data["pk_result_type"] == 0 && !isset($_data["matchs_event_type_id"])) || ($_data["pk_result_type"] == 1 && !isset($_data["time_long"]))) {
            return SystemErrorController::paramtersError($_language);
        }

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_arrOfPkRoom = array(
            "pk_room_id" => $_sno->nextId(),
            "pk_room_title" => isset($_data["pk_room_title"]) ? $_data["pk_room_title"] : "",
            "pk_room_number" => $_data["pk_room_number"],
            "pk_type" => $_data["pk_type"],
            "pk_result_type" => $_data["pk_result_type"],
            "user_id" => $_usr_user["user_id"],
            "created_uid" => $_usr_user["user_id"],
            "status" => 1
        );

//        如果是多人pk，设置最多人数
        if ($_arrOfPkRoom["pk_type"] == 1) {
            $_arrOfPkRoom["pk_max_person"] = $_data["pk_max_person"];
            $_arrOfPkRoom["group_red_title"] = $_data["group_red_title"];
            $_arrOfPkRoom["group_blue_title"] = $_data["group_blue_title"];
        }

//        如果比赛结果按时间计算
        if ($_arrOfPkRoom["pk_result_type"] == 0) {
            $_arrOfPkRoom["matchs_event_type_id"] = $_data["matchs_event_type_id"];
            $_arrOfMatchsEventType = MatchsEventType::where(["matchs_event_type_id" => $_data["matchs_event_type_id"]])
                ->select("matchs_event_type_id", "match_events_distance_value")->get();
            if (count($_arrOfMatchsEventType) == 1) {
                $_arrOfPkRoom["distance_value"] = $_arrOfMatchsEventType[0]["match_events_distance_value"];
            }
        } else {
            $_arrOfPkRoom["time_long"] = $_data["time_long"] * 60;
        }

//        创建PK房间
        $_arrOfPkRoom['created_time'] = time();
        $_arrOfPkRoom['updated_time'] = time();

        //修复重复创建bug
        $is_pk_room_number = PkRoom::where('pk_room_number',$_data["pk_room_number"])->exists();
        if ($is_pk_room_number !== true){
            try {
                PkRoom::create($_arrOfPkRoom);
                Log::info("房间号".$_data["pk_room_number"]."创建成功================");
            } catch (\Exception $ex){
                Log::info("房间号创建失败，Err：", (array)$ex);
                return LanguageController::getLanguage($_language,"room_number_error");
            }
        }

        $_arrOfPkRoom["red"] = array();
        $_arrOfPkRoom["blue"] = array();
        $_arrOfPkRoom["pk_start_time"] = time();//PK 开始时间
        $_arrOfPkRoom["pk_stop_time"] = time();//PK 结束时间

        Redis::select(13);
        Redis::setex($_arrOfPkRoom["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfPkRoom
        );
    }


    /**
     * @abstract PK房间列表
     * @param Request $request
     * @return array
     */
    public function pkRoomList(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_search = isset($_data["search"]) ? $_data["search"] : '';
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_time = time() - 60 * 60 * 24;

        $_arrOfPkRoomQuery = PkRoom::where([
            "status" => 1,
        ])->where("created_time", ">", $_time);


        // 如果存在搜索筛选
        if ($_search != '') {
            $_arrOfPkRoomQuery = $_arrOfPkRoomQuery->where(function ($query) use ($_search) {
                $query->where("pk_room_title", "like", '%' . $_search . "%");
            });
        }

        $_arrOfPkRoomQuery = $_arrOfPkRoomQuery->select(
            "pk_room_id", "pk_room_title", "time_long", "pk_type", "distance_value", "pk_result_type", "user_id"
            , "pk_room_pwd", "pk_max_person"
        )->orderBy("created_time", "DESC");

        $_arrOfPkRoomCount = $_arrOfPkRoomQuery->get();
        $_arrOfPkRoom = $_arrOfPkRoomQuery->skip($_offset)->take($_limit)->get();

        $_arrOfPkRoomId = array();
        $_arrOfPkRoomKey = array();
        foreach ($_arrOfPkRoom as $value) {
            $_arrOfPkRoomKeyNode = array(
                "pk_room_id" => $value["pk_room_id"],
                "pk_room_title" => $value["pk_room_title"],
                "time_long" => $value["time_long"],
                "pk_type" => $value["pk_type"],
                "distance_value" => $value["distance_value"],
                "pk_result_type" => $value["pk_result_type"],
                "user_id" => $value["user_id"],
                "pk_room_pwd" => $value["pk_room_pwd"],
                "pk_max_person" => $value["pk_max_person"],
                "is_join" => 0,
                "is_full" => 0,
                "join_user_id" => array(),
            );
            $_arrOfPkRoomKey[$value["pk_room_id"]] = $_arrOfPkRoomKeyNode;
            array_push($_arrOfPkRoomId, $value["pk_room_id"]);
        }

        $_arrOfUserPkList = UserPkList::where([
            "status" => 1,
        ])->whereIn("pk_room_id", $_arrOfPkRoomId)->select(
            "pk_room_id", "user_id", "user_pk_list_id"
        )->get();

        foreach ($_arrOfUserPkList as $value) {
            array_push($_arrOfPkRoomKey[$value["pk_room_id"]]["join_user_id"], $value["user_id"]);
        }

        foreach ($_arrOfPkRoomKey as $key => $value) {
//            如果当前用户在报名列表内
            if (in_array($_usr_user["user_id"], $value["join_user_id"])) {
                $value["is_join"] = 1;//已报名
            }

//            双人PK
            if ($value["pk_type"] == 0 && count($value["join_user_id"]) >= 2) {
                $value["is_full"] = 1;//满员
            }

//            组队PK
            if ($value["pk_type"] == 1 && count($value["join_user_id"]) >= $value["pk_max_person"] * 2) {
                $value["is_full"] = 1;//满员
            }

            unset($value["join_user_id"]);
            $_arrOfPkRoomKey[$key] = $value;
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfPkRoomCount),
                "list" => array_values($_arrOfPkRoomKey),
            )
        );
    }

    /**
     * @abstract 用户加入PK房间，参与PK
     * @param Request $request
     * @return array
     */
    public function pkJoinRoom(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        字段缺失验证
        if (!isset($_data["pk_room_number"])) {
            return SystemErrorController::paramtersError($_language);
        }

//        查询10天内 未开始的房间号，降序，避免10天内房间号重复
        $_arrOfPkRoom = PkRoom::where([
            "pk_room_number" => $_data["pk_room_number"],
            "status" => 1,
        ])->where("created_time", ">", time() - 3600 * 24 * 10)
            ->select("pk_room_id", "pk_type", "pk_max_person", "status", "pk_room_number", "group_red_title", "group_blue_title", "time_long", "distance_value", "pk_result_type")
            ->orderBy("pk_room_id", "DESC")->get();
            
//        房间号错误
        if (count($_arrOfPkRoom) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "room_number_error")
            );
        }

        $_arrOfPkRoomInfo = $_arrOfPkRoom[0];
        $_arrOfPkRoomInfo["time_long"] = $_arrOfPkRoomInfo["time_long"] * 60;

//        从redis 获取PK列表
        Redis::select(13);
        $_arrOfRedisPkRoom = json_decode(Redis::get($_arrOfPkRoomInfo["pk_room_id"]), true);

//        PK已开始
        if ($_arrOfRedisPkRoom["status"] == 2) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "pk_is_start")
            );
        }

        $_user_group_data = array(
            array(
                "user_group" => "red",
                "user_group_title" => $_arrOfPkRoomInfo["group_red_title"]
            ),
            array(
                "user_group" => "blue",
                "user_group_title" => $_arrOfPkRoomInfo["group_blue_title"]
            )
        );

//        团队PK没有选择加入队伍，返回提示
        if ($_arrOfPkRoomInfo["pk_type"] == 1 && !isset($_data["user_group"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "pk_group_choose"),
                "data" => $_user_group_data
            );
        }


        $_arrOfRedisPkRoom["group_info"] = $_user_group_data;

        $_arrOfUserPkListKey = array();

        $_arr_group_red = $_arrOfRedisPkRoom["red"];
        $_arr_group_blue = $_arrOfRedisPkRoom["blue"];
        foreach ($_arrOfRedisPkRoom["red"] as $key => $value) {
            if (isset($value["user_id"])) {
                $_arrOfUserPkListKey[$value["user_id"]] = $value;
            } else {
                unset($_arrOfRedisPkRoom["red"][$key]);
            }
        }
        foreach ($_arrOfRedisPkRoom["blue"] as $key => $value) {
            if (isset($value["user_id"])) {
                $_arrOfUserPkListKey[$value["user_id"]] = $value;
            } else {
                unset($_arrOfRedisPkRoom["red"][$key]);
            }
        }

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_arrOfRedisPkRoom["user_id"] = $_usr_user["user_id"];

//        单人PK
        if ($_arrOfPkRoomInfo["pk_type"] == 0) {
//            用户不在参赛队员列表
            if (!array_key_exists($_usr_user["user_id"], $_arrOfUserPkListKey)) {

                $_pk_group_id = $_sno->nextId();
                $_group_type = "";
                $_user_pk_list_data = array(
                    "status" => 1,
                    "user_id" => $_usr_user["user_id"],
                    "pk_room_id" => $_arrOfPkRoomInfo["pk_room_id"],
                    "user_pk_list_id" => $_pk_group_id,
                );
                $redisName = 'PKROOMGROUPTYPE-' . $_arrOfPkRoomInfo["pk_room_id"];

                if (count($_arr_group_red) == 0 && Redis::setnx($redisName . 'red', 1) == 1) {
                    $_group_type = "red";
                    Redis::expire($redisName . 'red', 60);
                } else if (count($_arr_group_blue) == 0 && Redis::setnx($redisName . 'blue', 1) == 1) {
                    $_group_type = "blue";
                    Redis::expire($redisName . 'red', 60);
                } else {
//                    没有名额
                    return array(
                        "code" => 0,
                        "msg" => LanguageController::getLanguage($_language, "join_pk_group_full"),
                    );
                }

                $_user_pk_list_data["user_group"] = $_group_type;

                UserPkList::create($_user_pk_list_data);
                $_user_pk_list_data["user_name"] = $_usr_user["user_name"];
                $_user_pk_list_data["user_img"] = $_usr_user["user_img"];
                $_user_pk_list_data["fd"] = 0;//会话ID
                $_user_pk_list_data["is_stop"] = 0;//用户是否结束运动
                $_user_pk_list_data["is_ready"] = 0;//用户是否准备运动
                $_user_pk_list_data["circle_count"] = array(0, 0, 0, 0, 0);//用户运动总圈数
                $_user_pk_list_data["speed"] = array(0, 0, 0, 0, 0);//用户运动速度
                $_user_pk_list_data["is_abnormal"] = 0;//异常数据判定


                if (strpos($_user_pk_list_data["user_img"], 'http') === false) {
                    $_user_pk_list_data["user_img"] = StaticDataController::$_server_url . "/" . $_user_pk_list_data["user_img"];
                }


                $_arrOfRedisPkRoom["user_group"] = $_group_type;
                $_arrOfRedisPkRoom["user_pk_list_id"] = $_user_pk_list_data["user_pk_list_id"];

//                加入成功，更新redis
                $_arrOfRedisPkRoom[$_group_type][$_user_pk_list_data["user_id"]] = $_user_pk_list_data;
                Redis::setex($_arrOfRedisPkRoom["pk_room_id"], 3600 * 24, json_encode($_arrOfRedisPkRoom));


                $_arrOfRedisPkRoom["red"] = array_values($_arrOfRedisPkRoom["red"]);
                $_arrOfRedisPkRoom["blue"] = array_values($_arrOfRedisPkRoom["blue"]);

                Redis::del($redisName);
//                加入成功
                return array(
                    "code" => 1,
                    "msg" => LanguageController::getLanguage($_language, "join_pk_group_success"),
                    "data" => $_arrOfRedisPkRoom
                );
            } else {
//                用户存在
                $_arrOfRedisPkRoom["user_group"] = $_arrOfUserPkListKey[$_usr_user["user_id"]]["user_group"];
                $_arrOfRedisPkRoom["user_pk_list_id"] = $_arrOfUserPkListKey[$_usr_user["user_id"]]["user_pk_list_id"];

                $_arrOfRedisPkRoom["red"] = array_values($_arrOfRedisPkRoom["red"]);
                $_arrOfRedisPkRoom["blue"] = array_values($_arrOfRedisPkRoom["blue"]);
                return array(
                    "code" => 1,
                    "msg" => LanguageController::getLanguage($_language, "join_pk_group_success"),
                    "data" => $_arrOfRedisPkRoom
                );
            }
        } else {

            $_pk_max_person = $_arrOfPkRoomInfo["pk_max_person"];
//            多人PK，不在参赛队员列表
            if (!array_key_exists($_usr_user["user_id"], $_arrOfUserPkListKey)) {
                $_pk_group_id = $_sno->nextId();
                $_group_type = "";
                $_user_pk_list_data = array(
                    "status" => 1,
                    "user_id" => $_usr_user["user_id"],
                    "pk_room_id" => $_arrOfPkRoomInfo["pk_room_id"],
                    "user_pk_list_id" => $_pk_group_id,
                );

//                用户选择红队，且 红队有名额
                if ($_data["user_group"] == "red" && count($_arr_group_red) < $_arrOfPkRoomInfo["pk_max_person"]) {
                    $_group_type = "red";
                } else if ($_data["user_group"] == "blue" && count($_arr_group_blue) < $_arrOfPkRoomInfo["pk_max_person"]) {
//                    用户选择蓝队，且 蓝队有名额
                    $_group_type = "blue";
                } else {
//                    没有名额
                    return array(
                        "code" => 0,
                        "msg" => LanguageController::getLanguage($_language, "join_pk_group_full"),
                    );
                }

                $_user_pk_list_data["user_group"] = $_group_type;
                $is_user_pk_list = UserPkList::where([
                    'status' => 1,
                    'user_id' => $_user_pk_list_data['user_id'],
                    'pk_room_id' => $_user_pk_list_data['pk_room_id'],
                ])->first();
                if (empty($is_user_pk_list)){
                    UserPkList::create($_user_pk_list_data);
                }else{
                    UserPkList::where([
                        'status' => 1,
                        'user_id' => $_user_pk_list_data['user_id'],
                        'pk_room_id' => $_user_pk_list_data['pk_room_id'],
                    ])->update([
                        'user_pk_list_id' => $_user_pk_list_data['user_pk_list_id'],
                        'user_group' => $_user_pk_list_data["user_group"]
                    ]);
                }


                $_user_pk_list_data["user_name"] = $_usr_user["user_name"];
                $_user_pk_list_data["user_img"] = $_usr_user["user_img"];


                if (strpos($_user_pk_list_data["user_img"], 'http') === false) {
                    $_user_pk_list_data["user_img"] = StaticDataController::$_server_url . "/" . $_user_pk_list_data["user_img"];
                }

                $_user_pk_list_data["fd"] = 0;//会话ID
                $_user_pk_list_data["is_stop"] = 0;//用户是否结束运动
                $_user_pk_list_data["is_ready"] = 0;//用户是否准备运动
                $_user_pk_list_data["circle_count"] = array(0, 0, 0, 0, 0);//用户运动总圈数
                $_user_pk_list_data["speed"] = array(0, 0, 0, 0, 0);//用户运动总时间
                $_user_pk_list_data["is_abnormal"] = 0;//异常数据判定

//                加入成功，更新redis
                $_arrOfRedisPkRoom[$_group_type][$_user_pk_list_data["user_id"]] = $_user_pk_list_data;
                if ($_group_type == "red"){
                    if (isset($_arrOfRedisPkRoom['blue'][$_user_pk_list_data["user_id"]])){
                        unset($_arrOfRedisPkRoom['blue'][$_user_pk_list_data["user_id"]]);
                    }
                }else{
                    if (isset($_arrOfRedisPkRoom['red'][$_user_pk_list_data["user_id"]])){
                        unset($_arrOfRedisPkRoom['red'][$_user_pk_list_data["user_id"]]);
                    }
                }
                Redis::setex($_arrOfRedisPkRoom["pk_room_id"], 3600 * 24, json_encode($_arrOfRedisPkRoom));

                $_arrOfRedisPkRoom["user_group"] = $_group_type;
                $_arrOfRedisPkRoom["user_pk_list_id"] = $_user_pk_list_data["user_pk_list_id"];

                $_arrOfRedisPkRoom["red"] = array_values($_arrOfRedisPkRoom["red"]);
                $_arrOfRedisPkRoom["blue"] = array_values($_arrOfRedisPkRoom["blue"]);
//                加入成功
                return array(
                    "code" => 1,
                    "msg" => LanguageController::getLanguage($_language, "join_pk_group_success"),
                    "data" => $_arrOfRedisPkRoom
                );
            } else {
                $_arrOfRedisPkRoom["user_group"] = $_arrOfUserPkListKey[$_usr_user["user_id"]]["user_group"];
                $_arrOfRedisPkRoom["user_pk_list_id"] = $_arrOfUserPkListKey[$_usr_user["user_id"]]["user_pk_list_id"];

//                用户存在
                $_arrOfRedisPkRoom["red"] = array_values($_arrOfRedisPkRoom["red"]);
                $_arrOfRedisPkRoom["blue"] = array_values($_arrOfRedisPkRoom["blue"]);
                return array(
                    "code" => 1,
                    "msg" => LanguageController::getLanguage($_language, "join_pk_group_success"),
                    "data" => $_arrOfRedisPkRoom
                );
            }
        }

    }

    /**
     * @abstract 用户进入房间，围观  需修改
     * @param Request $request
     * @return array
     */
    public function pkRoomInfo(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        字段缺失验证
        if (!isset($_data["pk_room_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfPkRoom = PkRoom::where([
            "status" => 1,
            "pk_room_id" => $_data["pk_room_id"]
        ])->select("pk_room_id", "pk_room_title", "time_long", "pk_type", "distance_value", "pk_result_type", "user_id"
            , "pk_room_pwd", "pk_max_person", "group_win", "status")->get();

        if (count($_arrOfPkRoom) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "not_found")
            );
        }

        $_arrOfPkRoomInfo = array(
            "pk_room_id" => $_arrOfPkRoom[0]["pk_room_id"],
            "pk_room_title" => $_arrOfPkRoom[0]["pk_room_title"],
            "status" => $_arrOfPkRoom[0]["status"],
            "time_long" => $_arrOfPkRoom[0]["time_long"],
            "pk_type" => $_arrOfPkRoom[0]["pk_type"],
            "distance_value" => $_arrOfPkRoom[0]["distance_value"],
            "pk_result_type" => $_arrOfPkRoom[0]["pk_result_type"],
            "user_id" => $_arrOfPkRoom[0]["user_id"],
            "pk_room_pwd" => $_arrOfPkRoom[0]["pk_room_pwd"],
            "pk_max_person" => $_arrOfPkRoom[0]["pk_max_person"],
            "group_win" => $_arrOfPkRoom[0]["group_win"],
            "PkGroupRed" => array(),
            "PkGroupBlue" => array(),
        );

        $_arrOfUserPkList = UserPkList::where([
            "user_pk_list.status" => 1,
            "user_pk_list.pk_room_id" => $_arrOfPkRoomInfo["pk_room_id"]
        ])->join("usr_user", function ($join) {
            $join->on("user_pk_list.user_id", "usr_user.user_id");
        })->select("usr_user.user_id", "usr_user.user_name", "user_pk_list.user_pk_list_id", "user_pk_list.user_group")->get();

        foreach ($_arrOfUserPkList as $value) {
            if ($_arrOfPkRoomInfo["user_id"] == $value["user_id"]) {
                $value["is_master"] = 1;
            }

            if ($value["user_group"] == "red") {
                array_push($_arrOfPkRoomInfo["PkGroupRed"], $value);
            }
            if ($value["user_group"] == "blue") {
                array_push($_arrOfPkRoomInfo["PkGroupBlue"], $value);
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfPkRoomInfo
        );
    }


    /**
     * @abstract 用户切换队伍
     * @param Request $request
     * @return array
     */
    public function pkChangeGroup(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        字段缺失验证
        if (!isset($_data["pk_room_id"]) || !isset($_data["user_pk_list_id"]) || !isset($_data["new_user_group"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfPkRoom = PkRoom::where([
            "status" => 1,
            "pk_room_id" => $_data["pk_room_id"]
        ])->select("pk_room_id", "pk_type", "pk_max_person", "status", "pk_room_number", "group_red_title", "group_blue_title", "time_long", "distance_value", "pk_result_type")->get();

        if (count($_arrOfPkRoom) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "not_found")
            );
        }

        $_arrOfPkRoomInfo = $_arrOfPkRoom[0];

        $_arrOfUserPkList = UserPkList::where([
            "status" => 1,
            "pk_room_id" => $_data["pk_room_id"],
            "user_group" => $_data["new_user_group"]
        ])->select("user_id")->get();

        if ($_arrOfPkRoomInfo["pk_type"] == 0 && count($_arrOfUserPkList) == 1) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "join_pk_group_full"),
            );
        }

        if ($_arrOfPkRoomInfo["pk_type"] == 1 && count($_arrOfUserPkList) >= $_arrOfPkRoomInfo["pk_max_person"]) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "join_pk_group_full"),
            );
        }

//        原计划取消
        UserPkList::where(["user_pk_list_id" => $_data["user_pk_list_id"]])->update(["status" => 0]);

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_arrOfUserPkListData = array(
            "user_pk_list_id" => $_sno->nextId(),
            "user_id" => $_usr_user["user_id"],
            "pk_room_id" => $_data["pk_room_id"],
            "status" => 1,
            "user_group" => $_data["new_user_group"],
        );
        UserPkList::create($_arrOfUserPkListData);

//        用户切换队伍
        WebsocketController::userPkSocketChangeGroup($_arrOfUserPkListData);

        $_arrOfUserPkListData["pk_type"] = $_arrOfPkRoomInfo["pk_type"];
        $_arrOfUserPkListData["time_long"] = $_arrOfPkRoomInfo["time_long"] * 60;
        $_arrOfUserPkListData["pk_result_type"] = $_arrOfPkRoomInfo["pk_result_type"];
        $_arrOfUserPkListData["distance_value"] = $_arrOfPkRoomInfo["distance_value"];

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfUserPkListData
        );
    }

    /**
     * @abstract 用户取消PK
     * @param Request $request
     * @return array
     */
    public function userPkListDelete(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        字段缺失验证
        if (!isset($_data["user_pk_list_id"]) || !isset($_data["pk_room_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

//        查询当前房间的创建者
        $_arrOfPkRoom = PkRoom::where(["pk_room_id" => $_data["pk_room_id"]])->select("user_id")->get();

//        当前用户是房主，PK取消
        if (count($_arrOfPkRoom) == 1 && $_arrOfPkRoom[0]["user_id"] == $_usr_user["user_id"]) {
            PkRoom::where([
                "pk_room_id" => $_data["pk_room_id"]
            ])->update(["status" => 0, "updated_uid" => $_usr_user["user_id"]]);

            UserPkList::where([
                "pk_room_id" => $_data["pk_room_id"]
            ])->update(["status" => 0, "updated_uid" => $_usr_user["user_id"]]);
        } else {
//            用户PK取消
            UserPkList::where(["user_pk_list_id" => $_data["user_pk_list_id"], "user_id" => $_usr_user["user_id"]])->update(["status" => 0, "updated_uid" => $_usr_user["user_id"]]);
        }


        $_user_pk_list_data = array(
            "user_pk_list_id" => $_data["user_pk_list_id"],
            "pk_room_id" => $_data["pk_room_id"],
            "user_id" => $_usr_user["user_id"],
        );

//        退出PK房间
        WebsocketController::userClosePkSocket($_user_pk_list_data);

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 用户开始PK
     * @param Request $request
     * @return array
     */
    public function userPkStart(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

//        参数验证
        if (!isset($_data["pk_room_id"]) || !isset($_data["pk_status"]) || !isset($_data["user_group"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

//        数据结构错误
        if (!isset($_arrOfPkRoom[$_data["user_group"]])
            || !isset($_arrOfPkRoom[$_data["user_group"]][$_usr_user["user_id"]])) {

            return SystemErrorController::sysDataError($_language);
        }


//        变更准备状态
        $_arrOfPkRoom[$_data["user_group"]][$_usr_user["user_id"]]["is_ready"] = $_data["pk_status"];

//        存入缓存
        Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

//        用户准备，下发通知
        WebsocketController::sentStatusToUser($_data["pk_room_id"], "pkListChange");

//        遍历用户状态
        if (isset($_arrOfPkRoom["red"]) && count($_arrOfPkRoom["red"]) > 0) {
            foreach ($_arrOfPkRoom["red"] as $key => $value) {
//                如果存在用户没有准备
                if ($value["is_ready"] == 0) {
                    return array(
                        "code" => 1,
                        "msg" => "success"
                    );
                }
            }
        } else {
//            变更状态
            WebsocketController::sentStatusToUser($_data["pk_room_id"], "pkListChange");
            return array(
                "code" => 1,
                "msg" => "success"
            );
        }
        if (isset($_arrOfPkRoom["blue"]) && count($_arrOfPkRoom["blue"]) > 0) {
            foreach ($_arrOfPkRoom["blue"] as $key => $value) {
//                如果存在用户没有准备
                if ($value["is_ready"] == 0) {
                    return array(
                        "code" => 1,
                        "msg" => "success"
                    );
                }
            }
        } else {
//            变更状态
            WebsocketController::sentStatusToUser($_data["pk_room_id"], "pkListChange");
            return array(
                "code" => 1,
                "msg" => "success"
            );
        }

//        所有用户都点击了开始游戏，服务端下发倒计时
        WebsocketController::sentStatusToUser($_data["pk_room_id"], "pkStart");

        PkRoom::where([
            "pk_room_id" => $_data["pk_room_id"]
        ])->update([
            "status" => 2,//PK 已开始
        ]);

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 用户完成PK
     * @param Request $request
     * @return array
     */
    public function internalPkStop(Request $request)
    {
        $_data = $request->input();
        
        if (!isset($_data["pk_room_id"]) || !isset($_data["group_win"])) {
            return ["code" => 0, "msg" => "missing params"];
        }
        
        $_pk_room_id = $_data["pk_room_id"];
        $_group_win = $_data["group_win"];
        
        // Get user_pk_list_ids for this room
        $_arrOfUserPkList = \App\Models\UserPkList::where([
            "pk_room_id" => $_pk_room_id,
            "status" => 1,
        ])->get();
        
        $_arrOfUserPkListId = [];
        foreach ($_arrOfUserPkList as $item) {
            $_arrOfUserPkListId[] = $item->user_pk_list_id;
        }
        
        // Update user_pk_list
        if (count($_arrOfUserPkListId) > 0) {
            \App\Models\UserPkList::where([
                "status" => 1,
            ])->whereIn("user_pk_list_id", $_arrOfUserPkListId)->update([
                "group_win" => $_group_win,
            ]);
        }
        
        // Update pk_room
        \App\Models\PkRoom::where([
            "pk_room_id" => $_pk_room_id,
        ])->update([
            "group_win" => $_group_win,
            "status" => 3,
            "stop_time" => time(),
            "start_time" => time() - ($_data["duration"] ?? 60),
        ]);
        
        Log::info("Internal PK stop: room={$_pk_room_id} win={$_group_win}");
        
        return ["code" => 1, "msg" => "success"];
    }

        public function userPkStop(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

//        参数验证
        if (!isset($_data["pk_room_id"]) || !isset($_data["user_group"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

//        数据结构错误
        if (!isset($_arrOfPkRoom[$_data["user_group"]])
            || !isset($_arrOfPkRoom[$_data["user_group"]][$_usr_user["user_id"]])) {

            return SystemErrorController::sysDataError($_language);
        }


//        变更准备状态
        $_arrOfPkRoom[$_data["user_group"]][$_usr_user["user_id"]]["is_stop"] = 1;

//        存入缓存
        Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

//        用户准备，下发通知
        WebsocketController::sentStatusToUser($_data["pk_room_id"], "pkListChange");

//        遍历用户状态
        if (isset($_arrOfPkRoom["red"])) {
            foreach ($_arrOfPkRoom["red"] as $key => $value) {
//                如果存在用户没有完成PK
                if ($value["is_stop"] == 0) {
                    return array(
                        "code" => 1,
                        "msg" => "success"
                    );
                }
            }
        }
        if (isset($_arrOfPkRoom["blue"])) {
            foreach ($_arrOfPkRoom["blue"] as $key => $value) {
//                如果存在用户没有完成PK
                if ($value["is_stop"] == 0) {
                    return array(
                        "code" => 1,
                        "msg" => "success"
                    );
                }
            }
        }

//        所有用户都完成PK，计算PK结果
        $_arrOfPkResult = $this->pkResult($_data["pk_room_id"]);

//        通知结果
        WebsocketController::sentPkResultToUser($_data["pk_room_id"], $_arrOfPkResult, "pkResult");

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 计算PK结果
     * @param $_pk_room_id
     * @return string[]
     */
    public function pkResult($_pk_room_id)
    {

        $_arrOfPkRoom = PkRoom::where([
            "pk_room_id" => $_pk_room_id
        ])->select("pk_room_id", "pk_type", "pk_result_type")->get();

        $_arrOfPkRoomInfo = $_arrOfPkRoom[0];

        $_arrOfUserPlay = UserPkList::where([
            "user_pk_list.status" => 1,
            "user_pk_list.pk_room_id" => $_pk_room_id
        ])->join("user_play", function ($join) {
            $join->on("user_pk_list.user_pk_list_id", "=", "user_play.user_pk_list_id");
        })->select(
            "user_play.user_id", "user_play.duration", "user_play.distance", "user_play.circle_count", "user_pk_list.user_pk_list_id", "user_pk_list.user_group"
        )->get();

        $_arrOfGroupRedDuration = 0;//红队总时间
        $_arrOfGroupRedDistance = 0;//红队总距离
        $_arrOfGroupBlueDuration = 0;//蓝队总时间
        $_arrOfGroupBlueDistance = 0;//蓝队总距离

        $_arrOfUserPkListId = array();//当前PK房间参与的所有用户
        foreach ($_arrOfUserPlay as $value) {
            array_push($_arrOfUserPkListId, $value["user_pk_list_id"]);

            if ($value["user_group"] == "red") {
                $_arrOfGroupRedDuration += $value["duration"];
                $_arrOfGroupRedDistance += $value["distance"];
            } else {
                $_arrOfGroupBlueDuration += $value["duration"];
                $_arrOfGroupBlueDistance += $value["distance"];
            }
        }

//        胜利方
        $_group_win = "";

        if ($_arrOfPkRoomInfo["pk_result_type"] == 0) {
//            固定距离，用时短方获胜
            $_group_win = $_arrOfGroupRedDuration < $_arrOfGroupBlueDuration ? "red" : "blue";

        } else if ($_arrOfPkRoomInfo["pk_result_type"] == 1) {
//            固定时间,距离长方获胜
            $_group_win = $_arrOfGroupRedDistance > $_arrOfGroupBlueDistance ? "red" : "blue";
        }

        UserPkList::where([
            "status" => 1,
        ])->whereIn("user_pk_list_id", $_arrOfUserPkListId)->update([
            "group_win" => $_group_win
        ]);

        PkRoom::where([
            "pk_room_id" => $_pk_room_id
        ])->update([
            "group_win" => $_group_win,
            "status" => 3,//PK 已结束
        ]);


        return array(
            "group_win" => $_group_win,
            "group_red_duration" => TimeFormatController::formatSecondToTime($_arrOfGroupRedDuration),
            "group_red_distance" => number_format($_arrOfGroupRedDistance / 1000, 3),
            "group_blue_duration" => TimeFormatController::formatSecondToTime($_arrOfGroupBlueDuration),
            "group_blue_distance" => number_format($_arrOfGroupBlueDistance / 1000, 3),
        );
    }

}
