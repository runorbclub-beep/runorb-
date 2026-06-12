<?php


namespace App\Http\Controllers\PublicFunction;


use App\Http\CommonClass\TimeFormatController;
use App\Models\PkRoom;
use App\Models\UserPkList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PkController
{

    /**
     * @param $_data
     */
    public static function butweenPlay($_data)
    {
        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

        if (array_key_exists($_data["user_group"], $_arrOfPkRoom) && array_key_exists($_data["user_id"], $_arrOfPkRoom[$_data["user_group"]])) {
            $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["circle_count"] = $_data["circle_detail"];
            $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["speed"] = $_data["speed_detail"];
            $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["speed"] = $_data["speed_detail"];

            if (isset($_data["is_abnormal"]) && $_data["is_abnormal"] == 1) {
                $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["is_abnormal"] = $_data["is_abnormal"];
            }

            Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));
            return "between_play";
        } else {
            return "between_play_error";
        }

    }


    /**
     * @abstract  用户准备
     * @param $_data
     * @return string
     */
    public static function pkReady($_data)
    {
        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

        if (array_key_exists($_data["user_group"], $_arrOfPkRoom) && array_key_exists($_data["user_id"], $_arrOfPkRoom[$_data["user_group"]])) {
            $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["is_ready"] = 1;
        } else {
            return "pkListChange";
        }


        Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

        $_arrOfPkGroupRed = $_arrOfPkRoom["red"];
        $_arrOfPkGroupBlue = $_arrOfPkRoom["blue"];

        $_is_ready = 1;
        foreach ($_arrOfPkGroupRed as $value) {
            if ($value["is_ready"] == 0) {
                $_is_ready = 0;
            }
        }
        foreach ($_arrOfPkGroupBlue as $value) {
            if ($value["is_ready"] == 0) {
                $_is_ready = 0;
            }
        }

//        开始游戏
        if ($_is_ready == 1 && count($_arrOfPkGroupRed) > 0 && count($_arrOfPkGroupBlue) > 0) {
            $_arrOfPkRoom["status"] = 2;
            $_arrOfPkRoom["pk_start_time"] = time();//更新开始PK时间
            $_arrOfPkRoom["pk_stop_time"] = $_arrOfPkRoom["pk_start_time"] + 9 + $_arrOfPkRoom["time_long"];
            Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));


            Log::info("Pk 状态变更：" . $_arrOfPkRoom["status"]);

            return "pkStart";
        } else {
            return "pkListChange";
        }
    }

    /**
     * @abstract 用户取消准备
     * @param $_data
     * @return string
     */
    public static function pkUnReady($_data)
    {
        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

        $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["is_ready"] = 0;

        Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

        return "pkListChange";
    }


    /**
     * @abstract 用户结束PK
     * @param $_data
     * @return string
     */
    public static function pkStop($_data)
    {
        Redis::select(1);
        Redis::rpush("user_pk_data", json_encode($_data));
        return "pkListChange";
    }

    /**
     * @abstract 用户退出PK
     * @param $_data
     * @return string
     */
    public static function pkCancel($_data)
    {

        Redis::select(1);
        Redis::rpush("user_pk_data", json_encode($_data));

        Redis::select(13);

        $_arrOfPkRoom = json_decode(Redis::get($_data["pk_room_id"]), true);

//        $_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]["is_stop"] = 1;

//        退出PK
        unset($_arrOfPkRoom[$_data["user_group"]][$_data["user_id"]]);

        Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

        return "pkListChange";

        $_arrOfPkGroupRed = $_arrOfPkRoom["red"];
        $_arrOfPkGroupBlue = $_arrOfPkRoom["blue"];

//        如果PK已开始
        if ($_arrOfPkRoom["status"] == 2) {
//            存在一个队伍人数为0；判定结果
            if (count($_arrOfPkGroupRed) == 0 || count($_arrOfPkGroupBlue) == 0) {
                $_arrOfPkRoom["status"] = 3;
                Redis::setex($_data["pk_room_id"], 3600 * 24, json_encode($_arrOfPkRoom));

                return "pkResult";
            }

            return "pkListChange";
        } else {
            return "pkListChange";
        }
    }

    /**
     * @abstract PK结束
     * @param $_pk_room_id
     * @return array
     */
    public static function pkResult($_pk_room_id)
    {
        Log::info("计算PK结果：" . $_pk_room_id);
        Redis::select(13);
        $_arrOfPkRoom = json_decode(Redis::get($_pk_room_id), true);

        $_arrOfGroupRedDuration = 0;//红队总时间
        $_arrOfGroupRedDistance = 0;//红队总圈数
        $_arrOfGroupBlueDuration = 0;//蓝队总时间
        $_arrOfGroupBlueDistance = 0;//蓝队总圈数

        $_arrOfUserPkListId = array();
        foreach ($_arrOfPkRoom["red"] as $value) {

            if (!isset($value["is_abnormal"]) || (isset($value["is_abnormal"]) && $value["is_abnormal"] == 0)) {
                $_arrOfGroupRedDistance += $value["circle_count"][count($value["circle_count"]) - 1];
                if (isset($value["duration"])) {
                    $_arrOfGroupRedDuration += $value["duration"];
                }
            }


            $_arrOfUserPkListId[$value["user_pk_list_id"]] = array(
                "user_id" => $value["user_id"],
                "pk_room_id" => $value["pk_room_id"],
                "user_pk_list_id" => $value["user_pk_list_id"],
                "user_group" => $value["user_group"],
                "circle_count" => $value["circle_count"][count($value["circle_count"]) - 1],
                "status" => 1
            );

        }

        foreach ($_arrOfPkRoom["blue"] as $value) {

            if (!isset($value["is_abnormal"]) || (isset($value["is_abnormal"]) && $value["is_abnormal"] == 0)) {
                $_arrOfGroupBlueDistance += $value["circle_count"][count($value["circle_count"]) - 1];
                if (isset($value["duration"])) {
                    $_arrOfGroupBlueDuration += $value["duration"];
                }
            }

            $_arrOfUserPkListId[$value["user_pk_list_id"]] = array(
                "user_id" => $value["user_id"],
                "pk_room_id" => $value["pk_room_id"],
                "user_pk_list_id" => $value["user_pk_list_id"],
                "user_group" => $value["user_group"],
                "circle_count" => $value["circle_count"][count($value["circle_count"]) - 1],
                "status" => 1
            );
        }

        foreach ($_arrOfUserPkListId as $key => $value) {
            UserPkList::where([
                "user_pk_list_id" => $key
            ])->update($value);
        }

//        胜利方
        $_group_win = "";

        if ($_arrOfPkRoom["pk_result_type"] == 0) {
            if (count($_arrOfPkRoom["red"]) == 0 && count($_arrOfPkRoom["blue"]) > 0) {
                $_group_win = "blue";
            } else if (count($_arrOfPkRoom["blue"]) == 0 && count($_arrOfPkRoom["red"]) > 0) {
                $_group_win = "red";
            } else {
//            固定距离，用时短方获胜
                $_group_win = $_arrOfGroupRedDuration < $_arrOfGroupBlueDuration ? "red" : "blue";
            }

        } else if ($_arrOfPkRoom["pk_result_type"] == 1) {
            if (count($_arrOfPkRoom["red"]) == 0 && count($_arrOfPkRoom["blue"]) > 0) {
                $_group_win = "blue";
            } else if (count($_arrOfPkRoom["blue"]) == 0 && count($_arrOfPkRoom["red"]) > 0) {
                $_group_win = "red";
            } else {
//            固定时间,距离长方获胜
                $_group_win = $_arrOfGroupRedDistance > $_arrOfGroupBlueDistance ? "red" : "blue";
            }
        }

        Log::info("pk_result_type：" . $_arrOfPkRoom["pk_result_type"]);
        Log::info("PK_user_group_win：" . $_group_win);

        UserPkList::where([
            "status" => 1,
            "pk_room_id" => $_pk_room_id
        ])->update([
            "group_win" => $_group_win
        ]);

        PkRoom::where([
            "pk_room_id" => $_pk_room_id
        ])->update([
            "group_win" => $_group_win,
            "status" => 3,//PK 已结束
            "stop_time" => $_arrOfPkRoom["pk_stop_time"] ?? time(),//PK 結束時間
        ]);

        $_arrOfPkRoom["status"] = 3;
        Redis::setex($_pk_room_id, 3600 * 24, json_encode($_arrOfPkRoom));


        return array(
            "group_win" => $_group_win,
            "group_red_duration" => TimeFormatController::formatSecondToTime($_arrOfGroupRedDuration),
            "group_red_distance" => number_format($_arrOfGroupRedDistance * StaticDataController::$_circle_distance / 1000 / 100, 3),
            "group_blue_duration" => TimeFormatController::formatSecondToTime($_arrOfGroupBlueDuration),
            "group_blue_distance" => number_format($_arrOfGroupBlueDistance * StaticDataController::$_circle_distance / 1000 / 100, 3),
        );
    }
}
