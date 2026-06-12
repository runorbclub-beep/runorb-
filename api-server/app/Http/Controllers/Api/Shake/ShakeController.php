<?php

namespace App\Http\Controllers\Api\Shake;
date_default_timezone_set('Asia/Shanghai');

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\PreventDuplication;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Requests\Api\Shake\getMyShakeBoostRankingRequest;
use App\Http\Requests\Api\Shake\getMyShakeHelpDetailRequest;
use App\Models\BrandRedeemLog;
use App\Models\Countries;
use App\Models\ShakeGroup;
use App\Models\ShakeGroupUser;
use App\Models\SysSetting;
use App\Models\SysShake;
use App\Models\UsrUser;
use App\Services\ShakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Console\Commands\Shake;

class ShakeController extends Controller
{

    /**
     * 首页
     *
     * @return array
     */
    public function postshakeIndex(Request $request)
    {
        $_token = $request->header('token');
        //赛事状态
        $_arr = $this->getTodayShakeInfo();
        $status = $_arr['status'] ?? 0;
        //我的前三记录
        $logs = $this->myShakeThreeList($_token);


        //        语言
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_banner_img = "";
        if ($_language == "zh-CN") {
            $_banner_img = StaticDataController::$_server_url . '/shake/shake_banner.png?v=' . date('Ymd');
        } else {
            $_banner_img = StaticDataController::$_server_url . '/shake/shake_banner_en.jpg?v=' . date('Ymd');
        }

        //获取用户加油累计获得积分与当前剩余总积分
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);
        $integral = BrandRedeemLog::where(['user_id' => $_usr_user['user_id'],'type' => 1,'integral_bill_type' => 2])->sum('integral');//加油累计获得积分
        $userIntegral = UsrUser::where('user_id',$_usr_user['user_id'])->where('status',1)->value('integral');//当前剩余总积分

        return [
            "code" => 1,
            "msg" => "success",
            "data" => [
                'status' => $status,
                'start_time' => '06:00',
                'stop_time' => '23:30',
                'banner_img' => $_banner_img,
                'banner_link' => '',
                'my_logs' => $logs,
                'shake_integral' => $integral,
                'user_integral' => $userIntegral
            ]
        ];
    }


    /**
     * 获取比赛详情
     *
     * @return array
     */
//    public function postShakeInfo(Request $request)
//    {
//        Redis::select(1);
//        $_usr_user = json_decode(Redis::hget("usr_user", $request->header('token')), true);
//        Redis::select(14);
//        if (!PreventDuplication::check($_usr_user['user_id'], 'postShakeInfo', 1)) {
//            return [
//                'code' => 0,
//                'msg' => '请求过于频繁，请稍后再试'
//            ];
//        }
//
//        $data = $this->getTodayShakeInfo(true);
//        if ($data) {
//            Redis::select(14);
//            $redisName = 'SHAKEINFO-' . $data['sys_shake_id'] . '-USERINFO';
//            $myInfo = Redis::hget($redisName, $_usr_user['user_id']);
//            $arrMyInfo = $myInfo ? json_decode($myInfo, true) : [];
//            unset($arrMyInfo['play_data']);
//            $data['my_info'] = (object)$arrMyInfo;
//            //0：未开始 1：进行中 2：开始报名 3：已结束
//            $data['countdown'] = self::getCountDown($data);
//        }
//        return [
//            "code" => 1,
//            "msg" => "success",
//            "data" => $data ?? []
//        ];
//    }
    public function postShakeInfo(Request $request)
    {
        $data = $this->getTodayShakeInfo(true);
        if ($data) {
            Redis::select(1);
            $_usr_user = json_decode(Redis::hget("usr_user", $request->header('token')), true);

            Redis::select(14);
            $redisName = 'SHAKEINFO-' . $data['sys_shake_id'] . '-USERINFO';
            $myInfo = Redis::hget($redisName, $_usr_user['user_id']);
            $arrMyInfo = $myInfo ? json_decode($myInfo, true) : [];
            unset($arrMyInfo['play_data']);
            $data['my_info'] = (object)$arrMyInfo;
            //0：未开始 1：进行中 2：开始报名 3：已结束
            $data['countdown'] = self::getCountDown($data);

            //应前端要求返回shake/sign存的upload_type
            Redis::select(14);
            $data['upload_type'] = Redis::get($_usr_user['user_id'].'_'.$data['sys_shake_id']);
            if ($data['upload_type']){
                $data['upload_type'] = json_decode($data['upload_type'],true);
            }
        }

        return [
            "code" => 1,
            "msg" => "success",
            "data" => empty($data) ? null : $data
        ];
    }


    /**
     * 获取倒计时
     *
     * @param $data
     * @return int
     */
    public static function getCountDown($data)
    {
        //0：未开始 1：进行中 2：开始报名 3：已结束
        if ($data['status'] == 1) { //结束时间
            $countdown = $data['stop_time'] - time();
        } elseif ($data['status'] == 2) { //开始比赛时间
            $countdown = $data['start_time'] - time();
        } elseif ($data['status'] == 0) { //开始选马时间
            $countdown = $data['start_time'] - 600 - time();
        }

        return $countdown ?? -1;
    }


    /**
     * 获取今日ID
     *
     * -- 可使用redis
     *
     * @return array|mixed
     */
    public function getTodayShakeId()
    {
        $_sys_shake_id = SysShake::where('datetime', strtotime(date('Y-m-d')))
            ->orderBy('created_time', 'desc')
            ->value('sys_shake_id');

        return $_sys_shake_id;
    }


    /**
     * 获取今日信息
     *
     * @return array|mixed
     */
    public function getTodayShakeInfo($status = false)
    {
        $_sys_shake_id = $this->getTodayShakeId();
        if ($_sys_shake_id) {
            $redisName = 'SHAKEINFO-' . $_sys_shake_id;
            Redis::select(14);
            $_arr = Redis::get($redisName);
            $_arr = $_arr ? json_decode($_arr, true) : [];
//            $_arr['datetime'] = $_arr['datetime'] ? date('Y-m-d', $_arr['datetime']) : 0;
            $_arr['date'] = $_arr['datetime'] ? date('Y-m-d', $_arr['datetime']) : '';
            $_arr['date_unix'] = $_arr['datetime'] ? $_arr['datetime'] : '';

            if ($status == true) {
                $groupDistance = Shake::getGroupDistance($_sys_shake_id);
                foreach ($_arr['group_list'] as $i => $groupLi) {
                    if (isset($groupDistance[$groupLi['shake_group_id']]) && $groupDistance[$groupLi['shake_group_id']]) {
                        $_arr['group_list'][$i]['distance'] = $groupDistance[$groupLi['shake_group_id']] ?? 0;
                    }
                }
            }
        }

        return $_arr ?? [];
    }


    /**
     * 报名
     *
     * @param Request $request
     * @return array
     */
    public function postShakeSign(Request $request)
    {
        $_data = $request->input();
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $request->header('token')), true);

        //        语言
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        Redis::select(14);
        if (!PreventDuplication::check($_usr_user['user_id'], 'postShakeSign')) {
            return [
                'code' => 0,
                'msg' => LanguageController::getLanguage($_language, "requests_are_too_frequent")
            ];
        }

        $_shake_group_id = $request->get('shake_group_id') ?? '';
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (!$_shake_group_id) {
            return [
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            ];
        }

        //赛事信息
        $_shakeInfo = $this->getTodayShakeInfo();
        if ($_shakeInfo['status'] != 1 && $_shakeInfo['status'] != 2) {
            return [
                'code' => 0,
                'msg' => LanguageController::getLanguage($_language, "the_race_has_not_yet_started")
            ];
        }

        //赛事信息
        $shakeGroup = $_shakeInfo['group_list'] ?? [];
        $shakeGroup = array_column($shakeGroup, null, 'shake_group_id');
        if (!isset($shakeGroup[$_shake_group_id])) {
            return [
                'code' => 0,
                'msg' => LanguageController::getLanguage($_language, "cant_find_the_horse")
            ];
        }

        $redisUserInfoName = 'SHAKEINFO-' . $_shakeInfo['sys_shake_id'] . '-USERINFO';
        $isHas = Redis::hExists($redisUserInfoName, $_usr_user['user_id']); //判断该用户是否已报名
        if ($isHas && $_shakeInfo['status'] == 1) {
            return [
                'code' => 0,
                'msg' => LanguageController::getLanguage($_language, "the_match_has_started")
            ];
        }

        //更新参赛者的信息
        if ($isHas) {
            $oldUserInfo = Redis::hget($redisUserInfoName, $_usr_user['user_id']);
            $oldUserInfo = $oldUserInfo ? json_decode($oldUserInfo, true) : [];
            $oldGroupId = $oldUserInfo['shake_group_id'] ?? '';
        }

        //判断是否选择同一账户
        if (isset($oldGroupId) && $oldGroupId == $_shake_group_id) {
            return [
                'code' => 0,
                'msg' => LanguageController::getLanguage($_language, "cannot_select_the_same_horse_repeatedly")
            ];
        }

        ///报名，插入数据库
        $_snowflake = new Snowflake(StaticDataController::$_workId);
        $_add = [
            'shake_group_user_id' => $_snowflake->nextId(),
            'sys_shake_id' => $_shakeInfo['sys_shake_id'],
            'shake_group_id' => $_shake_group_id,
            'user_id' => $_usr_user['user_id'],
            'distance' => 0,
            'integral' => 0,
            'duration' => 0,
            'play_data' => null,
            'index' => $shakeGroup[$_shake_group_id]['index'] ?? 0,
            'title' => $shakeGroup[$_shake_group_id]['title'] ?? '',
            'datetime' => strtotime(date('Y-m-d')),
            'integral_join' => 0
        ];
//        ShakeGroupUser::create($_add);
        Redis::hset($redisUserInfoName, $_usr_user['user_id'], json_encode($_add)); //更新新数据
        Redis::Expire($redisUserInfoName, 100000);

        //更新redis-这次赛事的详情
        $shakeGroup[$_shake_group_id]['num'] = (int)$shakeGroup[$_shake_group_id]['num'] + 1;
        $shakeGroup[$_shake_group_id]['integral'] = (int)$shakeGroup[$_shake_group_id]['integral'];
        $shakeGroup[$_shake_group_id]['integral'] = $shakeGroup[$_shake_group_id]['integral'] + (double)$_shakeInfo['each_integral'];

        //切换马匹时，将之前买-1
        if (isset($oldGroupId) && $oldGroupId) {
            $shakeGroup[$oldGroupId]['num'] = (int)$shakeGroup[$oldGroupId]['num'] - 1;
            $shakeGroup[$oldGroupId]['num'] = $shakeGroup[$oldGroupId]['num'] < 0 ? 0 : $shakeGroup[$oldGroupId]['num'];
            $shakeGroup[$oldGroupId]['integral'] = (int)$shakeGroup[$oldGroupId]['integral'];
            $shakeGroup[$oldGroupId]['integral'] = $shakeGroup[$oldGroupId]['integral'] - (double)$_shakeInfo['each_integral'];
            $shakeGroup[$oldGroupId]['integral'] = $shakeGroup[$oldGroupId]['integral'] < 0 ? 0 : $shakeGroup[$oldGroupId]['integral'];
        }

        sort($shakeGroup);
        $_shakeInfo['group_list'] = $shakeGroup;
        $redisName = 'SHAKEINFO-' . $_shakeInfo['sys_shake_id'];
//        $_shakeInfo['datetime'] = isset($_shakeInfo['datetime']) ? strtotime($_shakeInfo['datetime']) : 0;
        Redis::set($redisName, json_encode($_shakeInfo));
        Redis::Expire($redisName, 100000);


        //=============================================== 应前端要求 /start/play 接口代码复制过来 =====================================
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

        if ($_sys_shake_id != "") {
            $_arrOfUserPlay["sys_shake_id"] = $_sys_shake_id;
        }


//        运动缓存数据，10天过期，
        Redis::setex($_rand_id, 3600 * 24 * 60, json_encode($_arrOfUserPlay));

        //应前端要求添加该字段缓存
        if (isset($_data['upload_type'])){
//            $_objSnowflake = new Snowflake(StaticDataController::$_workId);
//            $_rand_id = $_objSnowflake->nextId();
            Redis::select(14);
            Redis::set($_usr_user['user_id'].'_'.$_shakeInfo['sys_shake_id'],json_encode(['upload_type' => $_data['upload_type'],'user_play_id' => $_rand_id,'start_time' => $_start_time]));
        }


        return [
            'code' => 1,
            'msg' => LanguageController::getLanguage($_language, "match_user_join_success"),
            'data' => [
                'user_play_id' => $_rand_id ?? null
            ]
        ];
    }


    /**
     * _赛事运动数据--摇加油
     *
     * @param $sysShakeId //摇加油ID
     * @param $usrUser //用户信息
     * @param $addCricle //本次增加的圈数
     * @param int $addTime
     * @param int $force
     * @param int $newaddCricle
     * @return array
     */
    public static function ShakePlayInfo($sysShakeId, $usrUser, $addCricle, $addTime = 0, $force = 0, $thisDistance = 0)
    {
        Redis::select(14);
        $redisName = 'SHAKEINFO-' . $sysShakeId;
        $shakeInfo = Redis::get($redisName);    //赛事信息
        $shakeInfo = $shakeInfo ? json_decode($shakeInfo, true) : [];

        $groupDistance = Shake::getGroupDistance($sysShakeId);
        foreach ($shakeInfo['group_list'] as $i => $groupLi) {
            if (isset($groupDistance[$groupLi['shake_group_id']]) && $groupDistance[$groupLi['shake_group_id']]) {
                $shakeInfo['group_list'][$i]['distance'] = $groupDistance[$groupLi['shake_group_id']] ?? 0;
            }
        }

        $shakeGroup = $shakeInfo['group_list'] ?? [];
        $isEnd = 1;

//        Log::info('本次需加圈数：', [$addCricle]);

        if (isset($shakeInfo['stop_time']) && $shakeInfo['stop_time'] > time()) {
            $isEnd = 0;
            if ($addCricle >= 0) {
                //用户数据
                $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
                if (Redis::hExists($redisUserInfoName, $usrUser['user_id'])) {
                    $userInfo = Redis::hget($redisUserInfoName, $usrUser['user_id']);
                    $userInfo = $userInfo ? json_decode($userInfo, true) : [];
                    $distance = $userInfo['distance'] ?? 0;
                    $groupId = $userInfo['shake_group_id'] ?? '';

                    $addDistance = round($addCricle * StaticDataController::$_circle_distance / 100, 2);


                    //处理前端，异常增加距离，修复前端摇加油突增距离BUG
                    if (!$force == 1){
                        if (!empty($addTime)){
                            $abnormalDistance = $addDistance/$addTime;
                            $_abnormal_index = StaticDataController::$_abnormal_index;//防作弊规则
                            $abnormalExponentMolecular = $_abnormal_index['exponent_molecular']*2/60;
                            if ($abnormalDistance > $abnormalExponentMolecular){
                                $addDistance = 0;//判定为异常距离时，忽略本次上传
                            }
                        }else{
                            $addDistance = $abnormalDistance = 0;
                        }
                    }

//                    $userInfo['distance'] = $distance + $addDistance;
                    //upload_type为1时使用新流程
                    Redis::select(14);
                    $upload_type = Redis::get($usrUser['user_id'].'_'.$sysShakeId);
                    if ($upload_type == 1){
                        $addDistance = $thisDistance - $distance;
                        $userInfo['distance'] = $thisDistance;
                    }else{
                        $userInfo['distance'] = $distance + $addDistance;
                    }

                    $userInfo['distance'] = $userInfo['distance'] > 0 ? round($userInfo['distance'], 2) : 0;

                    //新增时间
                    $userInfo['duration'] = $userInfo['duration'] ?? 0;
                    $userInfo['duration'] += $addTime;

                    Redis::hset($redisUserInfoName, $usrUser['user_id'], json_encode($userInfo)); //更新用户新数据
                    Redis::Expire($redisUserInfoName, 100000);


                    //赛事信息
                    $a = $shakeGroup[$groupId]['distance'] ?? 0;
                    $shakeGroup = array_column($shakeGroup, null, 'shake_group_id');
                    $shakeGroup[$groupId]['distance'] = $shakeGroup[$groupId]['distance'] + $addDistance;
                    $shakeGroup[$groupId]['distance'] = round($shakeGroup[$groupId]['distance'], 2);

//                    Log::info('本次加完圈数团队：', [$addDistance, $a, $shakeGroup[$groupId]['distance']]);

                    $shakeGroup[$groupId]['start_time'] = $shakeGroup[$groupId]['start_time'] != null ? $shakeGroup[$groupId]['start_time'] : time();
                    $shakeGroup[$groupId]['stop_time'] = time();
                    sort($shakeGroup);
                    $shakeInfo['group_list'] = $shakeGroup;

                    $groupDistance = Shake::getGroupDistance($sysShakeId);
                    foreach ($shakeInfo['group_list'] as $i => $groupLi) {
                        if (isset($groupDistance[$groupLi['shake_group_id']]) && $groupDistance[$groupLi['shake_group_id']]) {
                            $shakeInfo['group_list'][$i]['distance'] = $groupDistance[$groupLi['shake_group_id']] ?? 0;
                        }
                    }

                    Redis::set($redisName, json_encode($shakeInfo));
                    Redis::Expire($redisName, 100000);
                }
            }
            $shakeInfo['countdown'] = self::getCountDown($shakeInfo);
            $list = $shakeInfo;
            return [
                "is_end" => $isEnd,
                "list" => $list
            ];
        } else {
            //排行榜
//            $distances = array_column($shakeGroup, 'distance');
//            array_multisort($distances, SORT_DESC, $shakeGroup);
//            foreach ($shakeGroup as $i => &$shakeG) {
//                $shakeG['ranking'] = $i + 1;
//            }
//
//            //我的信息
//            $redisName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
//            $myInfo = Redis::hget($redisName, $usrUser['user_id']);
//            $myInfo = $myInfo ? json_decode($myInfo, true) : [];
//
//            //更新积分
//            $myInfo['integral'] = self::calculateIntegral($shakeGroup, $myInfo);
//            unset($myInfo['play_data']);
            return [
                "is_end" => 1,
                "list" => []
//                "list" => $shakeGroup,
//                "my_info" => $myInfo
            ];
        }

    }

    public static function ShakePlayStop($sysShakeId, $usrUser, $addCricle, $userPlayId, $duration)
    {
        Redis::select(14);
        $redisName = 'SHAKEINFO-' . $sysShakeId;
        $shakeInfo = Redis::get($redisName);    //赛事信息
        $shakeInfo = $shakeInfo ? json_decode($shakeInfo, true) : [];

        $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
        if (Redis::hExists($redisUserInfoName, $usrUser['user_id'])) {
            $userInfo = Redis::hget($redisUserInfoName, $usrUser['user_id']);
            $userInfo = $userInfo ? json_decode($userInfo, true) : [];
        }

        if ($shakeInfo['stop_time'] > time()) {
            //用户数据
            if (isset($userInfo) && $userInfo) {
                $distance = $userInfo['distance'] ?? 0;
                $groupId = $userInfo['shake_group_id'] ?? '';

                $addDistance = round($addCricle * StaticDataController::$_circle_distance / 100, 2);
                $userInfo['distance'] = $distance + $addDistance;
                $userInfo['distance'] = $userInfo['distance'] > 0 ? round($userInfo['distance'], 2) : 0;

                $userInfo['play_data'][] = $userPlayId;
                $userInfo['duration'] += (double)$duration;

                Redis::hset($redisUserInfoName, $usrUser['user_id'], json_encode($userInfo)); //更新用户新数据
                Redis::Expire($redisUserInfoName, 100000);

                //赛事信息
                $shakeGroup = $shakeInfo['group_list'] ?? [];

                $shakeGroup = array_column($shakeGroup, null, 'shake_group_id');
                $shakeGroup[$groupId]['distance'] = $shakeGroup[$groupId]['distance'] + $addDistance;
                $shakeGroup[$groupId]['distance'] = $shakeGroup[$groupId]['distance'] > 0 ? round($shakeGroup[$groupId]['distance'], 2) : 0;

                $shakeGroup[$groupId]['start_time'] = $shakeGroup[$groupId]['start_time'] != null ? $shakeGroup[$groupId]['start_time'] : time();
                $shakeGroup[$groupId]['stop_time'] = time();
                sort($shakeGroup);
                $shakeInfo['group_list'] = $shakeGroup;
                Redis::set($redisName, json_encode($shakeInfo));
                Redis::Expire($redisName, 100000);
            }
        }
    }


    /**
     * 获取前三条
     *
     * @param $userId
     * @return mixed
     */
    public function myShakeThreeList($_token)
    {
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_arrOfList = ShakeGroupUser::where([
            "shake_group_user.user_id" => $_usr_user['user_id']
        ])->join("shake_group", function ($join) {
            $join->on("shake_group.shake_group_id", "=", "shake_group_user.shake_group_id");
        })->selectRaw('
            shake_group_user.sys_shake_id, shake_group_user.shake_group_user_id, shake_group_user.integral, shake_group_user.distance, shake_group.datetime, shake_group.index, shake_group.title
            , shake_group.ranking, FROM_UNIXTIME(shake_group.datetime, "%Y-%m-%d") as date,shake_group.datetime AS datetime_unix
        ')->orderBy("shake_group.datetime", "DESC")->orderBy("shake_group.shake_group_id", "DESC")->limit(3)->get();

        return $_arrOfList;
    }


    /**
     * 我的比赛列表
     *
     * @param Request $request
     * @return array
     */
    public static function myShakeList(Request $request)
    {
        $_data = $request->input();
        $_token = $request->header('token');

        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 10;
        $_offset = ($_page - 1) * $_limit;

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_arrOfPkListQuery = ShakeGroupUser::where([
            "shake_group_user.user_id" => $_usr_user['user_id']
        ])->join("shake_group", function ($join) {
            $join->on("shake_group.shake_group_id", "=", "shake_group_user.shake_group_id");
        })->selectRaw('
            shake_group_user.sys_shake_id, shake_group_user.shake_group_user_id, shake_group_user.integral, shake_group_user.integral_join, shake_group_user.distance, shake_group.datetime, shake_group.index, shake_group.title
            , shake_group.ranking, FROM_UNIXTIME(shake_group.datetime, "%Y-%m-%d") as date,shake_group.datetime AS date_unix
        ')->orderBy("shake_group.datetime", "DESC")->orderBy("shake_group.shake_group_id", "DESC");

        //分页
        $_arrListCount = $_arrOfPkListQuery->count();
        $_arrList = $_arrOfPkListQuery->skip($_offset)->take($_limit)->get();

        return [
            "code" => 1,
            "msg" => "success",
            "data" => [
                "count" => $_arrListCount,
                "list" => $_arrList
            ]
        ];
    }


    /**
     * 我的记录详情
     *
     * @param Request $request
     * @return array
     */
    public function myShakeInfo(Request $request)
    {
        $sysShakeId = $request->get('sys_shake_id') ?? 0;
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (!$sysShakeId) {
            return [
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            ];
        }

        $_token = $request->header('token');
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        Redis::select(14);
        $redisName = 'SHAKEINFO-' . $sysShakeId;
        if (Redis::Exists($redisName)) {
            $_shakeInfo = Redis::get($redisName);
            $_shakeInfo = $_shakeInfo ? json_decode($_shakeInfo, true) : [];
            $arrGroupList = $_shakeInfo['group_list'] ?? [];
            $distances = array_column($arrGroupList, 'distance');
            array_multisort($distances, SORT_DESC, $arrGroupList);

            $allIntegral = array_sum(array_column($arrGroupList, 'integral'));  //总积分
            //排名
            foreach ($arrGroupList as $i => &$shakeG) {
                $shakeG['ranking'] = $i + 1;
                $shakeG['integral'] = $shakeG['ranking'] <= 3 && $shakeG['distance'] > 0 ? ShakeController::calculateGroupIntegral($allIntegral, $shakeG['ranking']) : 0;
            }

            $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
            if (Redis::hExists($redisUserInfoName, $_usr_user['user_id'])) {
                $userInfo = Redis::hget($redisUserInfoName, $_usr_user['user_id']);
                $userInfo = $userInfo ? json_decode($userInfo, true) : [];
                $userInfoIntegral = ShakeController::calculateIntegral($allIntegral, $arrGroupList, $userInfo);
                $userInfo['integral'] = $userInfoIntegral['integral'] ?? 0;
                $userInfo['integral_join'] = $userInfoIntegral['integral_join'] ?? 0;
                if (isset($userInfo['play_data'])) {
                    unset($userInfo['play_data']);
                }
            }

            $arrMyInfo = $userInfo ?? [];
        } else {
            //我的赛事信息
            $arrMyInfo = ShakeGroupUser::with(['usr_user' => function($query){
                $query->select('user_id','sys_sex_id', 'user_name','user_img');
            }])->where('sys_shake_id', $sysShakeId)
                ->where('user_id', $_usr_user['user_id'])
                ->first(['sys_shake_id', 'shake_group_id', 'shake_group_user_id', 'integral', 'distance', 'index', 'title', 'integral_join', 'datetime']);
            $arrMyInfo = $arrMyInfo ? $arrMyInfo->toArray() : [];
            if (!empty($arrMyInfo['usr_user']['user_img'])){
                $arrMyInfo['user_img'] = StaticDataController::$_server_url . "/" .$arrMyInfo['usr_user']['user_img'];
                unset($arrMyInfo['usr_user']);
            }
            $arrGroupList = ShakeGroup::where('sys_shake_id', $sysShakeId)->orderBy('ranking', 'asc')->get([
                'sys_shake_id', 'shake_group_id', 'title', 'index', 'num', 'distance', 'integral'
            ]);
        }

        $arrMyInfo['datetime'] = $arrMyInfo['datetime'] ?? 0;
        $arrMyInfo['date'] = $arrMyInfo['datetime'] ? date('Y-m-d', $arrMyInfo['datetime']) : 0;

        return [
            'code' => 1,
            'msg' => '请求成功',
            'data' => [
                'group_list' => $arrGroupList,
                'my_info' => (object)$arrMyInfo
            ]
        ];
    }

    /**
     * 获取助力排行
     * @param getMyShakeBoostRankingRequest $request
     * @param ShakeService $shakeService
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/24 17:06
     */
    public function getMyShakeBoostRanking(getMyShakeBoostRankingRequest $request, ShakeService $shakeService): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $shakeService->getMyShakeBoostRanking($data);
        return $this->success($list);
    }

    /**
     * 根据位置（马号）获取位置详情
     * @param getMyShakeHelpDetailRequest $request
     * @param ShakeService $shakeService
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/24 17:19
     */
    public function getMyShakeHelpDetail(getMyShakeHelpDetailRequest $request, ShakeService $shakeService): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $shakeService->getMyShakeHelpDetail($data);
        return $this->success($list);
    }


    /**
     * 计算积分
     * 第1名赛马对应的助力者，获得此次比赛总积分的50%积分。
     * 第2名赛马对应的助力者，获得此次比赛总积分30%积分。
     * 第3名赛马对应的助力者，获得此次比赛总积分10%积分。
     * 每位助力者可以获得的积分数量为： 所选马匹积分 X（个人助力里程/被助力马匹总里程数）。
     * $allIntegral, $arrGroupList, $userInfo
     * @param $groupList  array  团队列表
     * @param $myInfo     array  我的信息
     * @return array
     */
    public static function calculateIntegral($allIntegral, array $groupList, array $myInfo)
    {
        if (isset($myInfo['distance']) && $myInfo['distance'] > 0) {
            $allNum = array_sum(array_column($groupList, 'num')); //总人数
            $groupList = array_column($groupList, null, 'shake_group_id');  //团队列表
            $myGroupData = $groupList[$myInfo['shake_group_id']] ?? []; //我的团队

            $coefficient = $myGroupData['distance'] > 0 ? $myInfo['distance'] / $myGroupData['distance'] : 0;
            $integral = round($myGroupData['integral'] * $coefficient);

            $other = $allNum > 0 ? $allIntegral * 0.1 / $allNum : 0;

            $finalIntegral = $integral + $other;
            $finalIntegral = $finalIntegral ? round($finalIntegral) : 0;
        }

        return ['integral' => $finalIntegral ?? 0, 'integral_join' => $other ?? 0];
    }


    /**
     * 团队积分
     *
     * @param $allIntegral
     * @param $ranking
     * @return int
     */
    public static function calculateGroupIntegral($allIntegral, $ranking)
    {
        $coefficient = 0;
        switch ($ranking) {
            case 1:
                $coefficient = 0.5;
                break;
            case 2:
                $coefficient = 0.3;
                break;
            case 3:
                $coefficient = 0.1;
                break;
        }

        $integral = $allIntegral * $coefficient;
        $integral = $integral ? round($integral) : 0;

        return $integral;
    }


    /**
     * 规则
     *
     * @return array
     */
    public function postShakeRule(Request $request)
    {
        //        语言
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if ($_language == "zh-CN") {
            $content = trans("messages.shake_rule_content_zh");
            if (url()->previous() == StaticDataController::$_server_url_en){//考虑国际服中文
                $content = trans("messages.shake_rule_content_en");
            }
        } else {
            $content = trans("messages.shake_rule_content_en");
            if (url()->previous() == StaticDataController::$_server_url_zh){//考虑国服中文
                $content = trans("messages.shake_rule_content_zh");
            }
        }

        return [
            'code' => 1,
            'msg' => '请求成功',
            'data' => [
                'content' => $content,//(原)
                'content_zh' => trans("messages.shake_rule_content_zh"),//国服
                'content_en' => trans("messages.shake_rule_content_en"),//国际服
            ]
        ];
    }

    /**
     * 补全摇加油数据缺失
     * @param ShakeService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/02 14:19
     * @throws BusinessException
     */
    public function completionShake(ShakeService $service): JsonResponse
    {
        $list = $service->completionShake();
        return $this->success($list);
    }

    /**
     * 摇加油积分系数修改
     * @param Request $request
     * @return JsonResponse
     */
    public function updateEachIntegral(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['each_integral'])){
            return $this->error(ErrorCode::SEVER_ERROR,'积分系数 each_integral 不能为空！');
        }

        $sysShake = SysShake::where('datetime',strtotime(date('Y-m-d')))->first();
        $sysShake->each_integral = $data['each_integral'];

        //事务
        DB::beginTransaction();
        try {
            $sysShake->save();
            SysSetting::where('sys_setting_id',48216651664986112)->update(['each_integral' => $data['each_integral']]);
            DB::commit();
        }catch (\Throwable $ex){
            DB::rollBack();
            return $this->error(ErrorCode::SEVER_ERROR,'积分系数设置失败！');
        }

        $redisName = 'SHAKEINFO-' . $sysShake->sys_shake_id;
        Redis::select(14);
        $list = json_decode(Redis::get($redisName),true);
        $list['each_integral'] = $data['each_integral'];

        foreach ($list['group_list'] as $k => $v) {
            $list['group_list'][$k]['integral'] = $v['num']*$data['each_integral'];
        }
        Redis::set($redisName, json_encode($list));
        Redis::Expire($redisName, 100000);

        $list = json_decode(Redis::get($redisName),true);

        return $this->success($list);
    }
    
    /**
     * 获取地区
     * @param Request $request
     * @return JsonResponse
     */
    public function postAreaList(Request $request): JsonResponse
    {
        $list = Countries::where("status",1)->get()->toArray();
        return $this->success($list);
    }

}
