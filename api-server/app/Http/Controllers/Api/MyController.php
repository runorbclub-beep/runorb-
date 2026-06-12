<?php


namespace App\Http\Controllers\Api;


use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\PkGroupBlue;
use App\Models\PkGroupRed;
use App\Models\PkRoom;
use App\Models\UserGroupAssociated;
use App\Models\UserPkList;
use App\Models\UserPlay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MyController extends Controller
{

    /**
     * @abstract 我的赛事
     * @param Request $request
     * @return array
     */
    public function myMatch(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

//        先查询用户所在队伍
        $_arrOfUserGroupAssociated = UserGroupAssociated::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"]
        ])->select("user_group_id")->get();

        $_user_group_id = array();
        foreach ($_arrOfUserGroupAssociated as $value) {
            array_push($_user_group_id, $value["user_group_id"]);
        }


        $_arrOfMatchUserQuery = MatchsUser::where([
            "matchs_user.status" => 1,
            "sys_match.status" => 1,
        ])->join("sys_match", function ($join) {
            $join->on("matchs_user.sys_match_id", "=", "sys_match.sys_match_id");
        })->join("sys_match as sys_sys_match", function ($join) {
            $join->on("matchs_user.sys_sys_match_id", "=", "sys_sys_match.sys_match_id");
        })
            ->whereIn("matchs_user.user_group_id", $_user_group_id)
            ->orwhere("matchs_user.user_id", "=", $_usr_user["user_id"]);


        $_arrOfMatchUserQuery = $_arrOfMatchUserQuery->select(
            "sys_match.match_champion_prize_description", "sys_match.sys_match_id", "sys_sys_match.sys_match_id as sys_sys_match_id"
            , "sys_sys_match.match_title", "sys_sys_match.match_start_time"
            , "sys_sys_match.match_stop_time", "sys_sys_match.match_image", "sys_sys_match.match_status", "sys_sys_match.match_user_sign_count"
            , "sys_sys_match.is_group"
        )->orderBy("sys_sys_match.match_start_time", "DESC");

        $_arrOfMatchUserCount = $_arrOfMatchUserQuery->get();
        $_arrOfMatchUser = $_arrOfMatchUserQuery->skip($_offset)->take($_limit)->get();

        $_arrOfMatchUserKey = array();
        foreach ($_arrOfMatchUser as $key => $value) {
            $value["start_time"] = date("Y-m-d H:i:s", $value["match_start_time"]);
            $value["stop_time"] = date("Y-m-d H:i:s", $value["match_stop_time"]);

            $value["match_image"] = StaticDataController::$_server_url . "/" . $value["match_image"];

            $value["match_champion_prize_description"] = $value["match_champion_prize_description"] == null ? "" : $value["match_champion_prize_description"];

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

            $value["matchs_stage_id"] = "";
            $value["view_type"] = 0;

            $_arrOfMatchUserKey[$value["sys_match_id"]] = $value;
        }


//        查询赛段
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->whereIn("sys_match_id", array_keys($_arrOfMatchUserKey))->select(
            "matchs_stage_id", "view_type", "sys_match_id", "match_stage_start_time", "match_stage_stop_time", "is_exponent"
        )->get();


        foreach ($_arrOfMatchStage as $key => $value) {
            if ($value["match_stage_start_time"] < time() && $value["match_stage_stop_time"] > time()) {
                $_arrOfMatchUserKey[$value["sys_match_id"]]["matchs_stage_id"] = $value["matchs_stage_id"];
                $_arrOfMatchUserKey[$value["sys_match_id"]]["view_type"] = $value["view_type"];
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfMatchUserCount),
                "list" => array_values($_arrOfMatchUserKey)
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/22 17:13
     * @abstract _查询赛事下的赛段
     */
    public function myMatchStage(Request $request)
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
            "matchs_stage_id", "match_stage_title", "match_stage_start_time", "match_stage_stop_time", "is_exponent" ,"match_stage_distance"
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
    public function myMatchInfo(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

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
        } else {
            $_order_by_type = 'ASC';
        }

//        $_arrOfMatchsUserGradeQuery = MatchsUserGrade::where([
//            "matchs_user_grade.is_group" => $_is_group,
//            "matchs_stage_id" => $_data["matchs_stage_id"]
//        ])->where("match_grade", '!=', 10000000000)
//            ->where("match_grade", '>', 0);

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
            ->where("matchs_user.sys_match_id", $_data["sys_match_id"]);
//            ->where("matchs_user.user_group_finish_time", '!=', null);
//            ->whereRaw("matchs_user.user_group_finish_time != null OR matchs_user.stage_pass = 1");
//            ->whereRaw(" matchs_user.stage_pass = 1 OR matchs_user.user_group_finish_time != null ");

        if ($_is_group == 1) {
            $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->join("user_group", function ($join) {
                $join->on("matchs_user_grade.user_group_id", "=", "user_group.user_group_id");
            })->select(
                "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "user_group.group_title as name", "user_group.group_logo as image"
                , "matchs_user_grade.matchs_user_grade_id", "matchs_user_grade.created_time as time"
            );
        } else {
            $_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->join("usr_user", function ($join) {
                $join->on("matchs_user_grade.user_id", "=", "usr_user.user_id");
            })->select(
                "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "usr_user.user_name as name", "usr_user.user_img as image"
                , "matchs_user_grade.matchs_user_grade_id", "usr_user.address as address", "matchs_user_grade.created_time as time"
            );
        }

        /*$_arrOfMatchsUserGradeQuery = $_arrOfMatchsUserGradeQuery->groupByRaw('name');
        $_arrOfMatchsUserGrade = $_arrOfMatchsUserGradeQuery->orderBy("matchs_user_grade.match_ranking", $_order_by_type)->skip($_offset)->take($_limit)->get();
        $_arrOfMatchsUserGradeCount = count($_arrOfMatchsUserGrade);*/

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
            $value["match_ranking"] = $key + 1;
            $value['time'] = date('Y-m-d H:i:s',$value['time']);

            $_arrOfMatchsUserGrade[$key] = $value;
        }

        $_myGrade = array(
            "match_grade" => 0,
            "match_ranking" => 0,
            "created_time" => null,
            "user_id" => $_usr_user["user_id"],
            "user_name" => $_usr_user["user_name"],
            "user_img" => $_usr_user["user_img"],
            "address" => $_usr_user["address"],
            "sys_sex_id" => $_usr_user["sys_sex_id"],
        );
        if ($_is_group == 1) {

            $_arrOfMyGrade = MatchsUserGrade::where([
                "matchs_user_grade.is_group" => $_is_group,
                "user_group_associated.status" => 1,
                "user_group_associated.user_id" => $_usr_user["user_id"],
                "matchs_stage_id" => $_data["matchs_stage_id"]
            ])->where('matchs_user_grade.match_grade', '!=', 10000000000)
                ->where('matchs_user_grade.match_grade', '>', 0)
                ->join("user_group_associated", function ($join) {
                    $join->on("matchs_user_grade.user_group_id", "=", "user_group_associated.user_group_id");
                })
                ->join("usr_user", function ($join) {
                    $join->on("matchs_user_grade.matchs_user_id", "=", "usr_user.user_id");
                })
                ->select(
                    "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "matchs_user_grade.created_time",
                    "usr_user.user_id", "usr_user.user_name", DB::raw("CONCAT('".StaticDataController::$_server_url . "/',usr_user.user_img) as user_img"), "usr_user.address","usr_user.sys_sex_id"
                )
                ->get();

            if (count($_arrOfMyGrade) == 1) {
//                $_arrOfMyGrade[0]['user_img'] = StaticDataController::$_server_url . "/" . $_arrOfMyGrade[0]['user_img'];
                $_myGrade = $_arrOfMyGrade[0];
            }
        } else {
            $_arrOfMyGrade = MatchsUserGrade::where([
                "matchs_user_grade.is_group" => $_is_group,
                "matchs_user_grade.user_id" => $_usr_user["user_id"],
                "matchs_user_grade.matchs_stage_id" => $_data["matchs_stage_id"]
            ])->where('matchs_user_grade.match_grade', '!=', 10000000000)
                ->where('matchs_user_grade.match_grade', '>', 0)
                ->join("usr_user", function ($join) {
                    $join->on("matchs_user_grade.matchs_user_id", "=", "usr_user.user_id");
                })
                ->select(
                    "matchs_user_grade.match_grade", "matchs_user_grade.match_ranking", "matchs_user_grade.created_time",
                    "usr_user.user_id", "usr_user.user_name", DB::raw("CONCAT('".StaticDataController::$_server_url . "/',usr_user.user_img) as user_img"), "usr_user.address","usr_user.sys_sex_id"
                )->get();
            if (count($_arrOfMyGrade) == 1) {
//                $_arrOfMyGrade[0]['user_img'] = StaticDataController::$_server_url . "/" . $_arrOfMyGrade[0]['user_img'];
                $_myGrade = $_arrOfMyGrade[0];
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfMatchsUserGradeCount,
                "list" => $_arrOfMatchsUserGrade,
                "my_grade" => $_myGrade,
                'is_exponent' => $_is_exponent
            )
        );
    }


    /**
     * @abstract 我的PK
     * @param Request $request
     * @return array
     */
    public function myPkList(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_arrOfPkListQuery = UserPkList::where([
            "user_pk_list.status" => 1,
            "user_pk_list.user_id" => $_usr_user["user_id"]
        ])->join("user_play", function ($join) {
            $join->on("user_pk_list.user_pk_list_id", "=", "user_play.user_pk_list_id");
        })->join("pk_room", function ($join) {
            $join->on("user_pk_list.pk_room_id", "=", "pk_room.pk_room_id");
        })->select(
            "user_play.created_time", "pk_room.pk_room_id", "user_play.user_play_id", "user_play.speed_max", "user_play.distance"
            , "pk_room.group_win", "user_pk_list.user_pk_list_id", "user_pk_list.user_group", "pk_room.pk_type"
        )->orderBy("user_play.created_time", "DESC");

//        分页
        $_arrOfPkListCount = $_arrOfPkListQuery->get();
        $_arrOfPkList = $_arrOfPkListQuery->skip($_offset)->take($_limit)->get();


//        格式化数据
        foreach ($_arrOfPkList as $key => $value) {
            $value["start_date"] = date("Y.m.d H:i", $value["created_time"]);
            $value["is_win"] = 0;

            unset($value["created_time"]);


            if ($value["group_win"] != null && $value["group_win"] == $value["user_group"]) {
                $value["is_win"] = 1;
            }
            $value["distance"] = (string)number_format($value["distance"] / 1000, 3);
            $_arrOfPkList[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfPkListCount),
                "list" => $_arrOfPkList
            )
        );
    }

    /**
     * 我的PK==v2
     * @param Request $request
     * @return JsonResponse
     */
    public function myPkListV2(Request $request): JsonResponse
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header('token');
        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 10;
        $_offset = ($_page - 1) * $_limit;

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_data["source"] = $_data["source"] ?? 0;

        //获取用户已结束的PK列表
        $userPkListQuery = UserPkList::where([
            'user_pk_list.user_id' => $_usr_user['user_id'],
            'user_pk_list.status' => 1
        ])
            ->join('pk_room',function ($join){
                $join->on('pk_room.pk_room_id','=','user_pk_list.pk_room_id');
            })
            ->join('usr_user',function ($join){
                $join->on('usr_user.user_id','=','user_pk_list.user_id');
            })
            ->join('user_pk_list as user_pk_list_b',function ($join){
                $join->on('user_pk_list_b.pk_room_id','=','user_pk_list.pk_room_id');
                $join->on('user_pk_list_b.user_id','<>','user_pk_list.user_id');
            })
            ->join('usr_user as usr_user_b',function ($join){
                $join->on('usr_user_b.user_id','=','user_pk_list_b.user_id');
            });

        if ($_data["source"] == 1){//胜
            $userPkListQuery = $userPkListQuery->whereColumn([['user_pk_list.user_group','=','user_pk_list.group_win']]);
        }elseif ($_data["source"] == 2){//负
            $userPkListQuery = $userPkListQuery->whereColumn([['user_pk_list.user_group','!=',"user_pk_list.group_win"]]);
        }

        $userPkListQuery = $userPkListQuery->selectRaw("user_pk_list.user_pk_list_id,user_pk_list.pk_room_id,user_pk_list.user_group,user_pk_list.group_win,user_pk_list.user_group_title,ROUND((IF(SUM(IF(user_pk_list.user_group=user_pk_list_b.user_group,user_pk_list_b.distance,null)),SUM(IF(user_pk_list.user_group=user_pk_list_b.user_group,user_pk_list_b.distance,null)),0)+user_pk_list.distance)/1000,3) AS distance,IF(user_pk_list.group_win=user_pk_list.user_group AND user_pk_list.user_group is not null,1,0) AS is_win,pk_room.pk_type,FROM_UNIXTIME(pk_room.stop_time,'%Y-%m-%d %H:%i:%s') AS stop_time,pk_room.stop_time AS stop_time_nuix,usr_user.sys_sex_id,usr_user.user_name,CONCAT('".StaticDataController::$_server_url . "/',usr_user.user_img) as user_img,COUNT(IF(user_pk_list.user_group=user_pk_list_b.user_group,true,null))+1 AS my_count,usr_user_b.sys_sex_id as b_sys_sex_id,usr_user_b.user_name as b_user_name,CONCAT('".StaticDataController::$_server_url . "/',usr_user_b.user_img) as b_user_img,COUNT(IF(user_pk_list.user_group!=user_pk_list_b.user_group,true,null)) AS b_count,user_pk_list_b.user_group AS b_user_group,user_pk_list_b.user_group_title AS b_user_group_title,ROUND(IF(SUM(IF(user_pk_list.user_group!=user_pk_list_b.user_group,user_pk_list_b.distance,null)),SUM(IF(user_pk_list.user_group!=user_pk_list_b.user_group,user_pk_list_b.distance,null)),0)/1000,3) AS b_distance,'km' AS unit")
            ->orderBy("pk_room.stop_time", "DESC")//ROUND((IF(SUM(IF(user_pk_list.user_group=user_pk_list_b.user_group,user_pk_list_b.distance,null)),SUM(IF(user_pk_list.user_group=user_pk_list_b.user_group,user_pk_list_b.distance,null)),0)+user_pk_list.distance)/1000,3) as aas
            ->groupBy("user_pk_list.user_pk_list_id");//ROUND(SUM(user_pk_list_b.distance)/1000,3)

//        分页
        $userPkListCount = count($userPkListQuery->get());
        $userPkList = $userPkListQuery->skip($_offset)->take($_limit)->get();

        return $this->success(['count'=>$userPkListCount,'list'=>$userPkList]);
    }

    /**
     * @abstract 查看PK 详情
     * @param Request $request
     * @return array
     */
    public function myPkListInfo(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["pk_room_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserPkList = UserPkList::where([
            "user_pk_list.status" => 1,
            "user_pk_list.pk_room_id" => $_data["pk_room_id"],
        ])->join("usr_user", function ($join) {
            $join->on("usr_user.user_id", "=", "user_pk_list.user_id");
        })->join("pk_room", function ($join) {
            $join->on("pk_room.pk_room_id", "=", "user_pk_list.pk_room_id");
        })->select(
            "user_pk_list.user_pk_list_id", "user_pk_list.duration", "user_pk_list.user_group", "user_pk_list.group_win", "user_pk_list.created_time"
            , "usr_user.user_id", "usr_user.user_name", "usr_user.sys_sex_id", "usr_user.user_img", "pk_room.group_red_title", "pk_room.group_blue_title", "pk_room.pk_type"
        )->get();

        $_arrOfUserPkListId = array();
        $_arrOfUserPkListKey = array();

        foreach ($_arrOfUserPkList as $value) {
            array_push($_arrOfUserPkListId, $value["user_pk_list_id"]);
            $value["speed_max"] = 0;
            $value["distance"] = 0;
            $_arrOfUserPkListKey[$value["user_pk_list_id"]] = $value;
        }

        $_arrOfUserPlay = UserPlay::where([
            "status" => 1,
        ])->whereIn("user_pk_list_id", $_arrOfUserPkListId)->select(
            "created_time", "speed_max", "distance", "user_pk_list_id"
        )->get();


        foreach ($_arrOfUserPlay as $value) {
            $_arrOfUserPkListKey[$value["user_pk_list_id"]]["speed_max"] = $value["speed_max"];
            $_arrOfUserPkListKey[$value["user_pk_list_id"]]["distance"] = $value["distance"];
        }

        $_arrOfUserPkListValue = array_values($_arrOfUserPkListKey);


        foreach ($_arrOfUserPkListValue as $key => $value) {
            if (!strstr($value["user_img"], 'http')) {
                $value["user_img"] = StaticDataController::$_server_url . "/" . $value["user_img"];
            }

            $value["distance"] = number_format($value["distance"] / 1000, 3);
            $value["start_date"] = date("Y.m.d H:i", $value["created_time"]);

            unset($value["created_time"]);

            $_arrOfUserPkListValue[$key] = $value;
        }


        if (count($_arrOfUserPkListValue) > 0 && $_arrOfUserPkListValue[0]["pk_type"] == 0) {
//            双人PK

            $_arrOfUserPkResult = array();
            foreach ($_arrOfUserPkListValue as $key => $value) {
                unset($value["group_red_title"]);
                unset($value["group_blue_title"]);

                $value["is_win"] = 0;

                if ($value["group_win"] != null && $value["group_win"] == $value["user_group"]) {
                    $value["is_win"] = 1;
                }
                $_arrOfUserPkResult[$value["is_win"] + 1] = $value;
            }

            krsort($_arrOfUserPkResult);

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "pk_type" => 0,
                    "list" => array_values($_arrOfUserPkResult)
                )
            );

        } else if (count($_arrOfUserPkListValue) > 0 && $_arrOfUserPkListValue[0]["pk_type"] == 1) {
//            组队PK
            $_arrOfReturn = array(
                "red" => array(
                    "group_title" => "",
                    "is_win" => 0,
                    "list" => array()
                ),
                "blue" => array(
                    "group_title" => "",
                    "is_win" => 0,
                    "list" => array()
                ),
            );


            foreach ($_arrOfUserPkListValue as $key => $value) {

                if ($value["user_group"] == "red") {
                    $_arrOfReturn["red"]["group_title"] = $value["group_red_title"];
                    unset($value["group_red_title"]);
                    unset($value["group_blue_title"]);
                    if ($value["group_win"] != null && $value["group_win"] == $value["user_group"]) {
                        $_arrOfReturn["red"]["is_win"] = 1;
                    }
                    array_push($_arrOfReturn["red"]["list"], $value);
                } else {
                    $_arrOfReturn["blue"]["group_title"] = $value["group_blue_title"];
                    unset($value["group_red_title"]);
                    unset($value["group_blue_title"]);
                    if ($value["group_win"] != null && $value["group_win"] == $value["user_group"]) {
                        $_arrOfReturn["blue"]["is_win"] = 1;
                    }
                    array_push($_arrOfReturn["blue"]["list"], $value);
                }

            }

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "pk_type" => 1,
                    "list" => $_arrOfReturn
                )
            );

        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "pk_type" => 0,
                "list" => array()
            )
        );
    }




}
