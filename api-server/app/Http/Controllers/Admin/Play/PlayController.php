<?php

namespace App\Http\Controllers\Admin\Play;


use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\UserAchievement;
use App\Models\UserMedalAssociated;
use App\Models\UserPlay;
use Illuminate\Support\Facades\Redis;


class PlayController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/6/7 10:48
     * @abstract _查询运动列表
     */
    public function postUserPlay()
    {

        $_data = request()->input();

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_play_time_arr = isset($_data["play_time"]) ? $_data["play_time"] : array();

        $_start_time = count($_play_time_arr) == 2 ? strtotime($_play_time_arr[0]) : "";
        $_stop_time = count($_play_time_arr) == 2 ? strtotime($_play_time_arr[1]) : "";

        $_order_by_type = isset($_data["order_by_type"]) ? $_data["order_by_type"] : "";
        $_order_by = isset($_data["order_by"]) ? $_data["order_by"] : "DESC";
        $_is_abnormal = isset($_data["is_abnormal"]) ? $_data["is_abnormal"] : "";

        $_arrOfUserPlayQuery = UserPlay::where([
            "user_play.status" => 1
        ])->join("usr_user", function ($join) {
            $join->on("user_play.user_id", "=", "usr_user.user_id");
        });

        if ($_is_abnormal != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where(["user_play.is_abnormal" => $_is_abnormal]);
        }

        if ($_start_time != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->whereBetween("user_play.start_time", array($_start_time, $_stop_time));
        }

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "user_play.user_play_id", "user_play.duration"
            , "user_play.speed_max", "user_play.circle_count", "user_play.distance", "user_play.start_time", "user_play.stop_time"
            , "user_play.is_abnormal", "user_play.exponent", "user_play.exponent_molecular"
        );

//        若存在指定排序
        if ($_order_by_type != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play." . $_order_by_type, $_order_by);
        }

//        二次排序
        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play.start_time", "DESC");


        $_arrOfUserPlayCount = $_arrOfUserPlayQuery->count();
        $_arrOfUserPlay = $_arrOfUserPlayQuery->skip($_offset)->take($_limit)->get();


        foreach ($_arrOfUserPlay as $value) {
            $value["distance_format"] = round($value["distance"] / 1000, 3);
            $value["start_time_format"] = date("Y-m-d H:i:s", $value["start_time"]);
            $value["stop_time_format"] = date("Y-m-d H:i:s", $value["stop_time"]);
            $value["user_img"] = StaticDataController::$_server_url . "/" . $value["user_img"];

            $value["duration_format"] = RankController::timeFormat($value["duration"]);

            unset($value["start_time"]);
            unset($value["stop_time"]);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfUserPlayCount,
                "list" => $_arrOfUserPlay
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/30 20:57
     * @abstract _运动数据删除
     */
    public function postUserPlayDelete()
    {
        $_data = request()->input();

        //语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["user_play_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_token_key = "admin_user_token:" . request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);

        //异常运动删除
        UserPlay::where([
            "user_play_id" => $_data["user_play_id"]
        ])->update([
            "status" => 0,
            "updated_uid" => $_admin_user["admin_user_id"]
        ]);

        //异常运动关联徽章删除
        UserMedalAssociated::where([
            "status" => 1,
            "user_play_id" => $_data["user_play_id"]
        ])->update([
            "status" => 0
        ]);

        //删除对应的我的成就
        $_user_id = UserPlay::where('user_play_id', $_data["user_play_id"])->value('user_id');
        $_max_user_play = UserPlay::where('user_id', $_user_id)
            ->where('status', 1)
            ->selectRaw('max(duration) as maxDuration, max(speed_max) as speedMax, max(circle_count) as circleCount, max(endurance_max) as enduranceMax,
                max(distance) as distanceMax, max(exponent) as exponentMax, min(marathon) as marathonMin, max(exponent_molecular) as exponentMolecularMax
            ')
            ->first();
        if ($_max_user_play) {
            UserAchievement::where('user_id', $_user_id)
                ->update([
                    'duration' => $_max_user_play->maxDuration ?? 0,
                    'speed_max' => $_max_user_play->speedMax ?? 0,
                    'circle_count' => $_max_user_play->circleCount ?? 0,
                    'endurance_max' => $_max_user_play->enduranceMax ?? 0,
                    'runball_exponent' => $_max_user_play->exponentMax ?? 0,
                    'marathon' => $_max_user_play->marathonMin ?? 0,
                    'exponent_molecular' => $_max_user_play->exponentMolecularMax ?? 0,
                ]);
        }
        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/7 10:58
     * @abstract _异常数据
     */
    public function postAbnormalList()
    {

        Redis::select(1);

        $_arrOfAbnormal = json_decode(Redis::get("play_abnormal"), true);

        if ($_arrOfAbnormal == "null" || $_arrOfAbnormal == "") {
            $_arrOfAbnormal = StaticDataController::$_err_play_speed;

            Redis::set("play_abnormal", json_encode($_arrOfAbnormal));
        }

        foreach ($_arrOfAbnormal as $key => $value) {
            $value["id"] = time() + $key;
            $_arrOfAbnormal[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfAbnormal
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/7 11:26
     * @abstract _更新异常运动判定
     */
    public function postAbnormalUpdate()
    {
        Redis::select(1);
        $_data = request()->input();

//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["play_abnormal"])) {
            return SystemErrorController::paramtersError($_language);
        }

        foreach ($_data["play_abnormal"] as $key => $value) {
            if ($value == null) {
                unset($_data["play_abnormal"][$key]);
            }
        }

        Redis::set("play_abnormal", json_encode($_data["play_abnormal"]));

        return array(
            "code" => 1,
            "msg" => "更新成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/7/2 15:01
     * @abstract _运动筛选条件
     */
    public function postPlayCheckData()
    {

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "is_abnormal" => array(
                    "form_label" => "运动数据状态",
                    "form_type" => "button",
                    "form_name" => "is_abnormal",
                    "require" => array(
                        array(
                            "key" => 0,
                            "value" => 0,
                            "label" => "正常"
                        ),
                        array(
                            "key" => 1,
                            "value" => 1,
                            "label" => "异常"
                        )
                    )
                ),
                "play_time" => array(
                    "form_label" => "运动时间",
                    "form_type" => "arr_date_time",
                    "form_name" => "play_time",
                    "require" => array(
                        array(
                            "key" => "start_time",
                            "value" => "start_time",
                            "label" => "开始时间"
                        ),
                        array(
                            "key" => "stop_time",
                            "value" => "stop_time",
                            "label" => "结束时间"
                        )
                    )
                ),
                "play_date" => array(
                    "form_label" => "排序项",
                    "form_type" => "button",
                    "form_name" => "order_by_type",
                    "require" => array(
                        array(
                            "key" => "duration",
                            "value" => "duration",
                            "label" => "持续时间"
                        ),
                        array(
                            "key" => "speed_max",
                            "value" => "speed_max",
                            "label" => "最高转速"
                        ),
                        array(
                            "key" => "circle_count",
                            "value" => "circle_count",
                            "label" => "运动圈数"
                        ),
                        array(
                            "key" => "exponent_molecular",
                            "value" => "exponent_molecular",
                            "label" => "摇跑一分钟"
                        ),
                        array(
                            "key" => "exponent",
                            "value" => "exponent",
                            "label" => "摇跑指数"
                        )
                    )
                ),
                "order_by" => array(
                    "form_label" => "排序选项",
                    "form_type" => "button",
                    "form_name" => "order_by",
                    "require" => array(
                        array(
                            "key" => "ASC",
                            "value" => "ASC",
                            "label" => "升序"
                        ),
                        array(
                            "key" => "DESC",
                            "value" => "DESC",
                            "label" => "降序"
                        )
                    )
                )
            )
        );
    }


    /**
     * @author JKD
     * @time 2021/7/6 13:02
     * @abstract _查询每日之星列表
     */
    public function postUserPlayStar()
    {

        $_data = request()->input();
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;
        $_order_by = $_data['order_by'] ?? 'DESC';
        $_order_by = $_order_by == 'DESC' ? SORT_DESC : SORT_ASC;

        //早-时间
        $_data['play_time'] = $_data["morning_play_time"] ?? [];
        $morningData = $this->getUserPlayStarData($_data);

        //中-时间
        $_data['play_time'] = $_data["noon_play_time"] ?? [];
        $noonData = $this->getUserPlayStarData($_data);

        //晚-时间
        $_data['play_time'] = $_data["night_play_time"] ?? [];
        $nightData = $this->getUserPlayStarData($_data);

        $data = array_merge($morningData, $noonData, $nightData);
        $morningData = null;
        $noonData = null;
        $nightData = null;

        $allData = [];
        foreach ($data as &$da) {
            $da["duration_format"] = $da["duration"] ? RankController::timeFormat($da["duration"]) : '';
            $thisData = $allData[$da['user_id']] ?? [];
            $allData[$da['user_id']] = $da;
            $maxVal = ($thisData['maxVal'] ?? 0) + $da['maxVal'];
            $allData[$da['user_id']]['maxVal'] = $maxVal;
            $thisData = null;
            $maxVal = null;
        }
        array_multisort(array_column($allData, 'maxVal'), $_order_by, $allData);

        $_arrOfUserPlayCount = count($allData);
        $res = [];
        $totalLimit = $_offset + $_limit - 1;
        foreach ($allData as $i => $allDa) {
            if ($_offset <= $i && $totalLimit >= $i) {
                $thisRes = $allData[$i];
                $thisRes["distance_format"] = round($thisRes["distance"] / 1000, 3);
                $thisRes["user_img"] = StaticDataController::$_server_url . "/" . $thisRes["user_img"];
                $res[] = $thisRes;
            }
        }
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfUserPlayCount,
                "list" => $res
            )
        );
    }


    /**
     * @param $_data
     * @return array
     * @author JKD
     * @time 2021/7/6 13:22
     * @abstract _查询时间段内用户最好的数据
     */
    public function getUserPlayStarData($_data)
    {
        //早-时间
        $_time_arr = $_data["play_time"] ?? [];
        $_start_time = count($_time_arr) == 2 ? strtotime($_time_arr[0]) : "";
        $_stop_time = count($_time_arr) == 2 ? strtotime($_time_arr[1]) : "";

        $_order_by_type = $_data["order_by_type"] ?? 'exponent_molecular';
        $_is_abnormal = $_data["is_abnormal"] ?? '';
        $is_members = $_data['is_members'] ?? '';

        $_arrOfUserPlayQuery = UserPlay::where([
            "user_play.status" => 1
        ])->join("usr_user", function ($join) {
            $join->on("user_play.user_id", "=", "usr_user.user_id");
        });


        if ($_is_abnormal != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where(["user_play.is_abnormal" => $_is_abnormal]);
        }

        if ($_order_by_type != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("user_play." . $_order_by_type, '>', 0);
        }

        if ($is_members != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("usr_user.is_members", $is_members);
        }

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->selectRaw(
            'usr_user.user_id,usr_user.user_name,usr_user.is_members,usr_user.user_img,user_play.user_play_id,user_play.duration,user_play.speed_max,user_play.circle_count,
            user_play.distance,user_play.is_abnormal
            ,user_play.exponent,user_play.exponent_molecular,user_play.exponent_denominator,user_play.marathon
            ,max(user_play.' . $_order_by_type . ') as maxVal'
        );
        if ($_start_time != "") {
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->whereBetween("user_play.start_time", array($_start_time, $_stop_time));
        }
        $_arrOfUserPlayCount = $_arrOfUserPlayQuery->groupBy("usr_user.user_id")->get();

        return $_arrOfUserPlayCount ? $_arrOfUserPlayCount->toArray() : [];
    }

}
