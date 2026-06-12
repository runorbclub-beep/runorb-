<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\UserPlay;
use App\Models\UserTargetPunch;
use Illuminate\Support\Carbon;

/**
 * 用户目标打卡Service
 * Class UserTargetPunchService
 * @package App\Services
 * User: zxw
 * Date: 2021/11/24 09:09
 */
class UserTargetPunchService
{
    /**
     * 根据ID获取打卡详情
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/11/24 09:30
     */
    public function getUserTargetPunchDetail($param)
    {
        return UserTargetPunch::where('id',$param['id'])->first();
    }

    /**
     * 添加打卡目标
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/11/24 09:27
     * @throws BusinessException
     */
    public function add($param)
    {
        try {
            $UserTargetPunch = UserTargetPunch::insert($param);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex);
        }
        return $UserTargetPunch;
    }

    /**
     * 编辑打卡目标
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/11/24 09:36
     * @throws BusinessException
     */
    public function edit($param)
    {
        $userTargetPunch = self::getUserTargetPunchDetail($param);//dd($userTargetPunch->toArray());
        try {
            isset($param['month_time']) ? $userTargetPunch->month_time = $param['month_time'] : '';
            isset($param['target_distance']) ? $userTargetPunch->target_distance = $param['target_distance'] : '';
            isset($param['min_days']) ? $userTargetPunch->min_days = $param['min_days'] : '';
            isset($param['fulfil_days']) ? $userTargetPunch->fulfil_days = $param['fulfil_days'] : '';
            isset($param['status']) ? $userTargetPunch->status = $param['status'] : '';
            $userTargetPunch->save();
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $userTargetPunch;
    }

    /**
     * 获取用户所有打卡目标
     * @param $param
     * @param array $select
     * @return mixed
     * User: zxw
     * Date: 2021/11/24 10:15
     */
    public function list($param, array $select = ['*'])
    {
        return UserTargetPunch::select($select)->where('user_id',$param['user_id'])->get();
    }

    /**
     * 根据月份获取数据
     * @param $param
     * @param array|string[] $select
     * @return mixed
     * User: zxw
     * Date: 2021/11/25 11:28
     */
    public function getMonthList($param, array $select = ['*'])
    {
        return UserTargetPunch::select($select)->whereIn('month_time',$param['month_time'])->where('user_id',$param['user_id'])->get();
    }

    /**
     * 根据条件修改打卡目标
     * @param $param
     * @param $map
     * @return mixed
     * User: zxw
     * Date: 2021/11/25 14:21
     */
    public function editMonthTime($param,$map)
    {
        return UserTargetPunch::whereIn('month_time',$param)
            ->where('user_id',$map['user_id'])
            ->update([
                'target_distance' => $map['target_distance'],
                'min_days' => $map['min_days'],
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]);
    }

    /**
     * 定时任务
     * @return bool
     * User: zxw
     * Date: 2021/11/25 16:15
     */
    public function getUserTargetPunchsConsoleList(): bool
    {
        //先将当月未进行的修改为进行状态
        $userTargetPunch = UserTargetPunch::where('status',3)->where('month_time',date('Y-m'))->get();
        if (count($userTargetPunch) > 0){
            UserTargetPunch::where('month_time',date('Y-m'))
                ->where('status',3)
                ->update([
                    'status' => 1,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]);
        }

        //使用游标获取状态为进行中的数据
        $userTargetPunchs = UserTargetPunch::where('status', 1)->cursor();

        foreach ($userTargetPunchs as $cursor) {
            $cursor->fulfil_days = UserPlay::selectRaw("user_id,TRUNCATE(SUM(distance)/1000,3) AS distance,FROM_UNIXTIME(created_time, '%Y-%m-%d') AS date")
                ->whereRaw("user_id=".$cursor->user_id." AND FROM_UNIXTIME(created_time,'%Y-%m')='".$cursor->month_time."' AND status=1")
                ->groupBy('date')
                ->having('distance','>=',$cursor->target_distance)
                ->count();

            if ($cursor->fulfil_days >= $cursor->min_days){
                $cursor->status = 2;
            }else{
                strtotime($cursor->month_time) > strtotime(date('Y-m')) ? $cursor->status = 3 : '';
                strtotime($cursor->month_time) == strtotime(date('Y-m')) ? $cursor->status = 1 : '';
                strtotime($cursor->month_time) < strtotime(date('Y-m')) ? $cursor->status = 0 : '';
            }
            $cursor->save();
        }
        unset($userTargetPunch,$userTargetPunchs);
        return true;
    }
}
