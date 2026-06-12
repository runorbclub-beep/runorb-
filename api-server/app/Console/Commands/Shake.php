<?php
/**
 * 摇加油定时器
 */

namespace App\Console\Commands;
date_default_timezone_set('Asia/Shanghai');

use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Api\Shake\ShakeController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\BrandRedeemLog;
use App\Models\ShakeGroup;
use App\Models\ShakeGroupUser;
use App\Models\SysSetting;
use App\Models\SysShake;
use App\Models\UsrUser;
use App\Services\YouzanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Shake
{
    //团队数量
    const SHAKE_GROUP_NUM = 8;

    //马匹名称 李宁，安踏，海尔，格力，美的，平安，阳光，特步
    // const SHAKE_GROUP_TITLE = [
    //     '李宁',
    //     '安踏',
    //     '海尔',
    //     '格力',
    //     '美的',
    //     '平安',
    //     '阳光',
    //     '特步',
    // ];
    
    const SHAKE_GROUP_TITLE = [
        '赤兔马',
        '的卢马',
        '白龙马',
        '绝影马',
        '照夜玉狮子',
        '忽雷驳',
        '乌骓马',
        '黄骠马',
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
    public static function createShake()
    {
        $_snowflake = new Snowflake(StaticDataController::$_workId);
        $_sys_shake_id = $_snowflake->nextId();
        //获取积分配置
        $_sys_setting = SysSetting::where('sys_setting_id',48216651664986112)->first();
        $_sys_shake = SysShake::where('datetime',strtotime(date('Y-m-d')))->first();
        if ($_sys_shake) {
            Log::info("createShake时已存在：".json_encode($_sys_shake,true));
            return;
        }

        // 创建赛事
        $data = [
            'sys_shake_id' => $_sys_shake_id,
            'title' => '搖加油',
            'datetime' => strtotime(date('Y-m-d')),
//            'start_time' => strtotime(date('Y-m-d 16:15:00')),
            'start_time' => strtotime(date('Y-m-d 06:00:00')),
            'stop_time' => strtotime(date('Y-m-d 23:30:00')),
//            'stop_time' => strtotime(date('Y-m-d 16:30:00')),
            'each_integral' => $_sys_setting['each_integral'] ?? 300,
            'status' => 0, //0：未开始 1：进行中 2：开始报名 3：已结束
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
//        if ($shakeGroup) {
//            ShakeGroup::insert($shakeGroup);
//        }

        $data['group_list'] = $shakeGroup;
        $redisName = 'SHAKEINFO-' . $_sys_shake_id;
        Redis::select(14);
        Redis::set($redisName, json_encode($data));
        Redis::Expire($redisName, 100000);
    }


    /**
     * 更新搖加油狀態
     */
    public static function changeShakeStatus()
    {
        // Log::info('定时器：', [111222]);
        $res = SysShake::where('datetime', strtotime(date('Y-m-d')))->orderBy('created_time', 'desc')->first(['sys_shake_id', 'start_time', 'stop_time', 'status']);
        if ($res) {
            //0：未开始 1：进行中 2：开始报名 3：已结束
            if (date('H:i') == date('H:i', $res->start_time)) { //开始
                $status = 1;
            } elseif (date('H:i') == date('H:i', $res->stop_time)) { //结束
                $status = 3;
            } elseif (date('H:i', strtotime('+10 minute')) == date('H:i', $res->start_time)) {   //开始报名
                $status = 2;
            }
//            $status = 1;
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
                        $groupList = $_shakeInfo['group_list'] ?? [];
                        unset($_shakeInfo['group_list']);

                        $groupDistance = self::getGroupDistance($res->sys_shake_id);
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
                                $youzanService = new YouzanService();
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

                                    //给对应有赞平台的账户加对应积分与记录积分账单
                                    $userData = UsrUser::selectRaw('user_id,integral,phone')->where('user_id', $user['user_id'])->first();
                                    try {
                                        //生产订单号
                                        $orders = get_order_number('YJY-');
                                        if ($user['integral'] > 0){//发放积分大于0的用户才进行发放有赞操作
                                            $msg = $youzanService->increaseUserPoints([
                                                'reason' => '“全云动” 摇加油积分发放',
                                                'points' =>  $user['integral'],
                                                'account_id' => $userData['phone'],
                                                'biz_value' => $orders
                                            ]);
                                            if ($msg['code'] !== 200){
                                                Log::info(date('Y-m-d H:i:s').'--摇加油给对应有赞平台的账户加对应积分:'.json_encode($msg,true));
                                            }
                                        }
                                        //记录积分账单
                                        BrandRedeemLog::create([
                                            'order_no' => $orders,
                                            'change_code' => $orders,
                                            'type' => 1,
                                            'integral_bill_type' => 2,
                                            'user_id' => $user['user_id'],
                                            'integral' => $user['integral'],
                                            'integral_balance' => $userData['integral'],
                                            'project_name' => '“全云动” 摇加油积分发放',
                                            'remark' => '“全云动” 摇加油积分发放',
                                            'created_at' => Carbon::now()->toDateTimeString()
                                        ]);
                                    }catch (\Throwable $ex){
                                        Log::info(date('Y-m-d H:i:s').'--摇加油给对应有赞平台的账户加对应积分:'.json_encode($ex,true));
                                    }
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


    public static function getGroupDistance($sysShakeId)
    {
        Redis::select(14);
        $groupList = [];
        $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
        $allUserRedisList = Redis::hgetAll($redisUserInfoName);
        foreach ($allUserRedisList as $key => $allUserRedis) {
            $userInfo = $allUserRedis ? json_decode($allUserRedis, true) : [];
            $groupList[$userInfo['shake_group_id']] = $groupList[$userInfo['shake_group_id']] ?? 0;
            $groupList[$userInfo['shake_group_id']] += $userInfo['distance'] ?? 0;
        }

        return $groupList;
    }


}
