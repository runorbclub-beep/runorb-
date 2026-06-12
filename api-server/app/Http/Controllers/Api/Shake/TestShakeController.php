<?php
/**
 * 摇加油手动
 */

namespace App\Http\Controllers\Api\Shake;
date_default_timezone_set('Asia/Shanghai');

use App\Console\Commands\Shake;
use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\ShakeGroup;
use App\Models\ShakeGroupUser;
use App\Models\SysShake;
use App\Models\UsrUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TestShakeController
{
    //团队数量
    const SHAKE_GROUP_NUM = 8;

    //马匹名称 李宁，安踏，海尔，格力，美的，平安，阳光，耐克
    const SHAKE_GROUP_TITLE = [
        '李宁',
        '安踏',
        '海尔',
        '格力',
        '美的',
        '平安',
        '阳光',
        '耐克',
    ];
    
    /*'赤兔马',
        '的卢马',
        '白龙马',
        '绝影马',
        '照夜玉狮子',
        '忽雷驳',
        '乌骓马',
        '黄骠马',*/


    /**
     * 创建要加油
     */
    public static function createShake(Request $request)
    {
        $nowDate = date('Y-m-d H:i:s');
        $startDate = $request->get('start_time') ?? $nowDate;
        $stopDate = $request->get('stop_time') ?? $nowDate;

        if ($startDate > $nowDate) {
            $status = 0;
            if ($startDate <= date("Y-m-d H:i:s", strtotime("+10 min", strtotime($nowDate)))) {
                $status = 2;
            }
        } else {
            $status = 1;
            if ($stopDate < $nowDate) {
                $status = 3;
            }
        }

//        dd($startDate, $stopDate, $status);

        $_snowflake = new Snowflake(StaticDataController::$_workId);
        $_sys_shake_id = $_snowflake->nextId();

        // 创建赛事
        $data = [
            'sys_shake_id' => $_sys_shake_id,
            'title' => '搖加油',
            'datetime' => strtotime(date('Y-m-d', strtotime($startDate))),
            'start_time' => strtotime($startDate),
            'stop_time' => strtotime($stopDate),
            'each_integral' => 100,
            'status' => $status, //0：未开始 1：进行中 2：开始报名 3：已结束
            'created_time' => time(),
            'updated_time' => time(),
            'created_uid' => 0,
            'updated_uid' => 0
        ];

        SysShake::create($data);

        //创建队伍-马匹
        $shakeGroup = [];
        for ($i = 0; $i < self::SHAKE_GROUP_NUM; $i++) {
            $shakeGroup[] = [
                'shake_group_id' => $_snowflake->nextId(),
                'sys_shake_id' => $_sys_shake_id,
                'title' => self::SHAKE_GROUP_TITLE[$i] ?? $i + 1,
                'start_time' => 0,
                'stop_time' => 0,
                'num' => 0,
                'distance' => (double)0,
                'integral' => 0,
                'status' => 1,
                'index' => $i,
                'datetime' => strtotime(date('Y-m-d')),
                'ranking' => 0
            ];
        }

        $data['group_list'] = $shakeGroup;
        $redisName = 'SHAKEINFO-' . $_sys_shake_id;
        Redis::select(14);
        Redis::set($redisName, json_encode($data));
        Redis::Expire($redisName, 100000);

        return [
            'code' => 1,
            'msg' => '创建成功'
        ];
    }

    /**
     * 更新摇加油，定时任务
     * @param Request $request
     */
    public function updateShakeStatus(Request $request)
    {
        $data = $request->all();
        Log::info('定时器：', [111]);
        $res = SysShake::where('datetime', strtotime($data['times']))->orderBy('created_time', 'desc')->first(['sys_shake_id', 'start_time', 'stop_time', 'status']);

        if ($res) {
            //0：未开始 1：进行中 2：开始报名 3：已结束
            if (date('H:i') == date('H:i', $res->start_time)) { //开始
                $status = 1;
            } elseif (date('H:i') == date('H:i', $res->stop_time)) { //结束
                $status = 3;
            } elseif (date('H:i', strtotime('+10 minute')) == date('H:i', $res->start_time)) {   //开始报名
                $status = 2;
            }
            $status = 3;
            if (isset($status) && $status) {
                //更新redis
                $redisName = 'SHAKEINFO-' . $res->sys_shake_id;
                Redis::select(14);
                $_shakeInfo = Redis::get($redisName);
                $_shakeInfo = $_shakeInfo ? json_decode($_shakeInfo, true) : [];

                if ($_shakeInfo) {
                    $_shakeInfo['status'] = $status;
                    Redis::set($redisName, json_encode($_shakeInfo));
                    Redis::Expire($redisName, 100000);

                    //结束时，落地数据，及发放积分
                    if ($status == 3 && $_shakeInfo) {
                        //判断是否已发放过
                        $issys_shake_id = ShakeGroupUser::where('sys_shake_id',$res->sys_shake_id)->first();
                        if (!empty($issys_shake_id)) throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.update_shake_status'));

                        $groupList = $_shakeInfo['group_list'] ?? [];
                        unset($_shakeInfo['group_list']);
                        Shake::getGroupDistance($res->sys_shake_id);

                        foreach ($groupList as $i => $groupLi) {
                            if (isset($groupDistance[$groupLi['shake_group_id']]) && $groupDistance[$groupLi['shake_group_id']]) {
                                $groupList[$i]['distance'] = $groupDistance[$groupLi['shake_group_id']] ?? 0;
                            }
                        }

                        $distances = array_column($groupList, 'distance');
                        array_multisort($distances, SORT_DESC, $groupList);
                        //排名
                        $allIntegral = array_sum(array_column($groupList, 'integral')); //总积分
                        foreach ($groupList as $i => &$shakeG) {
                            unset($shakeG['date']);
                            $shakeG['ranking'] = $i + 1;
                            //团队积分
                            $shakeG['integral'] = $shakeG['ranking'] <= 3 && $shakeG['distance'] > 0 ? ShakeController::calculateGroupIntegral($allIntegral, $shakeG['ranking']) : 0;
                        }

                        //更新数据库赛事
                        unset($_shakeInfo['date']);
                        SysShake::where('sys_shake_id', $_shakeInfo['sys_shake_id'])->update($_shakeInfo);

                        //更新团队数据
                        ShakeGroup::insert($groupList);

                        //更新所有用户信息
                        $allUserList = [];
                        $userList = [];
                        $redisUserInfoName = 'SHAKEINFO-' . $_shakeInfo['sys_shake_id'] . '-USERINFO';
                        $allUserRedisList = Redis::hgetAll($redisUserInfoName);
                        if ($allUserRedisList && is_array($allUserRedisList)) {
                            foreach ($allUserRedisList as $key => $allUserRedis) {
                                $myInfo = $allUserRedis ? json_decode($allUserRedis, true) : [];
                                $userInfoIntegral = ShakeController::calculateIntegral($allIntegral, $groupList, $myInfo);
                                $myInfo['integral'] = $userInfoIntegral['integral'] ?? 0;
                                $myInfo['integral_join'] = $userInfoIntegral['integral_join'] ?? 0;

                                $myInfo['play_data'] = $myInfo['play_data'] ? json_encode($myInfo['play_data']) : '';
                                unset($myInfo['date']);
                                $allUserList[] = $myInfo;

                                $userList[] = [
                                    'user_id' => $myInfo['user_id'],
                                    'integral' => $myInfo['integral'] ?? 0
                                ];
                            }

                            //新增团队报名
                            if ($allUserList) {
                                ShakeGroupUser::insert($allUserList);
                            }

                            //更新用户积分
                            if ($userList && is_array($userList)) {
                                Redis::select(1);
                                foreach ($userList as $user) {
                                    $_token = UsrUser::where('user_id', $user['user_id'])->value('access_token');
                                    $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);
                                    $_usr_user['integral'] = $_usr_user['integral'] ?? 0;
                                    $_usr_user['integral'] += $user['integral'];
                                    //更新缓存
                                    Redis::hset("usr_user", $_token, json_encode($_usr_user));

                                    //更新数据库
                                    UsrUser::where('user_id', $user['user_id'])->update(['integral' => $_usr_user['integral']]);
                                }
                            }
                        }
                        //结束时，删除该赛事的所有REDIS
//                        Redis::select(14);
//                        Redis::del($redisName); //删除赛事详情
//                        Redis::del($redisUserInfoName); //删除赛事的参赛人员
                    }
                }
            }
        }
    }


}
