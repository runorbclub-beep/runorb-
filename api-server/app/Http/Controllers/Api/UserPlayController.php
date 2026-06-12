<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\TimeFormatController;
use App\Http\Controllers\Api\Shake\ShakeController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Controllers\PublicFunction\UserPlayFunction;
use App\Http\Controllers\PublicFunction\UsrUserController;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\ShakeGroupUser;
use App\Models\UserAchievement;
use App\Models\UserPkList;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Models\UserTargetPunch;
use App\Models\UsrUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


/**
 * @author pengjl
 * @time 2021/5/7 21:47
 * Class AdminUserPlayController
 * @package App\Http\Controllers\Api
 * @abstract 用户运动相关接口
 */
class UserPlayController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/5/22 10:54
     * @abstract _运动相关数据接口
     */
    public function postPlayCheatData(Request $request)
    {

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "err_speed" => StaticDataController::$_err_play_speed,
                "init_circle_count" => StaticDataController::$_init_circle_count
            )
        );
    }


    /**
     * @abstract 开始运动，
     * @return array
     */
    public function postStartPlay(Request $request)
    {
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
//        如果是PK的运动，
        $_user_pk_list_id = isset($_data["user_pk_list_id"]) ? $_data["user_pk_list_id"] : "";

        //如果是摇加油
        $_sys_shake_id = $_data["sys_shake_id"] ?? '';

        //摇加油
        if ($_sys_shake_id != "") {
            $shake = new ShakeController();
            $_arr = $shake->getTodayShakeInfo();

            if ($_arr['start_time'] > time()) {
                return [
                    "code" => 0,
                    "msg" => '比赛未开始'
                ];
            }
            if ($_arr['stop_time'] <= time()) {
                return [
                    "code" => 0,
                    "msg" => '比赛已结束'
                ];
            }

            $_arrOfUserPlay["sys_shake_id"] = $_sys_shake_id;
        }

        $_user_token = $request->header('token');
        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        Redis::select(14);

        $_objSnowflake = new Snowflake(StaticDataController::$_workId);
        $_rand_id = $_objSnowflake->nextId();

        if (isset($_data["start_time"])) {
            $_start_time = $_data["start_time"];
        } else {
            $_start_time = time();
        }


        $_arrOfUserPlay = array(
            "user_play_id" => $_rand_id,
            "str_user_play_id" => (string)$_rand_id,
            "status" => 1,
            "created_uid" => $_usr_user["user_id"],
            "start_time" => $_start_time,
            "sys_start_time" => time(),
            "circle_detail" => array(),
            "speed_detail" => array(),
            "distance" => 0,
            "circle_count" => 0,
            "speed_max" => 0,
            "interval" => 0,
            "is_abnormal" => 0,
            "exponent_molecular" => 0,
            "exponent_denominator" => 0,
            "exponent" => 0,
            "marathon" => 0,
        );

        if ($_user_pk_list_id != "") {
            $_arrOfUserPlay["user_pk_list_id"] = $_user_pk_list_id;
        }
        if ($_sys_shake_id != "") {
            $_arrOfUserPlay["sys_shake_id"] = $_sys_shake_id;
        }

//        存在赛事ID
        if (isset($_data["sys_match_id"]) && isset($_data["matchs_stage_id"])) {
            $_arrOfUserPlay["matchs_stage_id"] = $_data["matchs_stage_id"];
            if (isset($_data["user_group_id"])) {
                $_redis_key = $_data["sys_match_id"] . ":" . $_data["matchs_stage_id"] . ":" . $_data["user_group_id"];
            } else {
                $_redis_key = $_data["sys_match_id"] . ":" . $_data["matchs_stage_id"] . ":" . $_usr_user["user_id"];
            }

            Redis::lpush($_redis_key, $_arrOfUserPlay["user_play_id"]);
        }

//        运动缓存数据，10天过期，
        Redis::setex($_rand_id, 3600 * 24 * 60, json_encode($_arrOfUserPlay));

//        Log::channel('debug')->info('开始运动-' . ($_usr_user["user_id"] ?? 0), [$_arrOfUserPlay]);

        //应前端要求返回shake/sign存的upload_type
        if (!empty($_sys_shake_id)){
            Redis::select(14);
            $upload_type = Redis::get($_usr_user['user_id'].'_'.$_sys_shake_id);
            if ($upload_type){
                $upload_type = json_decode($upload_type,true);
                if ($upload_type['upload_type'] == 1){
                    $upload_type['upload_type'] = 0;
                    Redis::set($_usr_user['user_id'].'_'.$_sys_shake_id,json_encode($upload_type));
                }
            }
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_play" => $_arrOfUserPlay,
                "err_speed" => StaticDataController::$_err_play_speed,
                'upload_type' => $upload_type ?? null
            )
        );
    }


    /**
     * @abstract 运动过程中
     * @param Request $request
     * @return array
     */
    public function postBetweenPlay(Request $request)
    {
        $_datas = $_data = $request->input();

        $_start_time = isset($_data["start_time"]) ? $_data["start_time"] : "";
        $_user_play_id = isset($_data["user_play_id"]) ? $_data["user_play_id"] : "";
        $_circle_detail = isset($_data["circle_detail"]) ? $_data["circle_detail"] : array();
        $_speed_detail = isset($_data["speed_detail"]) ? $_data["speed_detail"] : array();
        $currentTime = time();//$_data['current_time'] ?? time();    //请求时间
        $force = $_data['force'] ?? 0;

        //如果是摇加油
        $_sys_shake_id = $_data["sys_shake_id"] ?? '';

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if ($_start_time == "" || $_user_play_id == "" || count($_speed_detail) != count($_circle_detail)) {
            Log::info('提交数据错误：'.json_encode($_datas,true));
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $request->header('token')), true);

//        $_data['post_time'] = date('Y-m-d H:i:s');
//        Redis::hset('post_between_play_'.$_usr_user['user_id'].'_'.date('Y-m-d'),date('H:i:s'),json_encode($_data,true));
//        Log::info('缓存2222222222222222222222222222'.json_encode($_data,true));

        $_date = date("Y:m:d", $_start_time);
        Redis::select(14);
        //该次运动时数据
        $_user_play = json_decode(Redis::get($_user_play_id), true);

        if (isset($_data["is_abnormal"]) && $_data["is_abnormal"] == 1) {
            $_user_play["is_abnormal"] = $_data["is_abnormal"];
        }

        //如果没有找到圈数数据
        if (!isset($_user_play["circle_detail"]) && !$_sys_shake_id) {
            return array(
                "code" => 1,
                "msg" => "success",
            );
        }

        //摇加油时才需要
        if ($_sys_shake_id) {
            $_user_play["circle_detail"] = $_user_play["circle_detail"] ?? [];
            $_user_play["speed_detail"] = $_user_play["speed_detail"] ?? [];
            $lastCircle = isset($_user_play["circle_detail"]) && $_user_play["circle_detail"] ? $_user_play["circle_detail"][count($_user_play["circle_detail"]) - 1] : 0;    //上次最后的圈数
            $thisCircle = $_circle_detail ? $_circle_detail[count($_circle_detail) - 1] : 0;    //本次最后的圈数
            $thisDistance = round($thisCircle * StaticDataController::$_circle_distance / 100, 2);
            $addCricle = $thisCircle - $lastCircle;
            $addCricle = $addCricle > 0 ? $addCricle : 0;   //本次增加的圈数
            $addTime = $currentTime - ($_user_play['last_time'] ?? $_user_play['start_time']);    //本次增加的运动时间
            $addTime = $addTime > 0 ? $addTime : 0;

            //应前端要求记录参数记录==1
            if ($addCricle > 0){
                $redisAddDistance = round($addCricle * StaticDataController::$_circle_distance / 100, 2);
            }else{
                $redisAddDistance = 0;
            }
            $_datas['cache_data'] = [
                'last_circle' => $lastCircle,//lastCircle上一个圈数
                'this_circle' => $thisCircle,//thisCircle本次圈数
                'add_circle' => $addCricle,//addCricle新增圈数
                'add_distance' => $redisAddDistance,//addDistance新增距离
                'add_time' => $addTime,//addTime新增时间
            ];
            //记录每个用户提交缓存记录==2`
            $_datas['post_time'] = date('Y-m-d H:i:s');
            Redis::select(1);
            Redis::hset('post_between_play_'.$_usr_user['user_id'].'_'.date('Y-m-d'),date('H:i:s'),json_encode($_datas,true));
            Log::info('缓存3333333333333'.json_encode($_datas,true));

            if ($addTime > 0) {
                $_user_play['last_time'] = $currentTime;
            }
//            Log::info('本次增加的圈数' . $_usr_user["user_id"], [$thisCircle - $lastCircle, $addTime]);

            //应前端要求条件过滤条件，修复bug
            if (!$force== 1){
                if ($addTime > 0 && $addCricle > 0) {
                    $addDistance = round($addCricle * StaticDataController::$_circle_distance / 100, 2);
                    $abnormalDistance = $addDistance / $addTime;
                    $_abnormal_index = StaticDataController::$_abnormal_index;//防作弊规则
                    $abnormalExponentMolecular = $_abnormal_index['exponent_molecular']*2 / 60;
                    if ($abnormalDistance > $abnormalExponentMolecular) {
                        $addDistance = 0;//判定为异常距离时，忽略本次上传
                        Log::info('新增距离除时间比例过大:'.json_encode($_datas,true));
                        return array(
                            "code" => 0,
                            "msg" => "error1111111",
                        );
                    }
                }else{
                    Log::info('新增距离或新增时间小于等于0:'.json_encode($_datas,true));
                    return array(
                        "code" => 0,
                        "msg" => "error2222",
                    );
                }
            }


        }

        foreach ($_circle_detail as $key => $value) {
            array_push($_user_play["circle_detail"], $value);
            array_push($_user_play["speed_detail"], $_speed_detail[$key]);
        }

        //修复前端bug 异常数据不作处理
//        if (empty($addCricle)){
//            return array(
//                "code" => 0,
//                "msg" => "error",
//            );
//        }


//        Redis::lpush($_user_play_id,json_encode($_user_play));

//        10天后删除数据d
        Redis::select(14);
        Redis::setex($_user_play_id, 3600 * 24 * 60, json_encode($_user_play));


//        如果存在赛事ID，
        if (isset($_data["sys_match_id"]) && isset($_data["matchs_stage_id"])) {
            $_show_all = isset($_data["show_all"]) ? $_data["show_all"] : 0;

            $_user_group_id = isset($_data["user_group_id"]) ? $_data["user_group_id"] : "";

//            如果检测到运动数据异常，将运动从赛事内移除
            if (isset($_data["is_abnormal"]) && $_data["is_abnormal"] == 1) {
                if (isset($_data["user_group_id"])) {
                    $_redis_key = $_data["sys_match_id"] . ":" . $_data["matchs_stage_id"] . ":" . $_data["user_group_id"];
                } else {
                    $_redis_key = $_data["sys_match_id"] . ":" . $_data["matchs_stage_id"] . ":" . $_usr_user["user_id"];
                }

//                从赛事队列里面删除运动数据
                Redis::lrem($_redis_key, -1, $_user_play_id);
            }

            $_data = MatchController::MatchPlayInfo($_language, $_data["sys_match_id"], $_user_group_id, $_usr_user, $_show_all, "between_play");

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => $_data
            );
        }

        //如果存在摇加油ID
        if ($_sys_shake_id) {
            $_data = ShakeController::ShakePlayInfo($_sys_shake_id, $_usr_user, $addCricle, $addTime, $force, $thisDistance);

            return [
                "code" => 1,
                "msg" => "success",
                "data" => $_data
            ];
        }


        return array(
            "code" => 1,
            "msg" => "success",
        );
    }

    /**
     * @abstract 用户停止运动
     * @param Request $request
     * @return array
     */
    public function postStopPlay(Request $request)
    {
        $_sys_stop_time = time();

        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (!isset($_data["start_time"]) || !isset($_data["user_play_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_start_time = $_data["start_time"];
        $_data["stop_time"] = $_stop_time = isset($_data["stop_time"]) ? $_data["stop_time"] : time();
        $_user_play_id = $_data["user_play_id"];
        $_interval = isset($_data["interval"]) ? $_data["interval"] : "1000";
        $_circle_detail = isset($_data["circle_detail"]) ? $_data["circle_detail"] : array();
        $_speed_detail = isset($_data["speed_detail"]) ? $_data["speed_detail"] : array();

//        如果是PK的运动，
        $_user_pk_list_id = isset($_data["user_pk_list_id"]) ? $_data["user_pk_list_id"] : "";

        //摇加油ID
        $_sys_shake_id = $_data["sys_shake_id"] ?? '';

        $_user_token = $request->header('token');

        if ($_start_time == "" || $_user_play_id == "" || $_interval == "" || count($_speed_detail) != count($_circle_detail)) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $_last_play = isset($_usr_user["last_play"]) ? $_usr_user["last_play"] : array();

        $_date = date("Y:m:d", $_start_time);
//        $_user_play_key = $_user_token.":".$_date.":".$_user_play_id;
        Redis::select(14);
        $_user_play = json_decode(Redis::get($_user_play_id), true);
        $lastCircleDetail = $_user_play["circle_detail"] ?? []; // 上次数据

        $addTime = $_stop_time - ($_user_play['last_time'] ?? $_user_play['start_time']);   //本次更加时间

//        服务端记录到的结束时间
        $_user_play["sys_stop_time"] = $_sys_stop_time;
        $_user_play["stop_time"] = $_stop_time;

        if (empty($_data['source'])){//补充老版本没source的bug
            if (!empty($_data['matchs_stage_id'])){
                $_data['source'] = 4;
            }elseif (!empty($_data['user_pk_list_id'])){
                $_data['source'] = 3;
            }elseif (!empty($_data['sys_shake_id'])){
                $_data['source'] = 2;
            }else{
                $_data['source'] = 1;
            }
        }

        $_user_play['source'] = $_data['source'] ?? 0;

//        Log::info("结束运动-------------------------------");
//        Log::info($_user_play);

//        如果没有找到圈数数据
        if (!isset($_user_play["circle_detail"])) {
            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "new_medal" => array(),
                    "new_achievement" => array()
                )
            );
        }

//        将结束前的运动明细存入数组
        foreach ($_circle_detail as $key => $value) {
            array_push($_user_play["circle_detail"], $value);
            array_push($_user_play["speed_detail"], $_speed_detail[$key]);
        }

//        弥补误差
//       误差圈数 = 最后一个总圈数 - 倒数第二个总圈数，客户端没400ms从蓝牙获取总圈数，开始运动和结束运动时，存在获取总圈数的时间误差，误差时间不超过400ms

        if (count($_user_play["circle_detail"]) > 1) {
            $_sub_circle = $_user_play["circle_detail"][count($_user_play["circle_detail"]) - 1] - $_user_play["circle_detail"][count($_user_play["circle_detail"]) - 2];
        } else {
            $_sub_circle = 0;
        }

//        补齐数据
        if (count($_user_play["circle_detail"]) > 0) {
            array_push($_user_play["circle_detail"], $_user_play["circle_detail"][count($_user_play["circle_detail"]) - 1] + $_sub_circle);
        } else {
            array_push($_user_play["circle_detail"], 0);
        }
        array_push($_user_play["speed_detail"], 0);
        $thisCircleDetail = $_user_play["circle_detail"] ?? []; //本次圈数

//        格式化运动数据
        $_user_play = UserPlayFunction::StopPlayFormatData($_user_play, $_last_play, $_start_time, $_stop_time, $_interval);

//        Log::channel('debug')->info('运动停止3-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);
        if (isset($_data["distance"])) {
            $_user_play["distance"] = $_data["distance"];
        }
        if (isset($_data["circle_count"])) {
            $_user_play["circle_count"] = $_data["circle_count"];
        }
        if (isset($_data["speed_max"])) {
            $_user_play["speed_max"] = $_data["speed_max"];
        }
        if (isset($_data["interval"])) {
            $_user_play["interval"] = $_data["interval"];
        }

        Redis::setex($_user_play_id, 3600 * 24 * 60, json_encode($_user_play));

//        存储用户运动数据
        $_arrOfUserPlayData = array(
            "user_play_id" => $_user_play["user_play_id"],
            "status" => 1,
            "duration" => $_user_play["duration"],
            "speed_max" => $_user_play["speed_max"],
            "circle_count" => $_user_play["circle_count"],
            "endurance_max" => $_user_play["endurance_max"],
            "compare_last" => $_user_play["compare_last"],
            "start_time" => $_user_play["start_time"],
            "stop_time" => $_user_play["stop_time"],
            "distance" => $_user_play["distance"],
            "user_id" => $_user_play["created_uid"],
            "is_abnormal" => $_user_play["is_abnormal"],
            "exponent_molecular" => isset($_user_play["exponent_molecular"]) ? $_user_play["exponent_molecular"] : 0,
            "exponent_denominator" => isset($_user_play["exponent_denominator"]) ? $_user_play["exponent_denominator"] : 0,
            "exponent" => isset($_user_play["exponent"]) ? $_user_play["exponent"] : 0,
            "marathon" => isset($_user_play["marathon"]) ? $_user_play["marathon"] : 0,
            "source" => $_user_play['source'] ?? 0,
        );

        if ($_user_pk_list_id != "") {
            $_arrOfUserPlayData["user_pk_list_id"] = $_user_pk_list_id;
        }

        if (isset($_user_play["user_pk_list_id"])) {
            $_arrOfUserPlayData["user_pk_list_id"] = $_user_play["user_pk_list_id"];
        }

        if (isset($_data["sys_match_id"]) && isset($_data["matchs_stage_id"])) {
            $_arrOfUserPlayData["matchs_stage_id"] = $_data["matchs_stage_id"];
        }

        if ($_sys_shake_id) {
            $_arrOfUserPlayData["sys_shake_id"] = $_sys_shake_id;
        }

//        UserPlay::create($_arrOfUserPlayData);
        self::saveUserPlay($_arrOfUserPlayData);

        $_sno = new Snowflake(StaticDataController::$_workId);
        $_user_play_detail_id = $_sno->nextId();
        $_arrOfUserPlayDetailData = array(
            "user_play_detail_id" => $_sno->nextId(),
            "status" => 1,
            "speed_interval" => $_interval,
            "user_play_id" => $_user_play["user_play_id"],
            "section_duration" => json_encode($_user_play["section_duration"]),
            "speed_detail" => json_encode($_user_play["user_play_detail"]),
        );
        $_arrOfUserPlayDetail = UserPlayDetail::where([
            "user_play_detail_id" => $_user_play_detail_id
        ])->select("status")->get();

        if (count($_arrOfUserPlayDetail) == 0) {
            UserPlayDetail::create($_arrOfUserPlayDetailData);
        } else {
            UserPlayDetail::where([
                "user_play_detail_id" => $_user_play_detail_id
            ])->update($_arrOfUserPlayDetailData);
        }

        $_arrOfNewMedal = array();
        $_arrOfNewAchievement = array();

//        正常数据时，判断是否正常
        if ($_user_play["is_abnormal"] == 0) {
//            用户运动结束后，存储数据，判定是否突破记录，用户相关榜单
            $_arrOfNewAchievement = UsrUserController::userStopPlayHasNewAchievement($_user_play, $_user_token, $_language);

//            运动结束后，判定是否获得新徽章
            $_arrOfNewMedal = UsrUserController::userStopPlayHasNewMedal($_user_play, $_user_token, $_language);
        }

//        如果是PK的运动
        if ($_user_pk_list_id != "") {
//            变更用户PK数据
            UserPkList::where([
                "user_pk_list_id" => $_user_pk_list_id,
                "user_id" => $_usr_user["user_id"],
            ])->update([
                "duration" => $_arrOfUserPlayData["duration"],
                "distance" => $_arrOfUserPlayData["distance"],
                "circle_count" => $_arrOfUserPlayData["circle_count"],
            ]);
        }

        //摇加油
        if ($_sys_shake_id) {
            $lastCircle = $lastCircleDetail ? $lastCircleDetail[count($lastCircleDetail) - 1] : 0;    //上次最后的圈数
            $thisCircle = $thisCircleDetail ? $thisCircleDetail[count($thisCircleDetail) - 1] : 0;    //本次最后的圈数
            $addCricle = $thisCircle - $lastCircle;
            $addCricle = $addCricle > 0 ? $addCricle : 0;   //本次增加的圈数
//            $_user_play['last_time'] = $_sys_stop_time;

//            ShakeController::ShakePlayStop($_sys_shake_id, $_usr_user, $addCricle, $_user_play_id, $_user_play["duration"]);
            ShakeController::ShakePlayStop($_sys_shake_id, $_usr_user, $addCricle, $_user_play_id, $addTime);
        }

        // 赛事下的运动结束后，更新赛事临时排名
        if (isset($_data["sys_match_id"]) && isset($_data["matchs_stage_id"])) {
            $_user_group_id = isset($_data["user_group_id"]) ? $_data["user_group_id"] : "";
            MatchController::MatchsStagePlayStop($_data["sys_match_id"], $_user_group_id, $_data["matchs_stage_id"], $_usr_user, $_user_play_id);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "new_medal" => $_arrOfNewMedal,
                "new_achievement" => $_arrOfNewAchievement,
//                "new_achievements"=>$_arrOfNewAchievement,
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/22 14:06
     * @abstract _用户运动数据异常
     */
    public function postUserPlayAbnormal(Request $request)
    {
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (!isset($_data["is_abnormal"]) || !isset($_data["user_play_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_user_play_id = $_data["user_play_id"];
        $_is_abnormal = isset($_date["is_abnormal"]) ? $_data["is_abnormal"] : 1;

        Redis::select(14);
        $_user_play = json_decode(Redis::get($_user_play_id), true);
        $_user_play["is_abnormal"] = $_is_abnormal;

        Redis::set($_user_play_id, json_encode($_user_play));

//        Log::channel('debug')->info('_用户运动数据异常-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);
        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

    /**
     * @abstract 获取单次运动明细数据
     * @param Request $request
     * @return array
     */
    public function postPlayInfo(Request $request)
    {
        $_data = $request->input();

        $_user_play_id = isset($_data["user_play_id"]) ? $_data["user_play_id"] : "";
        $_need_format = isset($_data["need_format"]) ? $_data["need_format"] : true;

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        /*if ($_user_play_id == "" || $_usr_user == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }*/

//        $_user_play = json_decode(Redis::hget("h_user_play",$_data["user_play_id"]),true);

        $created_time = '';
        !empty($_data['speed_max_time']) ? $created_time = $_data['speed_max_time'] : '';
        !empty($_data['runball_exponent_time']) ? $created_time = $_data['runball_exponent_time'] : '';
        !empty($_data['exponent_molecular_time']) ? $created_time = $_data['exponent_molecular_time'] : '';
        !empty($_data['marathon_time']) ? $created_time = $_data['marathon_time'] : '';

        $_user_play = self::getPlayDetail($_user_play_id, $_need_format, $_language, $created_time);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_play" => $_user_play
            )
        );
    }

    /**
     * @abstract 获取运动时间范围
     * @param Request $request
     * @return array
     */
    public function postPlayDateRange(Request $request)
    {
        $_data = $request->input();

        $_start_date = isset($_data["start_date"]) && $_data["start_date"] != "" ? $_data["start_date"] : date("Y-m-d", time());
        $_type = isset($_data["type"]) ? $_data["type"] : 'day';
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

//        获取时间范围
        $_date_range = UserPlayFunction::getLastDate($_start_date, $_type, $_language);

//        格式化时间范围
        $_this_time = strtotime(date("Y-m-d", time()));
        if ($_this_time >= $_date_range[0]["start_time"] && $_this_time <= $_date_range[0]["stop_time"]) {
            $_format_title = "";
            switch ($_type) {
                case "day":
                    $_format_title = LanguageController::getLanguage($_language, "this_day");
                    break;
                case "week":
                    $_format_title = LanguageController::getLanguage($_language, "this_week");
                    break;
                case "month":
                    $_format_title = LanguageController::getLanguage($_language, "this_month");
                    break;
                case "year":
                    $_format_title = LanguageController::getLanguage($_language, "this_year");
                    break;
            }
            if ($_format_title != "") {
                $_date_range[0]["format_title"] = $_format_title;
            }
        }

//        时间格式化
        foreach ($_date_range as $key => $value) {
            $value["start_date"] = date("Y-m-d", $value["start_time"]);
            $value["stop_date"] = date("Y-m-d", $value["stop_time"]);
            $value["title"] = $value["format_title"];
            unset($value["start_time"]);
            unset($value["stop_time"]);
            unset($value["format_title"]);

            $_date_range[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "date_range" => array_reverse($_date_range)
            )
        );
    }


    /**
     * @abstract 获取我的运动数据
     * @param Request $request
     * @return array
     */
    public function postMyPlayData(Request $request)
    {

        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        Redis::select(14);

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 20;
        $_start_date = isset($_data["start_date"]) ? $_data["start_date"] : date("Y-m-d", time());
        $_stop_date = isset($_data["stop_date"]) ? $_data["stop_date"] : date("Y-m-d", time());
        $_type = isset($_data["type"]) ? $_data["type"] : "day";

//        如果是按月查询
        if ($_type == 'month') {
//        根据开始日期，计算开始日期对应周的起止日期
            $w = strftime('%u', strtotime($_start_date));
            $_new_stop_time = strtotime(date("Y-m-d", strtotime($_start_date)) . " +" . (7 - $w) . " day");
            $_new_start_time = $_new_stop_time - 6 * 86400;

//        根据结束时间，计算结束时间对应的起止日期
            $w = strftime('%u', strtotime($_stop_date));
            $_new_stop_time = strtotime(date("Y-m-d", strtotime($_stop_date)) . " +" . (7 - $w) . " day");

//            格式化起止日期
            $_start_date = date("Y-m-d", $_new_start_time);
            $_stop_date = date("Y-m-d", $_new_stop_time);
        }

        $_sub_day = round((strtotime($_stop_date) - strtotime($_start_date)) / 86400);
        $_sub_day = $_sub_day > 0 ? $_sub_day + 1 : 0;


        $_start_time = strtotime($_start_date . " 00:00:00");
        $_stop_time = strtotime($_stop_date . " 23:59:59");


        $_arrOfUserPlay = UserPlay::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"],
        ])->whereBetween("start_time", [$_start_time, $_stop_time])->select(
            "user_play_id", "status", "duration", "speed_max", "circle_count", "endurance_max", "compare_last", "start_time", "stop_time", "distance", "user_id"
        )->orderBy("start_time", "DESC")->get();

        $_arrOfPlayData = array();
//        遍历，不完整的运动数据剔除
        foreach ($_arrOfUserPlay as $key => $value) {
            $_arrOfPlayDataNode = array(
                "user_id" => $value["user_id"],
                "user_play_id" => $value["user_play_id"],
                "status" => $value["status"],
                "duration" => $value["duration"],
                "speed_max" => $value["speed_max"],
                "circle_count" => $value["circle_count"],
                "endurance_max" => $value["endurance_max"],
                "compare_last" => $value["compare_last"],
                "start_time" => $value["start_time"],
                "stop_time" => $value["stop_time"],
                "distance" => $value["distance"],
            );
            array_push($_arrOfPlayData, $_arrOfPlayDataNode);
        }


        $_play_data = array_values($_arrOfPlayData);
        $_count = 0;
        $_play_data_format = array();

        if ($_type == 'day') {
            $_count = count($_play_data);
            $_data_start_index = ($_page - 1) * $_limit;
            $_data_stop_index = $_data_start_index + $_limit;
//        避免数组超范围
            if ($_data_stop_index >= $_count) {
                $_data_stop_index = $_count - 1;
            }

//        返回数据格式化
            for ($_i = $_data_start_index; $_i <= $_data_stop_index; $_i++) {
                $_play_data_node = $_play_data[$_i];
                $_play_data_node["start_time_format"] = date("m/d H:i", $_play_data_node["start_time"]);
                array_push($_play_data_format, $_play_data_node);
            }
        } else if ($_type == 'week') {
            $_total_type = "day_count";

//            运动数据按天归类
            $_arrOfPlayData = array();
            $_arrOfDayCount = array();
            foreach ($_play_data as $value) {
                $_play_date = date("Y-m-d", $value["start_time"]);
                if (!array_key_exists($_play_date, $_arrOfPlayData)) {
                    $_arrOfPlayData[$_play_date] = array(
                        "speed_max" => 0
                    );
                    $_arrOfDayCount[$_play_date] = array(
                        "circle_count" => 0,
                        "duration" => 0,
                        "endurance_max" => 0,
                        "play_count" => 0,
                        "distance" => 0
                    );
                }
//                去最高转速
                if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                    $_arrOfPlayData[$_play_date] = $value;
                }
                $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                $_arrOfDayCount[$_play_date]["play_count"] += 1;

            }

            $_format_year = date("Y", strtotime($_start_date));
            foreach ($_arrOfPlayData as $key => $value) {
                $_start_time_format = date("m/d", $value["start_time"]);
                if ($_format_year != date("Y", $value["start_time"])) {
                    $_format_year = date("Y", $value["start_time"]);
                    $_start_time_format = date("y/m/d", $value["start_time"]);
                }

                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $value["start_time_format"] = $_start_time_format;
                $_arrOfPlayData[$key] = $value;
            }

            $_play_data_format = array_values($_arrOfPlayData);
            $_count = count($_play_data_format);
        } else if ($_type == 'month') {
            $_total_type = "week_count";

//            运动数据按天归类
            $_arrOfPlayData = UserPlayFunction::getWeekDateRange($_start_date, $_stop_date);
            $_arrOfDayCount = array();


            foreach ($_arrOfPlayData as $key => $value) {
                $_arrOfPlayData[$key]["speed_max"] = 0;
                $_arrOfDayCount[$key] = array(
                    "circle_count" => 0,
                    "duration" => 0,
                    "endurance_max" => 0,
                    "play_count" => 0,
                    "distance" => 0
                );
            }


            foreach ($_play_data as $value) {
                $w = strftime('%u', $value["start_time"]);
                $_new_stop_time = strtotime(date("Y-m-d", $value["start_time"]) . " +" . (7 - $w) . " day");
                $_new_start_time = $_new_stop_time - 6 * 86400;

                $_play_date = date("Y_m_d", $_new_start_time) . "-" . date("Y_m_d", $_new_stop_time);

                $_format_year = date("Y", $value["start_time"]);
                if (array_key_exists($_play_date, $_arrOfPlayData)) {
//                取最高转速
                    if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                        $_start_time_format = date("m/d", $_new_start_time) . "-" . date("m/d", $_new_stop_time);
                        if ($_format_year != date("Y", $_new_start_time)) {
                            $_format_year = date("Y", $value["start_time"]);
                            $_start_time_format = date("y/m/d", $_new_start_time) . "-" . date("m/d", $_new_stop_time);
                        }
                        $value["start_time_format"] = $_start_time_format;
                        $_arrOfPlayData[$_play_date] = $value;
                    }
                    $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                    $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                    $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                    $_arrOfDayCount[$_play_date]["play_count"] += 1;
                }

            }


            foreach ($_arrOfPlayData as $key => $value) {

                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $_arrOfPlayData[$key] = $value;
            }

            foreach ($_arrOfPlayData as $key => $value) {
                if (!isset($value["status"])) {
                    unset($_arrOfPlayData[$key]);
                }
            }

            $_play_data_format = array_reverse(array_values($_arrOfPlayData));

            $_count = count($_play_data_format);


        } else if ($_type == 'year') {
            $_total_type = "month_count";

//            运动数据按月归类
            $_arrOfPlayData = array();
            $_arrOfDayCount = array();
            foreach ($_play_data as $value) {
                $_play_date = date("Y-m", $value["start_time"]);
                if (!array_key_exists($_play_date, $_arrOfPlayData)) {
                    $_arrOfPlayData[$_play_date] = array(
                        "speed_max" => 0
                    );
                    $_arrOfDayCount[$_play_date] = array(
                        "circle_count" => 0,
                        "duration" => 0,
                        "endurance_max" => 0,
                        "play_count" => 0,
                        "distance" => 0
                    );
                }
//                去最高转速
                if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                    $_arrOfPlayData[$_play_date] = $value;
                }
                $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                $_arrOfDayCount[$_play_date]["play_count"] += 1;

            }

            $_format_year = date("Y", strtotime($_start_date));
            foreach ($_arrOfPlayData as $key => $value) {
                $_start_time_format = date("m月", $value["start_time"]);
                if ($_format_year != date("Y", $value["start_time"])) {
                    $_format_year = date("Y", $value["start_time"]);
                    $_start_time_format = date("y/m月", $value["start_time"]);
                }
                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $value["start_time_format"] = $_start_time_format;
                $_arrOfPlayData[$key] = $value;
            }

            $_play_data_format = array_values($_arrOfPlayData);
            $_count = count($_play_data_format);
        }


        for ($_i = 0; $_i < count($_play_data_format) - 1; $_i++) {
            $_compare_last = 0;

            if ($_play_data_format[$_i]["circle_count"] < $_play_data_format[$_i + 1]["circle_count"]) {
                $_compare_last = -1;
            }
            if ($_play_data_format[$_i]["circle_count"] > $_play_data_format[$_i + 1]["circle_count"]) {
                $_compare_last = 1;
            }
            $_play_data_format[$_i]["compare_last"] = $_compare_last;
        }


//        数据格式化
        foreach ($_play_data_format as $key => $value) {
            $value["circle_count_format"] = number_format($value["circle_count"] / 1000, 3);
            $value["circle_count_unit"] = LanguageController::getLanguage($_language, "circle_count_unit");

//            $value["distance"] = $value["circle_count"]*StaticDataController::$_circle_distance/100;
//            $value["distance_format"] = number_format($value["circle_count"]*StaticDataController::$_circle_distance/100/1000,3);

            if (!isset($value["distance"])) {
                $value["distance"] = $value["circle_count"] * StaticDataController::$_circle_distance / 100;
            }

            $value["distance_format"] = number_format($value["distance"] / 1000, 3);
            $value["distance_unit"] = "km";


            $value["speed_max_format"] = number_format($value["speed_max"]);
            $value["speed_max_unit"] = 'rpm';

            $value["duration_format"] = TimeFormatController::formatSecondToTime($value["duration"] ?? 0);


//            汇总数据
            if (isset($value["play_total"])) {
                $value["play_total"]["circle_count_format"] = number_format($value["play_total"]["circle_count"] / 1000, 3);
                $value["play_total"]["circle_count_unit"] = LanguageController::getLanguage($_language, "circle_count_unit");
                $value["play_total"]["distance_unit"] = 'km';
            }

            $_play_data_format[$key] = $value;
        }
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_count,
                "play_data" => $_play_data_format,
            )
        );

    }

    /**
     * 获取我的运动数据 === v2改版
     * @param Request $request
     * @return array|JsonResponse
     */
    public function postMyPlayDataV2(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        Redis::select(14);

        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 20;
        $_start_date = $_data["start_date"] ?? date("Y-m-d", time());
        $_stop_date = $_data["stop_date"] ?? date("Y-m-d", time());
        $_type = $_data["type"] ?? "day";
        $_source = $_data['source'] ?? 0;

        //兼容国际版
        $_start_date = empty($_data["start_date_unix"]) ? $_start_date : date("Y-m-d", $_data["start_date_unix"]);
        $_stop_date = empty($_data["stop_date_unix"]) ? $_stop_date : date("Y-m-d", $_data["stop_date_unix"]);
//dd($_start_date,$_stop_date);

//        如果是按月查询
        if ($_type == 'month') {
//        根据开始日期，计算开始日期对应周的起止日期
            $w = strftime('%u', strtotime($_start_date));
            $_new_stop_time = strtotime(date("Y-m-d", strtotime($_start_date)) . " +" . (7 - $w) . " day");
            $_new_start_time = $_new_stop_time - 6 * 86400;

//        根据结束时间，计算结束时间对应的起止日期
            $w = strftime('%u', strtotime($_stop_date));
            $_new_stop_time = strtotime(date("Y-m-d", strtotime($_stop_date)) . " +" . (7 - $w) . " day");

//            格式化起止日期
            $_start_date = date("Y-m-d", $_new_start_time);
            $_stop_date = date("Y-m-d", $_new_stop_time);
        }

        $_sub_day = round((strtotime($_stop_date) - strtotime($_start_date)) / 86400);
        $_sub_day = $_sub_day > 0 ? $_sub_day + 1 : 0;


        $_start_time = strtotime($_start_date . " 00:00:00");
        $_stop_time = strtotime($_stop_date . " 23:59:59");


        $_arrOfUserPlay = UserPlay::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"],
        ])->where(function ($query) use ($_source) {
            if ($_source > 0){
                $query->where('source',$_source);
            }else{
                $query->whereIn('source',[0,1,2,3,4,5]);
            }
        })->whereBetween("start_time", [$_start_time, $_stop_time])
            ->select("user_play_id", "status", "duration", "speed_max", "circle_count", "endurance_max", "compare_last", "start_time", "stop_time", "distance", "user_id","source")
            ->orderBy("start_time", "DESC")
            ->get();

        $distanceSum = count($_arrOfUserPlay) > 0 ? array_sum($_arrOfUserPlay->pluck('distance')->toArray()) : 0;

        $_arrOfPlayData = array();
//        遍历，不完整的运动数据剔除
        foreach ($_arrOfUserPlay as $key => $value) {
            $_arrOfPlayDataNode = array(
                "user_id" => $value["user_id"],
                "user_play_id" => $value["user_play_id"],
                "status" => $value["status"],
                "duration" => $value["duration"],
                "speed_max" => $value["speed_max"],
                "circle_count" => $value["circle_count"],
                "endurance_max" => $value["endurance_max"],
                "compare_last" => $value["compare_last"],
                "start_time" => $value["start_time"],
                "stop_time" => $value["stop_time"],
                "distance" => $value["distance"],
                "source" => $value["source"],
            );
            array_push($_arrOfPlayData, $_arrOfPlayDataNode);
        }


        $_play_data = array_values($_arrOfPlayData);
        $_count = 0;
        $_play_data_format = array();

        if ($_type == 'day') {
            $_count = count($_play_data);
            $_data_start_index = ($_page - 1) * $_limit;
            $_data_stop_index = $_data_start_index + $_limit;
//        避免数组超范围
            if ($_data_stop_index >= $_count) {
                $_data_stop_index = $_count - 1;
            }

//        返回数据格式化
            for ($_i = $_data_start_index; $_i <= $_data_stop_index; $_i++) {
                $_play_data_node = $_play_data[$_i];
                $_play_data_node["start_time_format"] = date("m/d H:i", $_play_data_node["start_time"]);
                $_play_data_node["start_time_format_unix"] = $_play_data_node["start_time"];
                array_push($_play_data_format, $_play_data_node);
            }
        } else if ($_type == 'week') {
            $_total_type = "day_count";

//            运动数据按天归类
            $_arrOfPlayData = array();
            $_arrOfDayCount = array();
            foreach ($_play_data as $value) {
                $_play_date = date("Y-m-d", $value["start_time"]);
                if (!array_key_exists($_play_date, $_arrOfPlayData)) {
                    $_arrOfPlayData[$_play_date] = array(
                        "speed_max" => 0
                    );
                    $_arrOfDayCount[$_play_date] = array(
                        "circle_count" => 0,
                        "duration" => 0,
                        "endurance_max" => 0,
                        "play_count" => 0,
                        "distance" => 0
                    );
                }
//                去最高转速
                if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                    $_arrOfPlayData[$_play_date] = $value;
                }
                $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                $_arrOfDayCount[$_play_date]["play_count"] += 1;

            }

            $_format_year = date("Y", strtotime($_start_date));
            foreach ($_arrOfPlayData as $key => $value) {
                $_start_time_format = date("m/d", $value["start_time"]);
                if ($_format_year != date("Y", $value["start_time"])) {
                    $_format_year = date("Y", $value["start_time"]);
                    $_start_time_format = date("y/m/d", $value["start_time"]);
                }

                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $value["start_time_format"] = $_start_time_format;
                $_arrOfPlayData[$key] = $value;
            }

            $_play_data_format = array_values($_arrOfPlayData);
            $_count = count($_play_data_format);
        } else if ($_type == 'month') {
            $_total_type = "week_count";

//            运动数据按天归类
            $_arrOfPlayData = UserPlayFunction::getWeekDateRange($_start_date, $_stop_date);
            $_arrOfDayCount = array();


            foreach ($_arrOfPlayData as $key => $value) {
                $_arrOfPlayData[$key]["speed_max"] = 0;
                $_arrOfDayCount[$key] = array(
                    "circle_count" => 0,
                    "duration" => 0,
                    "endurance_max" => 0,
                    "play_count" => 0,
                    "distance" => 0
                );
            }


            foreach ($_play_data as $value) {
                $w = strftime('%u', $value["start_time"]);
                $_new_stop_time = strtotime(date("Y-m-d", $value["start_time"]) . " +" . (7 - $w) . " day");
                $_new_start_time = $_new_stop_time - 6 * 86400;

                $_play_date = date("Y_m_d", $_new_start_time) . "-" . date("Y_m_d", $_new_stop_time);

                $_format_year = date("Y", $value["start_time"]);
                if (array_key_exists($_play_date, $_arrOfPlayData)) {
//                取最高转速
                    if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                        $_start_time_format = date("m/d", $_new_start_time) . "-" . date("m/d", $_new_stop_time);
                        if ($_format_year != date("Y", $_new_start_time)) {
                            $_format_year = date("Y", $value["start_time"]);
                            $_start_time_format = date("y/m/d", $_new_start_time) . "-" . date("m/d", $_new_stop_time);
                        }
                        $value["start_time_format"] = $_start_time_format;
                        $_arrOfPlayData[$_play_date] = $value;
                    }
                    $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                    $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                    $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                    $_arrOfDayCount[$_play_date]["play_count"] += 1;
                }

            }


            foreach ($_arrOfPlayData as $key => $value) {

                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $_arrOfPlayData[$key] = $value;
            }

            foreach ($_arrOfPlayData as $key => $value) {
                if (!isset($value["status"])) {
                    unset($_arrOfPlayData[$key]);
                }
            }

            $_play_data_format = array_reverse(array_values($_arrOfPlayData));

            $_count = count($_play_data_format);


        } else if ($_type == 'year') {
            $_total_type = "month_count";

//            运动数据按月归类
            $_arrOfPlayData = array();
            $_arrOfDayCount = array();
            foreach ($_play_data as $value) {
                $_play_date = date("Y-m", $value["start_time"]);
                if (!array_key_exists($_play_date, $_arrOfPlayData)) {
                    $_arrOfPlayData[$_play_date] = array(
                        "speed_max" => 0
                    );
                    $_arrOfDayCount[$_play_date] = array(
                        "circle_count" => 0,
                        "duration" => 0,
                        "endurance_max" => 0,
                        "play_count" => 0,
                        "distance" => 0
                    );
                }
//                去最高转速
                if ($_arrOfPlayData[$_play_date]["speed_max"] < $value["speed_max"]) {
                    $_arrOfPlayData[$_play_date] = $value;
                }
                $_arrOfDayCount[$_play_date]["circle_count"] += $value["circle_count"];
                $_arrOfDayCount[$_play_date]["duration"] += $value["duration"];
                $_arrOfDayCount[$_play_date]["endurance_max"] += $value["endurance_max"];
                $_arrOfDayCount[$_play_date]["play_count"] += 1;

            }

            $_format_year = date("Y", strtotime($_start_date));
            foreach ($_arrOfPlayData as $key => $value) {
                $_start_time_format = date("m月", $value["start_time"]);
                if ($_format_year != date("Y", $value["start_time"])) {
                    $_format_year = date("Y", $value["start_time"]);
                    $_start_time_format = date("y/m月", $value["start_time"]);
                }
                $_arrOfDayCount[$key]["distance"] = $_arrOfDayCount[$key]["circle_count"] * StaticDataController::$_circle_distance / 100;
                $_arrOfDayCount[$key]["distance_format"] = number_format($_arrOfDayCount[$key]["distance"] / 1000, 3);
                $value["play_total"] = $_arrOfDayCount[$key];
                $value["total_type"] = $_total_type;
                $value["start_time_format"] = $_start_time_format;
                $_arrOfPlayData[$key] = $value;
            }

            $_play_data_format = array_values($_arrOfPlayData);
            $_count = count($_play_data_format);
        }


        for ($_i = 0; $_i < count($_play_data_format) - 1; $_i++) {
            $_compare_last = 0;

            if ($_play_data_format[$_i]["circle_count"] < $_play_data_format[$_i + 1]["circle_count"]) {
                $_compare_last = -1;
            }
            if ($_play_data_format[$_i]["circle_count"] > $_play_data_format[$_i + 1]["circle_count"]) {
                $_compare_last = 1;
            }
            $_play_data_format[$_i]["compare_last"] = $_compare_last;
        }


//        数据格式化
        foreach ($_play_data_format as $key => $value) {
            $value["circle_count_format"] = number_format($value["circle_count"] / 1000, 3);
            $value["circle_count_unit"] = LanguageController::getLanguage($_language, "circle_count_unit");

//            $value["distance"] = $value["circle_count"]*StaticDataController::$_circle_distance/100;
//            $value["distance_format"] = number_format($value["circle_count"]*StaticDataController::$_circle_distance/100/1000,3);

            if (!isset($value["distance"])) {
                $value["distance"] = $value["circle_count"] * StaticDataController::$_circle_distance / 100;
            }

            $value["distance_format"] = number_format($value["distance"] / 1000, 3);
            $value["distance_unit"] = "km";


            $value["speed_max_format"] = number_format($value["speed_max"]);
            $value["speed_max_unit"] = 'rpm';

            $value["duration_format"] = TimeFormatController::formatSecondToTime($value["duration"] ?? 0);


//            汇总数据
            if (isset($value["play_total"])) {
                $value["play_total"]["circle_count_format"] = number_format($value["play_total"]["circle_count"] / 1000, 3);
                $value["play_total"]["circle_count_unit"] = LanguageController::getLanguage($_language, "circle_count_unit");
                $value["play_total"]["distance_unit"] = 'km';
            }

            $_play_data_format[$key] = $value;
        }

        $list = [
            'odometer_sum' => [
                'distance_sum' => round($distanceSum/1000,3),
                'unit' => 'km',
            ],
            'list' => $_play_data_format,
        ];

        return $this->success($list);
    }

    /**
     * 我的记录-摇跑模式统计
     * @param Request $request
     * @return array|JsonResponse
     */
    public function postMyPlayStatistics(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        Redis::select(14);

        //数据统计
        //统计打榜、随手摇
        $userPlayCount = UserPlay::where('user_id',$_usr_user['user_id'])->first(DB::raw("COUNT(CASE WHEN source=1 THEN 1 END) AS 'hit_ranking_count',COUNT(CASE WHEN source=5 THEN 1 END) AS 'casually_count'"));
        //摇跑PK
        $userPkListCount = UserPkList::where(['user_id' => $_usr_user['user_id'],'status' => 1])->count();
        //统计摇跑赛事
        $matchsUserGradeCount = MatchsUserGrade::where(['user_id' => $_usr_user['user_id'],'is_join' => 1,])
            ->join('matchs_stage', function ($join) {
                $join->on('matchs_stage.matchs_stage_id', '=', 'matchs_user_grade.matchs_stage_id');
            })
            ->select('matchs_stage.sys_sys_match_id','matchs_user_grade.matchs_user_grade_id','matchs_stage.matchs_stage_id')
            ->groupBy('matchs_stage.sys_sys_match_id')
            ->count();
        //统计摇加油
        $shakeGroupUserCount = ShakeGroupUser::where('user_id',$_usr_user['user_id'])->count();

        $list = [
            'hit_ranking_count' => [//打榜
                'type' => 1,
                'count' => $userPlayCount['hit_ranking_count'],
                'unit' => trans('messages.unit_field'),
            ],
            'pk_count' => [//摇跑PK
                'type' => 2,
                'count' => $userPkListCount,
                'unit' => trans('messages.unit_frequency'),
            ],
            'shake_count' => [//摇加油
                'type' => 3,
                'count' => $shakeGroupUserCount,
                'unit' => trans('messages.unit_field'),
            ],
            'matchs_count' => [//摇跑赛事
                'type' => 4,
                'count' => $matchsUserGradeCount,
                'unit' => trans('messages.unit_field'),
            ],
            'casually_count' => [//随手摇
                'type' => 5,
                'count' => $userPlayCount['casually_count'],
                'unit' => trans('messages.unit_frequency'),
            ]
        ];

        return $this->success($list);
    }

    /**
     * @abstract 用户三分钟运动距离
     * @param Request $request
     * @return array
     */
    public function postPlayThrmin(Request $request)
    {

//        接口废弃，直接返回
        return array(
            "code" => 1,
            "msg" => "success"
        );


        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
            );
        }

        $_thrmin = $_data["circle"] * StaticDataController::$_circle_distance / 100;

        if (!isset($_usr_user["achievement"]["thrmin"])) {
            $_usr_user["achievement"]["thrmin"] = 0;
        }


//        Log::info("摇跑指数：".$_thrmin);
        if ($_usr_user["achievement"]["thrmin"] < $_thrmin) {
            $_usr_user["achievement"]["thrmin"] = $_thrmin;

//            个人三分钟运动距离，用于 个人三分钟榜单
            Redis::zadd("rank_list_self_thrmin", $_thrmin, $_usr_user["user_id"]);

            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["thrmin" => $_thrmin]);
        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );

    }


    /**
     * @abstract 用户运动半马距离耗时
     * @param Request $request
     * @return array
     */
    public function postPlayHalfMarathon(Request $request)
    {

//        接口废弃，直接返回
        return array(
            "code" => 1,
            "msg" => "success"
        );


        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
            );
        }

        if (!isset($_usr_user["achievement"]["half_marathon"])) {
            $_usr_user["achievement"]["half_marathon"] = 0;
        }

//        Log::info("摇跑指数：".$_data["duration"]);
        if ($_usr_user["achievement"]["half_marathon"] == 0 || $_usr_user["achievement"]["half_marathon"] > $_data["duration"]) {
            $_usr_user["achievement"]["half_marathon"] = $_data["duration"];

            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["half_marathon" => $_data["duration"]]);
        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 摇跑指数，分子
     * @param Request $request
     * @return array
     */
    public function postExponentMolecular(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_thrmin = $_data["circle"] * StaticDataController::$_circle_distance / 100;
        $_thrmin = $_thrmin < (StaticDataController::$_abnormal_index['exponent_molecular'] ?? 0) ? $_thrmin : 0;

//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_data["user_play_id"])) {
            Redis::select(14);
            $_user_play = json_decode(Redis::get($_data["user_play_id"]), true);

//            Log::channel('debug')->info('摇跑指数，分子1-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);

//            如果是异常数据，直接返回
            if ($_user_play["is_abnormal"] == 1) {
                return array(
                    "code" => 1,
                    "msg" => "success"
                );
            }

            $_user_play["user_id"] = $_usr_user["user_id"];

//           摇跑分母
            $_user_play["exponent_molecular"] = $_thrmin;
//            更新用户运动redis
            Redis::set($_data["user_play_id"], json_encode($_user_play));

            //临时储存运动数据
            self::saveUserPlay($_user_play);

            Redis::select(1);
        }

        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
                "exponent_molecular" => 0,
                "exponent_denominator" => 0,
            );
        }


        $_arrOfUserAchievement = UserAchievement::where(["user_id" => $_usr_user["user_id"]])->select(
            "exponent_molecular", "exponent_denominator", "runball_exponent"
        )->get();

        if (count($_arrOfUserAchievement) == 1 && ($_arrOfUserAchievement[0]["exponent_molecular"] == 0 || $_arrOfUserAchievement[0]["exponent_molecular"] < $_thrmin)) {
            $_runball_exponent = 0;

            if ($_arrOfUserAchievement[0]["exponent_denominator"] > 0) {
                $_runball_exponent = round($_thrmin / ($_arrOfUserAchievement[0]["exponent_denominator"] / 60), 2);
            }
//            摇跑指数
            Redis::zadd("rank_list_exponent", $_runball_exponent, $_usr_user["user_id"]);


//            个人1分钟运动距离，用于 官网个人1分钟榜单
            Redis::zadd("rank_list_self_onemin", $_thrmin, $_usr_user["user_id"]);

            $_usr_user["achievement"]["exponent_molecular"] = $_thrmin;

            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update([
                "exponent_molecular" => $_thrmin,
                "exponent_molecular_time" => time(),

//                "runball_exponent"=>$_runball_exponent,
//                "runball_exponent_time"=>time(),
            ]);
        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );

    }


    /**
     * @abstract 摇跑指数分母
     * @param Request $request
     * @return array
     */
    public function postExponentDenominator(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);


//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_data["user_play_id"])) {
            Redis::select(14);
            $_user_play = json_decode(Redis::get($_data["user_play_id"]), true);

//            Log::channel('debug')->info('摇跑指数分母1-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);

//            如果是异常数据，直接返回
            if ($_user_play["is_abnormal"] == 1) {
                return array(
                    "code" => 1,
                    "msg" => "success"
                );
            }

            $_user_play["user_id"] = $_usr_user["user_id"];
//           摇跑分母
            $_user_play["exponent_denominator"] = $_data["duration"] > StaticDataController::$_abnormal_index['exponent_denominator'] ? $_data["duration"] : ($_user_play["exponent_denominator"] ?? 0);
//            更新用户运动redis
            Redis::set($_data["user_play_id"], json_encode($_user_play));

            //临时储存运动数据
            self::saveUserPlay($_user_play);
//            Log::channel('debug')->info('摇跑指数分母2-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);

            Redis::select(1);
        }

        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
                "exponent_molecular" => 0,
                "exponent_denominator" => 0,
            );
        }

        $_arrOfUserAchievement = UserAchievement::where(["user_id" => $_usr_user["user_id"]])->select(
            "exponent_molecular", "exponent_denominator", "runball_exponent"
        )->get();

        if (count($_arrOfUserAchievement) == 1 && ($_arrOfUserAchievement[0]["exponent_denominator"] == 0 || $_arrOfUserAchievement[0]["exponent_denominator"] > $_data["duration"])) {
//            $_runball_exponent = round($_arrOfUserAchievement[0]["exponent_molecular"] / ($_data["duration"] / 60), 2);


            $_usr_user["achievement"]["exponent_denominator"] = $_data["duration"];

            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update([
                "exponent_denominator" => $_data["duration"],
//                "runball_exponent"=>$_runball_exponent,
//                "runball_exponent_time"=>time(),
            ]);

        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 用户运动全马距离耗时
     * @param Request $request
     * @return array
     */
    public function postPlayMarathon(Request $request)
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_data["duration"] = $_data["duration"] > StaticDataController::$_abnormal_index['marathon'] ? $_data["duration"] : 0;

//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_data["user_play_id"])) {
            Redis::select(14);
            $_user_play = json_decode(Redis::get($_data["user_play_id"]), true);
//            Log::channel('debug')->info('用户运动全马距离耗时1-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);
//            如果是异常数据，直接返回
            if ($_user_play["is_abnormal"] == 1) {
                return array(
                    "code" => 1,
                    "msg" => "success"
                );
            }
            $_user_play["marathon"] = $_data["duration"];

            $_user_play["user_id"] = $_usr_user["user_id"];
//            数据存储
            Redis::set($_data["user_play_id"], json_encode($_user_play));

            //临时储存运动数据
            self::saveUserPlay($_user_play);

//            Log::channel('debug')->info('用户运动全马距离耗时2-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);
            Redis::select(1);
        }


        if (!isset($_usr_user["achievement"])) {
            $_usr_user["achievement"] = array(
                "duration" => 0,
                "speed_max" => 0,
                "circle_count" => 0,
                "endurance_max" => 0,
                "play_count" => 0,
                "thrmin" => 0,
                "marathon" => 0,
                "half_marathon" => 0,
                "distance_max" => 0,
            );
        }

        if (!isset($_usr_user["achievement"]["marathon"])) {
            $_usr_user["achievement"]["marathon"] = 0;
        }

//        Log::info("摇跑指数：".$_data["duration"]);
        if ($_usr_user["achievement"]["marathon"] == 0 || $_usr_user["achievement"]["marathon"] > $_data["duration"]) {
            $_usr_user["achievement"]["marathon"] = $_data["duration"];
            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["marathon" => $_data["duration"], "marathon_time" => time()]);
        }

        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/1 11:06
     * @abstract _获取用户详情数据
     */
    public static function getPlayDetail($_user_play_id, $_need_format, $_language, $created_time = '')
    {
        $map = [
            "user_play.status" => 1,
        ];

        if (!empty($_user_play_id)){
            $map['user_play.user_play_id'] = $_user_play_id;
        }
        if (!empty($created_time)){
            $map['user_play.created_time'] = $created_time;
        }

        //speed_max_time runball_exponent_time exponent_molecular_time marathon_time

        $_arrOfUserPlay = UserPlay::where($map)->join("user_play_detail", function ($join) {
            $join->on("user_play.user_play_id", "=", "user_play_detail.user_play_id");
        })->select(
            "user_play.user_play_id", "user_play.duration", "user_play.speed_max", "user_play.circle_count", "user_play.endurance_max"
            , "user_play.compare_last", "user_play.start_time", "user_play.stop_time", "user_play.status", "user_play_detail.section_duration"
            , "user_play_detail.speed_detail", "user_play.distance", "user_play.exponent_molecular", "user_play.exponent_denominator", "user_play.exponent", "user_play.source", "user_play.is_abnormal", "user_play.marathon"
        )->get();


//        Log::channel('debug')->info('_获取用户详情数据1-' . ($_usr_user["user_id"] ?? 0), [$_arrOfUserPlay]);
        if (count($_arrOfUserPlay) == 1) {
            $_user_play = $_arrOfUserPlay[0];
            $_user_play["section_duration"] = json_decode($_user_play["section_duration"], true);
            $_user_play["user_play_detail"] = json_decode($_user_play["speed_detail"], true);
            $_user_play["exponent_molecular"] = round($_user_play["exponent_molecular"],2);
            $_user_play["exponent_denominator"] = round($_user_play["exponent_denominator"],2);
            $_user_play["exponent"] = round($_user_play["exponent"],2);
            $_user_play["marathon"] = RankController::timeFormat($_user_play["marathon"]);

            unset($_user_play["speed_detail"]);
        } else {
            return array();
        }

        if ($_need_format) {
            $_start_time = date("Y/m/d H:i:s", $_user_play["start_time"]);
            $_stop_time = date("Y/m/d H:i:s", $_user_play["stop_time"]);

            $_user_play["start_time_format"] = $_start_time;
            $_user_play["stop_date_format"] = $_stop_time;

            $_user_play["start_time_format_unix"] = $_user_play["start_time"];
            $_user_play["stop_date_format_unix"] = $_user_play["stop_time"];

            $_user_play["circle_count_format"] = (string)round($_user_play["circle_count"] / 1000, 3);
            $_user_play["circle_count_unit"] = LanguageController::getLanguage($_language, "circle_count_unit");

            if (!isset($_user_play["distance"])) {
                $_user_play["distance"] = $_user_play["circle_count"] * StaticDataController::$_circle_distance / 100;
            }

            $_user_play["distance_format"] = number_format($_user_play["distance"] / 1000, 3);
            $_user_play["distance_unit"] = "km";

            $_section_duration = array_values($_user_play["section_duration"]);
            unset($_user_play["section_duration"]);
            $_user_play["section_duration"] = $_section_duration;

            $_user_play["duration_format"] = TimeFormatController::formatSecondToTime($_user_play["duration"]);
        }


//        Log::channel('debug')->info('_获取用户详情数据2-' . ($_usr_user["user_id"] ?? 0), [$_user_play]);

        return $_user_play;
    }

    public function getPlayDetailV2()
    {

    }


        /**
     * 储存运动数据
     *
     * @param $_user_play
     * @param int $status
     */
    public static function saveUserPlay($_user_play, $status = 1)
    {
//        $_user_play['status'] = $status;
////        $_user_play['user_id'] = $_user_play["user_id"] ?? '';
////        $_user_play['user_id'] = $_user_play["user_id"] ?? '';
//
//        $_user_play_id = $_user_play['user_play_id'] ?? '';
//        if ($_user_play_id) {
//            unset($_user_play['str_user_play_id']);
//            $fillAble = [
//                'user_play_id',
//                'matchs_stage_id',
//                'user_id',
//                'user_pk_list_id',
//                'sys_shake_id',
//                'matchs_user_id',
//                'created_time',
//                'updated_time',
//                'created_uid',
//                'updated_uid',
//                'status',
//                'weight',
//                'calories',
//                'duration',
//                'speed_max',
//                'circle_count',
//                'endurance_max',
//                'compare_last',
//                'start_time',
//                'stop_time',
//                'distance',
//                'is_abnormal',
//                'exponent_molecular',
//                'exponent_denominator',
//                'exponent',
//                'marathon'
//            ];
//
//            if (isset($_user_play['speed_max']) && is_array($_user_play['speed_max'])) {
//                $_user_play['speed_max'] = json_encode($_user_play['speed_max']);
//            }
//
//            if (UserPlay::where('user_play_id', $_user_play_id)->count()) {
//                UserPlay::where('user_play_id', $_user_play_id)->update(array_only($_user_play, $fillAble));
//            } else {
//                UserPlay::create(array_only($_user_play, $fillAble));
//            }
//        }






        $_user_play_id = $_user_play['user_play_id'] ?? '';
        if ($_user_play_id) {
            $_userPlayData = array(
                "user_play_id"=>$_user_play_id,
                "status"=>$status,
            );

            if (isset($_user_play["matchs_stage_id"])){$_userPlayData["matchs_stage_id"] = $_user_play["matchs_stage_id"];}
            if (isset($_user_play["user_id"])){$_userPlayData["user_id"] = $_user_play["user_id"];}
            if (isset($_user_play["user_pk_list_id"])){$_userPlayData["user_pk_list_id"] = $_user_play["user_pk_list_id"];}
            if (isset($_user_play["sys_shake_id"])){$_userPlayData["sys_shake_id"] = $_user_play["sys_shake_id"];}
            if (isset($_user_play["matchs_user_id"])){$_userPlayData["matchs_user_id"] = $_user_play["matchs_user_id"]; }
            if (isset($_user_play["weight"])){$_userPlayData["weight"] = $_user_play["weight"];}
            if (isset($_user_play["calories"])){$_userPlayData["calories"] = $_user_play["calories"];}
            if (isset($_user_play["duration"])){$_userPlayData["duration"] = $_user_play["duration"];}
            if (isset($_user_play["speed_max"])){$_userPlayData["speed_max"] = $_user_play["speed_max"];}
            if (isset($_user_play["circle_count"])){$_userPlayData["circle_count"] = $_user_play["circle_count"];}
            if (isset($_user_play["endurance_max"])){$_userPlayData["endurance_max"] = $_user_play["endurance_max"];}
            if (isset($_user_play["endurance_max"])){$_userPlayData["endurance_max"] = $_user_play["endurance_max"];}
            if (isset($_user_play["compare_last"])){$_userPlayData["compare_last"] = $_user_play["compare_last"];}
            if (isset($_user_play["start_time"])){$_userPlayData["start_time"] = $_user_play["start_time"];}
            if (isset($_user_play["stop_time"])){$_userPlayData["stop_time"] = $_user_play["stop_time"];}
            if (isset($_user_play["distance"])){$_userPlayData["distance"] = $_user_play["distance"];}
            if (isset($_user_play["is_abnormal"])){$_userPlayData["is_abnormal"] = $_user_play["is_abnormal"];}
            if (isset($_user_play["exponent_molecular"])){$_userPlayData["exponent_molecular"] = $_user_play["exponent_molecular"];}
            if (isset($_user_play["exponent_denominator"])){$_userPlayData["exponent_denominator"] = $_user_play["exponent_denominator"];}
            if (isset($_user_play["exponent_speed_max"])){$_userPlayData["exponent_speed_max"] = $_user_play["exponent_speed_max"];}
            if (isset($_user_play["exponent"])){$_userPlayData["exponent"] = $_user_play["exponent"];}
            if (isset($_user_play["marathon"])){$_userPlayData["marathon"] = $_user_play["marathon"];}
            if (isset($_user_play["source"])){$_userPlayData["source"] = $_user_play["source"];}

            if (UserPlay::where('user_play_id', $_user_play_id)->count()) {
                UserPlay::where('user_play_id', $_user_play_id)->update($_userPlayData);
            } else {
                UserPlay::create($_userPlayData);
            }
        }
    }

    /**
     * 统计用户今天的总运动距离
     * @param Request $request
     * @return JsonResponse
     */
    public function getDistanceSum(Request $request): JsonResponse
    {
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');
        $_data = $request->all();

        if ($_user_token == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        if (empty($_data['start_time']) && empty($_data['stop_time'])){
            $sql = "FROM_UNIXTIME(created_time, '%Y-%m-%d')='".date('Y-m-d')."' AND created_time>0";
        }else{
            $sql = "created_time>=".$_data['start_time']." AND created_time<=".$_data['stop_time']." AND created_time>0";
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $userPlaySumDay = UserPlay::selectRaw("TRUNCATE(SUM(distance)/1000,3) AS distance")
            ->where('user_id',$_usr_user['user_id'])
            ->whereRaw($sql)
            ->first();

        return $this->success(['sum_distance'=>$userPlaySumDay->distance,'unit'=>'km']);
    }

    /**
     * 统计用户指定月份的每天总运动距离
     * @param Request $request
     * @return JsonResponse
     */
    public function getMonthDistanceSum(Request $request): JsonResponse
    {
        $data = $request->all();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == "" || empty($data['month_time'])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        //修复iOS传参bug
        $data['month_time'] = explode('-',$data['month_time']);
        if (strlen($data['month_time'][1]) <= 1){
            $data['month_time'][1] = '0'.$data['month_time'][1];
        }
        $data['month_time'] = implode('-',$data['month_time']);

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $userPlaySumMonth = UserPlay::selectRaw("TRUNCATE(SUM(distance)/1000,3) AS distance,FROM_UNIXTIME(created_time, '%Y-%m-%d') AS date,UNIX_TIMESTAMP(FROM_UNIXTIME(created_time, '%Y-%m-%d')) AS date_unix,'km' AS unit")
            ->where('user_id',$_usr_user['user_id'])
            ->whereRaw("FROM_UNIXTIME(created_time, '%Y-%m')='".$data['month_time']."' AND created_time>0")
            ->groupBy('date')
            ->get();

        $targetDistance = UserTargetPunch::where('user_id',$_usr_user['user_id'])->where('month_time',$data['month_time'])->value('target_distance');

        return $this->success([
            'target_distance' => $targetDistance,
            'sum_month' => $userPlaySumMonth,
        ]);
    }

    /**
     * 获取between在Redis用户hkeys提交时间列表
     * @param Request $request
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/20 11:11
     */
    public function getCacePostBetweenPlayList(Request $request): JsonResponse
    {
        $data = $request->all();
        isset($data['phone'])&&!empty($data['phone']) ? $map['phone'] = $data['phone'] : '';
        isset($data['user_id'])&&!empty($data['user_id']) ? $map['user_id'] = $data['user_id'] : '';
        if (empty($map)) return $this->error(0,'请传入正确的phone和user_id');
        $_usr_user = UsrUser::where($map)->where('status',1)->first();
        if (!$_usr_user) return $this->error(0,'用户不存在');

        //获取缓存记录列表
        Redis::select(1);
        $hvals = Redis::hvals('post_between_play_'.$_usr_user['user_id'].'_'.$data['times']);//返回哈希表所有字段的值

        $list = [];
        if ($hvals){
            foreach ($hvals as $k=>$v) {
                $list[] = json_decode($v,true);
            }
        }
        return $this->success($list);
    }

}
