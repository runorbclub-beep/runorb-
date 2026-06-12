<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\PkRoom;
use App\Models\PostPlayLog;
use App\Models\UserPkList;
use Illuminate\Support\Facades\Log;

/**
 * 定时任务Service
 * Class CrontabService
 * @package App\Services
 * User: zxw
 * Date: 2021/12/03 14:16
 */
class CrontabService
{
    /**
     * 用户V3数据上传队列失败任务弥补定时任务
     * User: zxw
     * Date: 2021/12/21 14:40
     */
    public static function localPlayConsole()
    {
        $postPlayLog = PostPlayLog::where('status', 1)
            ->where('created_at', '<=', now()->subMinutes(1)->toDateTimeString())
            ->cursor();
        $localPlayService = new LocalPlayService();
        foreach ($postPlayLog as $cursor) {
            try {
                Log::info("补偿任务执行post_play_id：".$cursor->post_play_id);
                $localPlayService->handlePlayLog($cursor);
            } catch (\Throwable $ex) {
                PostPlayLog::where('post_play_id', $cursor->post_play_id)->update(['status' => 3,'remark' => json_encode($ex,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }
    }

    /**
     * 批量处理PK结果数据错误问题
     * @return mixed
     * User: zxw
     * Date: 2021/12/03 14:29
     */
    public static function getPkList()
    {
        //获取用户已结束的PK列表
        $userPkListQuery = UserPkList::where([
            'user_pk_list.status' => 1,
//            'user_pk_list.pk_room_id' => 115618806075756544
//            'usr_user.user_id' => 89062416159084544
        ])
            ->join('pk_room', function ($join) {
                $join->on('pk_room.pk_room_id', '=', 'user_pk_list.pk_room_id');
            })
            ->join('usr_user', function ($join) {
                $join->on('usr_user.user_id', '=', 'user_pk_list.user_id');
            })
            ->join('user_pk_list as user_pk_list_b', function ($join) {
                $join->on('user_pk_list_b.pk_room_id', '=', 'user_pk_list.pk_room_id');
                $join->on('user_pk_list_b.user_id', '<>', 'user_pk_list.user_id');
            })
            ->join('usr_user as usr_user_b', function ($join) {
                $join->on('usr_user_b.user_id', '=', 'user_pk_list_b.user_id');
            });

        $userPkListQuery = $userPkListQuery->selectRaw("user_pk_list.user_pk_list_id,user_pk_list.pk_room_id,user_pk_list.user_group,user_pk_list.group_win,user_pk_list.user_group_title,ROUND(user_pk_list.distance/1000,3) AS distance,IF(user_pk_list.group_win=user_pk_list.user_group AND user_pk_list.user_group is not null,1,0) AS is_win,pk_room.pk_type,FROM_UNIXTIME(pk_room.stop_time,'%Y-%m-%d %H:%i:%s') AS stop_time,usr_user.sys_sex_id,usr_user.user_name,CONCAT('" . StaticDataController::$_server_url . "/',usr_user.user_img) as user_img,COUNT(user_pk_list.user_group) AS my_count,usr_user_b.sys_sex_id as b_sys_sex_id,usr_user_b.user_name as b_user_name,CONCAT('" . StaticDataController::$_server_url . "/',usr_user_b.user_img) as b_user_img,user_pk_list_b.user_pk_list_id as b_user_pk_list_id,COUNT(user_pk_list_b.user_group) AS b_count,user_pk_list_b.user_group AS b_user_group,user_pk_list_b.user_group_title AS b_user_group_title,ROUND(user_pk_list_b.distance/1000,3) AS b_distance,'km' AS unit")
            ->orderBy("pk_room.stop_time", "DESC")
            ->groupBy("user_pk_list.pk_room_id")
            ->cursor();

        $data = [];
        foreach ($userPkListQuery as $k => $v) {
//            if (empty($v['distance']) && empty($v['b_distance'])){
//                UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                    'status' => -1
//                ]);
//            }
            PkRoom::where('pk_room_id', $v['pk_room_id'])->update([
                'group_win' => $v['user_group']
            ]);
            if ($v['user_group'] == $v['group_win']) {
                if ($v['distance'] < $v['b_distance']) {
//                    $data[] = $v;
//                    PkRoom::where('pk_room_id',$v['pk_room_id'])->update([
//                        'group_win' => $v['b_user_group']
//                    ]);
                }
                if ($v['distance'] > $v['b_distance']) {
//                    $data[] = $v;
//                    UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                        'group_win' => $v['user_group']
//                    ]);
                }
//                if (empty($v['distance']) && empty($v['b_distance'])){
//                    UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                        'status' => -1
//                    ]);
//                }
            } else {
                if ($v['distance'] > $v['b_distance']) {
//                    $data[] = $v;
//                    PkRoom::where('pk_room_id',$v['pk_room_id'])->update([
//                        'group_win' => $v['user_group']
//                    ]);
//                    UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                        'group_win' => $v['user_group']
//                    ]);
//                    if (empty($v['distance']) && empty($v['b_distance'])){
//                        UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                            'status' => -1
//                        ]);
//                    }
                }
                if ($v['distance'] < $v['b_distance']) {
//                    $data[] = $v;
//                    UserPkList::where('pk_room_id',$v['pk_room_id'])->update([
//                        'group_win' => $v['b_user_group']
//                    ]);
                }
            }
        }

        return $data;
    }



}
