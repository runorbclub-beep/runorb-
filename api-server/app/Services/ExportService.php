<?php

namespace App\Services;


use App\Http\Controllers\PublicFunction\RankController;
use App\Models\QiyeShakeUser;
use App\Models\ShakeGroupUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Excel导出服务
 * Class ExportService
 * @package App\Services
 * User: zxw
 * Date: 2021/11/15 15:19
 */
class ExportService
{
    /**
     * 根据时间段查询所有报名用户的运动数据打卡统计
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/11/16 15:11
     */
    public function getUserPlay($param): array
    {
//        $type = $param['type'] == 1 ? '=' : '<>';
        $list = QiyeShakeUser::with([
            'usr_user' => function ($query) {
                $query->select('user_id', 'sys_sex_id', 'user_img', 'user_name', 'real_name')->where('status', 1);
            },
            'sys_qiye_shake' => function ($item) {
                $item->select('sys_qiye_shake_id', 'title')->where('status', 1);
            },
            'user_play' => function ($item) use ($param) {
                $item->selectRaw("user_play_id,user_id,distance,created_time,FROM_UNIXTIME(created_time, '%Y-%m-%d') AS date,COUNT(user_play_id) AS day_count")
                    ->where('status', 1)
//                    ->where('distance','>=',$param['day_distance'])
                    ->whereBetween('stop_time', [strtotime($param['s_time'] . ' 00:00:00'), strtotime($param['e_time'] . ' 23:59:59')])
                    ->groupBy('date', 'user_id')->selectRaw("user_play_id,user_id,created_time,distance," . ($param['day_distance'] / 1000) . " as param_distance,TRUNCATE(SUM(distance)/1000,3) AS distances,FROM_UNIXTIME(created_time, '%Y-%m-%d') AS date,COUNT(user_play_id) AS day_count")->groupBy('date', 'user_id')->havingRaw('distances >=' . $param['day_distance'] / 1000);
            }
        ])
            ->select('user_id', 'name', 'phone', 'qiye_shake_user_id', 'department', 'sex', 'sys_qiye_shake_id')
//            ->where('sys_qiye_shake_id', $type, 84060776356122624)
            ->where('sys_qiye_shake_id', $param['sys_qiye_shake_id'])
            ->get()
            ->map(function ($item) use ($param) {
                $item['user_play'] = $item['user_play']->toArray();
                if (!empty($item['user_play'])) {
                    $item['distance'] = array_sum(array_map(function ($val) {
                        return $val['distances'];
                    }, $item['user_play']));
                    $item['day_count'] = count($item['user_play']);
                } else {
                    $item['day_count'] = $item['distance'] = 0;
                }
                $item['s_time'] = $param['s_time'];
                $item['e_time'] = $param['e_time'];
                $item['day_distance'] = round($param['day_distance'] / 1000, 3);
                $item['title'] = $item['sys_qiye_shake']['title'] ?? '';
                $item['sys_sex_id'] = $item['usr_user']['sys_sex_id'] ?? '';
                $item['user_img'] = $item['usr_user']['user_img'] ?? '';
                $item['user_name'] = $item['usr_user']['user_name'] ?? '';
                $item['real_name'] = $item['usr_user']['real_name'] ?? '';
                unset($item['sys_qiye_shake'], $item['usr_user'], $item['user_play']);
                return $item;
            });

        return arraySort($list->toArray(), 'distance', SORT_DESC);
    }

    /**
     * 导出全国总部四项赛事排名表
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/5 10:28
     */
    public function getUserAchievements($param)
    {
        switch ($param) {
            case 'max_speed':
                //最高转速
                $list = RankController::UserAchivementV3('', $param, '', '', '', 1, 100, 69405373731180544, '', '');
                break;
            case 'onemin':
                //摇跑一分钟
                $list = RankController::UserAchivementV3('', $param, '', '', '', 1, 100, 69405373731180544, '', '');
                break;
            case 'exponent':
                //摇跑指数
                $list = RankController::UserAchivementV3('', $param, '', '', '', 1, 100, 69405373731180544, '', '');
                break;
            case 'marathon':
                //摇跑马拉松
                $list = RankController::UserAchivementV3('', $param, '', '', '', 1, 100, 69405373731180544, '', '');
                break;
            default:
                $list = [];
        }
//dd($list['data']['list']);
        return $list['data']['list'];
    }

    /**
     * 根据时间段导出摇加油积分排名Excel表
     * @param $param
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     * User: zxw
     * Date: 2022/1/18 9:57
     */
    public function getShakeIntegralRankingExport($param)
    {
        return ShakeGroupUser::with('usr_user:user_id,user_name,sys_sex_id,phone,integral')//BrandRedeemLog
        ->where('datetime', strtotime($param))
            ->orderByDesc('integral')
            ->get()
            ->map(function ($item) {
                $item->user_name = $item->usr_user->user_name;
                $item->sys_sex_id = $item->usr_user->sys_sex_id;
                $item->phone = $item->usr_user->phone;
                unset($item->usr_user);
                return $item;
            });
    }

}
