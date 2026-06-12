<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UserAchievement;
use App\Models\UserClan;
use App\Models\UserClanMember;
use App\Models\UsrUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 战队Service
 * Class ClanService
 * @package App\Services
 * User: zxw
 * Date: 2021/12/06 11:36
 */
class ClanService
{
    /**
     * 根据ID获取战队详情
     * @param $param
     * @param array $select
     * @return mixed
     * User: zxw
     * Date: 2021/12/06 16:38
     */
    public function getUserClan($param, array $select = ['*'])
    {
        $map = [];
        isset($param['id']) ? $map['id'] = $param['id'] : '';
        isset($param['title']) ? $map['title'] = $param['title'] : '';

        return UserClan::select($select)
            ->where($map)
            ->first();
    }

    /**
     * 根据ID获取战队成员详情
     * @param $param
     * @param array $select
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/12/06 16:38
     */
    public function getUserClanMember($param, array $select = ['*'])
    {
        $map = [];
        isset($param['user_clan_id']) ? $map['user_clan_id'] = $param['user_clan_id'] : '';
        isset($param['user_id']) ? $map['user_id'] = $param['user_id'] : '';
        isset($param['is_captain']) ? $map['is_captain'] = $param['is_captain'] : '';
        isset($param['status']) ? $map['status'] = $param['status'] : '';

        return UserClanMember::with('usr_user:user_id,user_name,sys_sex_id,user_img,address', 'user_clan')
            ->whereHas('usr_user', function ($query) {
                $query->where('status', 1);
            })
            ->select($select)
            ->where($map)
            ->first();
    }

    /**
     * 添加申请战队
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/12/06 15:29
     * @throws BusinessException
     */
    public function addUserClan($param)
    {
        $map = [
            'title' => $param['title'],
            'clan_avatar' => $param['clan_avatar'],
            'address' => $param['address'],
            'introduction' => $param['introduction'] ?? '',
            'telephone' => $param['telephone'] ?? '',
            'created_at' => Carbon::now()->toDateTimeString()
        ];
        try {
            $userClan = UserClan::create($map);
        } catch (\Throwable $ex) {
            Log::info('addUserClan=========：'.json_encode($ex));
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $userClan;
    }

    /**
     * 添加战队成员
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/12/06 15:33
     * @throws BusinessException
     */
    public function addUserClanMember($param)
    {
        $param['created_at'] = Carbon::now()->toDateTimeString();
        try {
            $userClan = UserClanMember::create($param);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $userClan;
    }

    /**
     * 取消申请战队
     * @param $param
     * @return bool
     * User: zxw
     * Date: 2021/12/08 18:45
     * @throws BusinessException
     */
    public function withdrawClan($param): bool
    {
        $userClanMember = UserClanMember::with('user_clan')
            ->where('user_id', $param['user_id'])
            ->where('user_clan_id', $param['user_clan_id'])
            ->first();

        if ($userClanMember->user_clan->status !== 0) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.not_in_pending_approval_status_error'));//不是待审核状态无法取消

        //数据处理
        DB::beginTransaction();
        try {
            $userClanMember->user_clan->delete();
            $userClanMember->delete();
            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return true;
    }

    /**
     * 删除战队成员
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/12/06 15:33
     * @throws BusinessException
     */
    public function delUserClanMember($param)
    {
        //当前用户如果是战队队长，需要先移交队长
        $userClanMember = self::getUserClanMember($param);
        if ($userClanMember->is_captain == 1) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.please_hand_over_to_the_captain_error'));
        }

        //数据处理
        try {
            $userClanMember = UserClanMember::where('id', $userClanMember->id)->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return $userClanMember;
    }

    /**
     * 编辑战队信息
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/12/06 17:00
     * @throws BusinessException
     */
    public function editUserClan($param)
    {
        $userClan = self::getUserClan(['id' => $param['id']]);
        //通过审核的战队名称，1年内只能修改一次
        if ($userClan->status == 1 && isset($param['title'])) {
            if ($userClan->title_time != null && strtotime("+1 year", strtotime($userClan->title_time)) > time()) {
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.can_only_be_modified_once_in_a_year_error'));
            }
            $userClan->title = $param['title'];
            $userClan->title_time = Carbon::now()->toDateTimeString();
        } else {
            isset($param['title']) ? $userClan->title = $param['title'] : '';
        }

        isset($param['clan_avatar']) ? $userClan->clan_avatar = $param['clan_avatar'] : '';
        isset($param['address']) ? $userClan->address = $param['address'] : '';
        isset($param['introduction']) ? $userClan->introduction = $param['introduction'] : '';
        isset($param['status']) ? $userClan->status = $param['status'] : '';
        isset($param['telephone']) ? $userClan->telephone = $param['telephone'] : '';
        isset($param['photo_text']) ? $userClan->photo_text = $param['photo_text'] : '';

        try {
            $userClan = $userClan->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $userClan;
    }

    /**
     * 获取战队列表
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/12/06 17:19
     * @throws BusinessException
     */
    public function getClanList($param): array
    {
        $map = [];
        $map[] = ['status', '=', 1];
        isset($param['title']) ? $map[] = ['title', 'like', '%' . $param['title'] . '%'] : '';
        $param['limit'] = $param['limit'] ?? 15;

        $userClan = UserClan::where($map)
            ->withCount(['user_clan_members as clan_count' => function ($query) {
                $query->where('status', 2);
            }])->orderBy('clan_count', 'desc')
            ->paginate($param['limit']);

        return data_list_format($userClan);
    }

    /**
     * 根据战队ID获取战队平均成绩
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/12/07 16:56
     */
    public function getAvgAchievement($param): array
    {
        //根据战队ID获取战队成员user_id
        $userClanMemberId = self::getUserClanMemberPluck($param);

        $userAchievementQuery = UserAchievement::selectRaw("*,10000/marathon AS marathon_asc")->whereIn('user_id', $userClanMemberId);

        $avg_speed_max = $userAchievementQuery->orderBy('speed_max', 'desc')->limit(3);
        $avg_speed_max = array_sum($avg_speed_max->pluck('speed_max')->toArray()) / 3;

        $avg_exponent_molecular = $userAchievementQuery->orderBy('exponent_molecular', 'desc')->limit(3);
        $avg_exponent_molecular = array_sum($avg_exponent_molecular->pluck('exponent_molecular')->toArray()) / 3;

        $avg_runball_exponent = $userAchievementQuery->orderBy('runball_exponent', 'desc')->limit(3);
        $avg_runball_exponent = array_sum($avg_runball_exponent->pluck('runball_exponent')->toArray()) / 3;

        $avg_marathon = $userAchievementQuery->orderBy('marathon_asc', 'desc')->orderBy('marathon_time', 'asc')->limit(3)->get();
        $avg_marathon = array_sum($avg_marathon->pluck('marathon')->toArray()) / 3;

        return [
            'user_count' => count($userClanMemberId),
            'avg_speed_max' => (int)$avg_speed_max,
            'avg_speed_max_unit' => empty($avg_speed_max) ? 0 : 'rpm',
            'avg_exponent_molecular' => empty($avg_exponent_molecular) ? 0 : (string)round($avg_exponent_molecular / 1000, 3),
            'avg_exponent_molecular_unit' => empty($avg_exponent_molecular) ? 0 : 'km',
            'avg_runball_exponent' => (string)round($avg_runball_exponent, 2),
            'avg_marathon' => empty($avg_marathon) ? 0 : RankController::timeFormat($avg_marathon),
        ];
    }

    /**
     * 根据战队ID获取战队平均成绩
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/12/07 16:56
     * @throws BusinessException
     */
    public function getAvgAchievementV2($param): array
    {
        //建立临时表
        $sql = DB::table('user_clan_members')
            ->leftJoin('usr_user','usr_user.user_id','=','user_clan_members.user_id')
            ->selectRaw("
                user_clan_members.user_clan_id,
                IF(user_clan_members.avg_speed_max,user_clan_members.avg_speed_max,0) AS avg_speed_max,
                IF(user_clan_members.avg_runball_exponent,user_clan_members.avg_runball_exponent,0) AS avg_runball_exponent,
                IF(user_clan_members.avg_exponent_molecular,user_clan_members.avg_exponent_molecular,0) AS avg_exponent_molecular,
                IF(user_clan_members.avg_marathon,user_clan_members.avg_marathon,0) AS avg_marathon,
                IF(1000 / user_clan_members.avg_marathon,1000 / user_clan_members.avg_marathon,0) AS avg_marathon_asc
            ")
            ->whereRaw("user_clan_members.user_clan_id=" . $param['id'] . " AND user_clan_members.status=2 AND usr_user.status=1");

        //子查询统计各战队成员数量
        $user_count = UserClanMember::query()
            ->whereHas('usr_user', function ($query) {
                $query->where('status', 1);
            })
            ->whereRaw("user_clan_members.STATUS = 2 AND user_clan_members.user_clan_id=" . $param['id'])
            ->count();

        //需要展示的字段
        $select = "dt_1.user_clan_id,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_speed_max>0,TRUE,NULL))>=3,AVG(dt_1.avg_speed_max),0),0) AS avg_speed_max,
                    'rpm' AS avg_speed_max_unit,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_runball_exponent>0,TRUE,NULL))>=3,AVG(dt_1.avg_runball_exponent),0),2) AS avg_runball_exponent,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_exponent_molecular>0,TRUE,NULL))>=3,AVG(dt_1.avg_exponent_molecular)/1000,0),3) AS avg_exponent_molecular,
                    'km' AS avg_exponent_molecular_unit,
                    TRUNCATE(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_marathon>0,TRUE,NULL))>=3,AVG(dt_1.avg_marathon),0),0) AS avg_marathon,
                    IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_marathon>0,TRUE,NULL))>=3,1000/AVG(dt_1.avg_marathon),0) AS avg_marathon_asc";

        //最高转速
        //排序
        $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
            ->selectRaw("COUNT(1)")
            ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_speed_max < dt_2.avg_speed_max");
        //最外层查询出结果
        $userClanQuery1 = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
            ->selectRaw($select)
            ->whereRaw("({$sql3->toSql()})<3")
            ->groupByRaw("dt_1.user_clan_id")
            ->orderByRaw("avg_speed_max DESC")
            ->first();
        //获取所以战队--最高转速
        $ClanQuery1 = self::userClanQuery(['type'=>1])->get();
        foreach ($ClanQuery1 as $k => $v) {
            if ($v->user_clan_id == $userClanQuery1->user_clan_id){
                $userClanQuery1->indexs_speed_max = $k+1;
            }
        }

        //摇跑一分钟
        //排序
        $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
            ->selectRaw("COUNT(1)")
            ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_exponent_molecular < dt_2.avg_exponent_molecular");
        //最外层查询出结果
        $userClanQuery2 = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
            ->selectRaw($select)
            ->whereRaw("({$sql3->toSql()})<3")
            ->groupByRaw("dt_1.user_clan_id")
            ->orderByRaw("avg_exponent_molecular DESC")
            ->first();
        //获取所以战队--摇跑一分钟
        $ClanQuery2 = self::userClanQuery(['type'=>2])->get();
        foreach ($ClanQuery2 as $k => $v) {
            if ($v->user_clan_id == $userClanQuery2->user_clan_id){
                $userClanQuery2->indexs_exponent_molecular = $k+1;
            }
        }

        //摇跑指数
        //排序
        $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
            ->selectRaw("COUNT(1)")
            ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_runball_exponent < dt_2.avg_runball_exponent");
        //最外层查询出结果
        $userClanQuery3 = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
            ->selectRaw($select)
            ->whereRaw("({$sql3->toSql()})<3")
            ->groupByRaw("dt_1.user_clan_id")
            ->orderByRaw("avg_runball_exponent DESC")
            ->first();
        //获取所以战队--摇跑指数
        $ClanQuery3 = self::userClanQuery(['type'=>3])->get();
        foreach ($ClanQuery3 as $k => $v) {
            if ($v->user_clan_id == $userClanQuery3->user_clan_id){
                $userClanQuery3->indexs_runball_exponent = $k+1;
            }
        }

        //马拉松
        //排序
        $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
            ->selectRaw("COUNT(1)")
            ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_marathon_asc < dt_2.avg_marathon_asc");

        //最外层查询出结果
        $userClanQuery4 = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
            ->selectRaw($select)
            ->whereRaw("({$sql3->toSql()})<3")
            ->groupByRaw("dt_1.user_clan_id")
            ->orderByRaw("avg_marathon_asc DESC")
            ->first();
        //获取所以战队--摇跑指数
        $ClanQuery4 = self::userClanQuery(['type'=>4])->get();
        foreach ($ClanQuery4 as $k => $v) {
            if ($v->user_clan_id == $userClanQuery4->user_clan_id){
                $userClanQuery4->indexs_marathon = $k+1;
            }
        }

        return [
            'user_count' => $user_count,
            'avg_speed_max' => empty($userClanQuery1->avg_speed_max) ? 0 : $userClanQuery1->avg_speed_max,
            'avg_speed_max_unit' => empty($userClanQuery1->avg_speed_max) ? 0 : $userClanQuery1->avg_speed_max_unit,
            'avg_exponent_molecular' => empty($userClanQuery2->avg_exponent_molecular) ? 0 : $userClanQuery2->avg_exponent_molecular,
            'avg_exponent_molecular_unit' => empty($userClanQuery2->avg_exponent_molecular) ? 0 : $userClanQuery2->avg_exponent_molecular_unit,
            'avg_runball_exponent' => empty($userClanQuery3->avg_runball_exponent) ? 0 : (string) $userClanQuery3->avg_runball_exponent,
            'avg_marathon' => empty($userClanQuery4->avg_marathon) ? 0 : RankController::timeFormat($userClanQuery4->avg_marathon),
            'indexs_speed_max' => empty($userClanQuery1->avg_speed_max) ? 0 : $userClanQuery1->indexs_speed_max,
            'indexs_exponent_molecular' => empty($userClanQuery2->avg_exponent_molecular) ? 0 : $userClanQuery2->indexs_exponent_molecular,
            'indexs_runball_exponent' => empty($userClanQuery3->avg_runball_exponent) ? 0 : $userClanQuery3->indexs_runball_exponent,
            'indexs_marathon' => empty($userClanQuery4->avg_marathon) ? 0 : $userClanQuery4->indexs_marathon,
        ];
    }

    /**
     * 根据战队ID获取战队成员user_id
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/12/10 11:57
     */
    public function getUserClanMemberPluck($param)
    {
        return UserClanMember::where([
            'user_clan_id' => $param['id'],
            'status' => 2
        ])->whereHas('usr_user', function ($query) {
            $query->where('status', 1);
        })->pluck('user_id');
    }

    /**
     * 根据战队ID获取不含队长的成员列表
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/12/07 18:10
     * @throws BusinessException
     */
    public function getClanMemberList($param): array
    {
        $map = [];
        $map[] = ['user_clan_id', '=', $param['user_clan_id']];
        isset($param['is_captain']) ? $map[] = ['is_captain', '=', $param['is_captain']] : '';
        isset($param['status']) ? $map[] = ['status', '=', $param['status']] : '';
        $param['limit'] = $param['limit'] ?? 15;

        $getClanMemberListQuery = UserClanMember::with('usr_user:user_id,user_name,sys_sex_id,user_img,address')
            ->whereHas('usr_user', function ($query) {
                $query->where('status', 1);
            })
            ->where($map);
        if (isset($param['title'])) {
            $getClanMemberListQuery = $getClanMemberListQuery->whereHas('usr_user', function ($query) use ($param) {
                $query->where('user_name', 'like', '%' . $param['title'] . '%');
            });
        }
        $getClanMemberList = $getClanMemberListQuery->orderBy('created_at', 'desc')
            ->paginate($param['limit']);
        return data_list_format($getClanMemberList);
    }

    /**
     * 编辑战队成员信息
     * @param $param
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/12/07 19:19
     * @throws BusinessException
     */
    public function editClanMember($param)
    {
        $param1 = $param;
        if (!empty($param1['status'])){//修复status查询时的编辑
            unset($param1['status']);
        }

        $getUserClanMember = self::getUserClanMember($param1);
        isset($param['is_captain']) ? $getUserClanMember->is_captain = $param['is_captain'] : '';
        isset($param['remark']) ? $getUserClanMember->remark = $param['remark'] : '';
        isset($param['reply_remark']) ? $getUserClanMember->reply_remark = $param['reply_remark'] : '';
        isset($param['status']) ? $getUserClanMember->status = $param['status'] : '';
        try {
            $getUserClanMember->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $getUserClanMember;
    }

    /**
     * 删除战队成员信息
     * @param $param
     * @return bool
     * User: zxw
     * Date: 2021/12/14 10:33
     * @throws BusinessException
     */
    public function delClanMember($param): bool
    {
        $getUserClanMember = self::getUserClanMember($param);
        try {
            UserClanMember::where('id', $getUserClanMember->id)->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return true;
    }

    /**
     * 战队排行榜
     * @param $param
     * User: zxw
     * Date: 2021/12/08 14:56
     * @return array
     * @throws BusinessException
     */
    public function getClanRankingList($param): array
    {
        $param['limit'] = $param['limit'] ?? 15;

        $userClanQuery = self::userClanQuery($param);

        if (!empty($param['title'])) {
            $userClanQuery = $userClanQuery->where('title', 'like', '%' . $param['title'] . '%');//whereRaw("dt_1.title like '%".$param['title']."%'");
        }
        $userClan = $userClanQuery->paginate($param['limit']);
        $userClanGet = $userClanQuery->get();

        //数据拼装
        $userClan = $userClan->toArray();
        foreach ($userClan['data'] as $k => $v) {
            $userClan['data'][$k]->indexs = $k + 1;
            switch ($param['type']) {
                case 1://最高转速
                    $userClan['data'][$k]->avg_speed_max_unit = empty($v->avg_speed_max) ? 0 : $v->avg_speed_max_unit;
                    unset($userClan['data'][$k]->avg_runball_exponent, $userClan['data'][$k]->avg_exponent_molecular, $userClan['data'][$k]->avg_exponent_molecular_unit, $userClan['data'][$k]->avg_marathon, $userClan['data'][$k]->avg_marathon_asc);
                    break;
                case 2://摇跑一分钟
                    $userClan['data'][$k]->avg_exponent_molecular_unit = empty($v->avg_exponent_molecular) ? 0 : $v->avg_exponent_molecular_unit;
                    unset($userClan['data'][$k]->avg_runball_exponent, $userClan['data'][$k]->avg_speed_max, $userClan['data'][$k]->avg_speed_max_unit, $userClan['data'][$k]->avg_marathon, $userClan['data'][$k]->avg_marathon_asc);
                    break;
                case 3://摇跑指数
                    $userClan['data'][$k]->avg_runball_exponent = empty($v->avg_runball_exponent) ? 0 : (string) $v->avg_runball_exponent;
                    unset($userClan['data'][$k]->avg_exponent_molecular, $userClan['data'][$k]->avg_exponent_molecular_unit, $userClan['data'][$k]->avg_speed_max, $userClan['data'][$k]->avg_speed_max_unit, $userClan['data'][$k]->avg_marathon, $userClan['data'][$k]->avg_marathon_asc);
                    break;
                case 4://马拉松
                    $userClan['data'][$k]->avg_marathon = empty($v->avg_marathon) ? 0 : RankController::timeFormat($v->avg_marathon);
                    unset($userClan['data'][$k]->avg_exponent_molecular, $userClan['data'][$k]->avg_exponent_molecular_unit, $userClan['data'][$k]->avg_speed_max, $userClan['data'][$k]->avg_speed_max_unit, $userClan['data'][$k]->avg_runball_exponent, $userClan['data'][$k]->avg_marathon_asc);
                    break;
                default:
                    throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
            }
        }

        $myClanInfo = self::getUserClanInfo($param);
        //查询自己所在的战队
        if (isset($param['user_id']) && count($userClanGet) > 0) {
            foreach ($userClanGet as $ks => $vs) {
                if (!empty($myClanInfo->user_clan)) {
                    if ($vs->user_clan_id == $myClanInfo->user_clan_id) {
                        $myClanInfo->user_clan->clan_avatar = !empty($myClanInfo->user_clan->clan_avatar) ? StaticDataController::$_server_url . "/" . $myClanInfo->user_clan->clan_avatar : '';
                        $myClanInfo->indexs = $ks + 1;
                        switch ($param['type']) {
                            case 1://最高转速
                                $myClanInfo->avg_speed_max = $vs->avg_speed_max;
                                $myClanInfo->avg_speed_max_unit = empty($vs->avg_speed_max) ? 0 : $vs->avg_speed_max_unit;
                                break;
                            case 2://摇跑一分钟
                                $myClanInfo->avg_exponent_molecular = empty($vs->avg_exponent_molecular) ? 0 : $vs->avg_exponent_molecular;
                                $myClanInfo->avg_exponent_molecular_unit = empty($vs->avg_exponent_molecular) ? 0 : $vs->avg_exponent_molecular;
                                break;
                            case 3://摇跑指数
                                $myClanInfo->avg_runball_exponent = empty($vs->avg_runball_exponent) ? 0 : $vs->avg_runball_exponent;
                                break;
                            case 4://马拉松
                                $myClanInfo->avg_marathon = empty($vs->avg_marathon) ? 0 : RankController::timeFormat($vs->avg_marathon);
                                break;
                            default:
                                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
                        }
                    }
                } else {
                    $myClanInfo = [
                        'avg_speed_max' => 0,
                        'avg_speed_max_unit' => 0,
                        'avg_exponent_molecular' => 0,
                        'avg_exponent_molecular_unit' => 0,
                        'avg_runball_exponent' => 0,
                        'avg_marathon' => 0,
                    ];
                }

                if (!empty($param['title'])) {//存在战队名称查询时
                    foreach ($userClan['data'] as $k => $v) {
                        if ($vs->user_clan_id == $v->user_clan_id) {
                            $userClan['data'][$k]->indexs = $ks + 1;
                        }
                    }
                }
            }
        }

        return [
            'my_clan_info' => $myClanInfo ?? null,
            'user_clan_list' => data_list_format($userClan)
        ];
    }

    /**
     * 获取战队列表的SqlQuery
     * @param $param  @type请求类型
     * @return \Illuminate\Database\Query\Builder
     * @throws BusinessException
     */
    public static function userClanQuery($param): \Illuminate\Database\Query\Builder
    {
        //建立临时表
        $sql = DB::table('user_clans')
            ->leftJoin('user_clan_members', 'user_clans.id', '=', 'user_clan_members.user_clan_id')
            ->leftJoin('usr_user','usr_user.user_id','=','user_clan_members.user_id')
            ->selectRaw("
                user_clan_members.user_clan_id,
                user_clans.title,
                user_clans.address,
                user_clans.introduction,
                user_clans.telephone,
                user_clans.clan_avatar,
                IF(user_clan_members.avg_speed_max,user_clan_members.avg_speed_max,0) AS avg_speed_max,
                IF(user_clan_members.avg_runball_exponent,user_clan_members.avg_runball_exponent,0) AS avg_runball_exponent,
                IF(user_clan_members.avg_exponent_molecular,user_clan_members.avg_exponent_molecular,0) AS avg_exponent_molecular,
                IF(user_clan_members.avg_marathon,user_clan_members.avg_marathon,0) AS avg_marathon,
                IF(1000 / user_clan_members.avg_marathon,1000 / user_clan_members.avg_marathon,0) AS avg_marathon_asc
            ")
            ->whereRaw("user_clans.status=1 AND user_clan_members.status=2 AND usr_user.status=1");

        //子查询统计各战队成员数量
        $sql2 = DB::table('user_clans')
            ->leftJoin('user_clan_members', 'user_clans.id', '=', 'user_clan_members.user_clan_id')
            ->leftJoin('usr_user','usr_user.user_id','=','user_clan_members.user_id')
            ->selectRaw("COUNT(IF (user_clan_members.user_clan_id = user_clan_members.user_clan_id,TRUE,NULL))")
            ->whereRaw("user_clans. STATUS = 1 AND user_clan_members. STATUS = 2 AND usr_user.status=1 AND dt_1.user_clan_id = user_clan_members.user_clan_id");

        //需要展示的字段
        $select = "dt_1.user_clan_id,dt_1.title,dt_1.address,dt_1.introduction,dt_1.telephone,CONCAT('" . StaticDataController::$_server_url . "/',dt_1.clan_avatar) as clan_avatar,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_speed_max >0,TRUE,NULL))>=3,AVG(dt_1.avg_speed_max),0),0) AS avg_speed_max,
                    'rpm' AS avg_speed_max_unit,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_runball_exponent >0,TRUE,NULL))>=3,AVG(dt_1.avg_runball_exponent),0),2) AS avg_runball_exponent,
                    ROUND(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_exponent_molecular >0,TRUE,NULL))>=3,AVG(dt_1.avg_exponent_molecular),0)/1000,3) AS avg_exponent_molecular,
                    'km' AS avg_exponent_molecular_unit,
                    TRUNCATE(IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_marathon >0,TRUE,NULL))>=3,AVG(dt_1.avg_marathon),0),0) AS avg_marathon,
                    IF(COUNT(IF(dt_1.user_clan_id=dt_1.user_clan_id AND dt_1.avg_marathon >0,TRUE,NULL))>=3,1000/AVG(dt_1.avg_marathon),0) AS avg_marathon_asc,";

        switch ($param['type']) {
            case 1://最高转速
                //排序
                $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
                    ->selectRaw("COUNT(1)")
                    ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_speed_max < dt_2.avg_speed_max");

                //最外层查询出结果
                $userClanQuery = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
                    ->selectRaw($select . "({$sql2->toSql()}) AS count_t")
                    ->whereRaw("({$sql3->toSql()})<3")
                    ->groupByRaw("dt_1.user_clan_id")
                    ->orderByRaw("avg_speed_max DESC");
                break;
            case 2://摇跑一分钟
                //排序
                $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
                    ->selectRaw("COUNT(1)")
                    ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_exponent_molecular < dt_2.avg_exponent_molecular");

                //最外层查询出结果
                $userClanQuery = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
                    ->selectRaw($select . "({$sql2->toSql()}) AS count_t")
                    ->whereRaw("({$sql3->toSql()})<3")
                    ->groupByRaw("dt_1.user_clan_id")
                    ->orderByRaw("avg_exponent_molecular DESC");
                break;
            case 3://摇跑指数
                //排序
                $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
                    ->selectRaw("COUNT(1)")
                    ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_runball_exponent < dt_2.avg_runball_exponent");

                //最外层查询出结果
                $userClanQuery = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
                    ->selectRaw($select . "({$sql2->toSql()}) AS count_t")
                    ->whereRaw("({$sql3->toSql()})<3")
                    ->groupByRaw("dt_1.user_clan_id")
                    ->orderByRaw("avg_runball_exponent DESC");
                break;
            case 4://马拉松
                //排序
                $sql3 = DB::table(DB::raw("({$sql->toSql()}) as dt_2"))
                    ->selectRaw("COUNT(1)")
                    ->whereRaw("dt_1.user_clan_id = dt_2.user_clan_id AND dt_1.avg_marathon_asc < dt_2.avg_marathon_asc");

                //最外层查询出结果
                $userClanQuery = DB::table(DB::raw("({$sql->toSql()}) as dt_1"))
                    ->selectRaw($select . "({$sql2->toSql()}) AS count_t")
                    ->whereRaw("({$sql3->toSql()})<3")
                    ->groupByRaw("dt_1.user_clan_id")
                    ->orderByRaw("avg_marathon_asc DESC");
                break;
            default:
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }

        return $userClanQuery;
    }

    /**
     * 根据用户ID获取战队信息与成员数量
     * User: zxw
     * Date: 2021/12/10 09:56
     */
    public function getUserClanInfo($param)
    {
        $map = [];
        isset($param['user_clan_id']) ? $map['user_clan_id'] = $param['user_clan_id'] : '';
        isset($param['user_id']) ? $map['user_id'] = $param['user_id'] : '';
        $userClanMember = UserClanMember::with('user_clan')
            ->selectRaw("user_clan_id,user_id,status")
            ->where($map)
            ->first();
        if (!empty($userClanMember)) {
            $userClanMember->count = UserClanMember::where('user_clan_id', $userClanMember->user_clan_id)->where('status', 2)->count();
        }
        return $userClanMember;
    }

    /**
     * 移交队长
     * @param $param
     * User: zxw
     * Date: 2021/12/09 15:45
     * @return bool
     * @throws BusinessException
     */
    public function postHandoverClanLeader($param): bool
    {
        //数据处理
        DB::beginTransaction();
        try {
            UserClanMember::where('status', 2)->where('user_id', $param['user_id'])->update(['is_captain' => 0]);//修改队长
            UserClanMember::where('status', 2)->where('user_id', $param['member_user_id'])->update(['is_captain' => 1]);//修改为被指派队长
            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return true;
    }

    /**
     * 获取战队详情列表
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/12/10 11:32
     * @throws BusinessException
     */
    public function getClanDetailsList($param): array
    {
        $param['limit'] = $param['limit'] ?? 15;

        //获取战队成绩
        $userClanAvg = self::getAvgAchievementV2(['id' => $param['user_clan_id']]);
        //获取战队详情
        $userClanInfo = self::getUserClan(['id' => $param['user_clan_id']]);
        !empty($userClanInfo) ? $userClanInfo->clan_avatar = StaticDataController::$_server_url . "/" . $userClanInfo->clan_avatar : '';

        //根据战队ID获取战队成员user_id
        $userClanMemberId = self::getUserClanMemberPluck(['id' => $param['user_clan_id']]);
        //获取战队的各成员成绩
        $userAchievementQuery = UserAchievement::with('usr_user:user_id,user_name,sys_sex_id,user_img,address')
            ->whereHas('usr_user', function ($query) {
                $query->where('status', 1);
            })
            ->where('status', 1)
            ->whereIn('user_id', $userClanMemberId);

        switch ($param['type']) {
            case 1://最高转速
                $userAchievementCount = $userAchievementQuery->selectRaw("user_id,speed_max,speed_max_time")->orderBy('speed_max', 'desc')->get();
                $userAchievement = $userAchievementQuery->orderBy('speed_max', 'desc')->paginate($param['limit']);
                foreach ($userAchievementCount as $k => $v) {
                    if ($v->user_id == $param['user_id']) {
                        $userClanInfo->indexs = $k + 1;
                        $userClanInfo->speed_max = (int)$v->speed_max;
                        $userClanInfo->speed_max_unit = empty($userClanInfo->speed_max) ? 0 : 'rpm';
                        $userClanInfo->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                        $userClanInfo->user_name = $v->usr_user->user_name;
                        $userClanInfo->sys_sex_id = $v->usr_user->sys_sex_id;
                        $userClanInfo->address = $v->usr_user->address;
                    } else {
                        $userClanInfo->indexs = 0;
                        $userClanInfo->speed_max = 0;
                        $userClanInfo->speed_max_unit = 0;
                        $userClanInfo->user_img = 0;
                        $userClanInfo->user_name = 0;
                        $userClanInfo->sys_sex_id = 0;
                        $userClanInfo->address = 0;
                    }
                }
                foreach ($userAchievement as $k => $v) {
                    $v->speed_max = (int)$v->speed_max;
                    $v->speed_max_unit = empty($v->speed_max) ? 0 : 'rpm';
                    $v->usr_user->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                }
                break;
            case 2://摇跑一分钟
                $userAchievementCount = $userAchievementQuery->selectRaw("user_id,exponent_molecular,exponent_molecular_time")->orderBy('exponent_molecular', 'desc')->get();
                $userAchievement = $userAchievementQuery->orderBy('exponent_molecular', 'desc')->paginate($param['limit']);
                foreach ($userAchievementCount as $k => $v) {
                    if ($v->user_id == $param['user_id']) {
                        $userClanInfo->indexs = $k + 1;
                        $userClanInfo->exponent_molecular = empty($v->exponent_molecular) ? 0 : round($v->exponent_molecular / 1000, 3);
                        $userClanInfo->exponent_molecular_unit = empty($userClanInfo->exponent_molecular) ? 0 : 'km';
                        $userClanInfo->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                        $userClanInfo->user_name = $v->usr_user->user_name;
                        $userClanInfo->sys_sex_id = $v->usr_user->sys_sex_id;
                        $userClanInfo->address = $v->usr_user->address;
                    } else {
                        $userClanInfo->indexs = 0;
                        $userClanInfo->exponent_molecular = 0;
                        $userClanInfo->exponent_molecular_unit = 0;
                        $userClanInfo->user_img = 0;
                        $userClanInfo->user_name = 0;
                        $userClanInfo->sys_sex_id = 0;
                        $userClanInfo->address = 0;
                    }
                }
                foreach ($userAchievement as $k => $v) {
                    $v->exponent_molecular = empty($v->exponent_molecular) ? 0 : round($v->exponent_molecular / 1000, 3);
                    $v->exponent_molecular_unit = empty($v->exponent_molecular) ? 0 : 'km';
                    $v->usr_user->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                }
                break;
            case 3://摇跑指数
                $userAchievementCount = $userAchievementQuery->selectRaw("user_id,runball_exponent,runball_exponent_time")->orderBy('runball_exponent', 'desc')->get();
                $userAchievement = $userAchievementQuery->orderBy('runball_exponent', 'desc')->paginate($param['limit']);
                foreach ($userAchievementCount as $k => $v) {
                    if ($v->runball_exponent == $param['user_id']) {
                        $userClanInfo->indexs = $k + 1;
                        $userClanInfo->runball_exponent = $v->runball_exponent;
                        $userClanInfo->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                        $userClanInfo->user_name = $v->usr_user->user_name;
                        $userClanInfo->sys_sex_id = $v->usr_user->sys_sex_id;
                        $userClanInfo->address = $v->usr_user->address;
                    } else {
                        $userClanInfo->indexs = 0;
                        $userClanInfo->runball_exponent = 0;
                        $userClanInfo->user_img = 0;
                        $userClanInfo->user_name = 0;
                        $userClanInfo->sys_sex_id = 0;
                        $userClanInfo->address = 0;
                    }
                }
                foreach ($userAchievement as $k => $v) {
                    $v->runball_exponent = $v->runball_exponent;
                    $v->usr_user->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                }
                break;
            case 4://马拉松
                $userAchievementCount = $userAchievementQuery->selectRaw("user_id,marathon,marathon_time,10000/marathon AS marathon_asc")
                    ->orderBy('marathon_asc', 'desc')
                    ->orderBy('marathon_time', 'asc')
                    ->get();
                $userAchievement = $userAchievementQuery->selectRaw("user_id,marathon,marathon_time,10000/marathon AS marathon_asc")
                    ->orderBy('marathon_asc', 'desc')
                    ->orderBy('marathon_time', 'asc')
                    ->paginate($param['limit']);
                foreach ($userAchievementCount as $k => $v) {
                    if ($v->runball_exponent == $param['user_id']) {
                        $userClanInfo->indexs = $k + 1;
                        $userClanInfo->marathon = RankController::timeFormat($v->marathon);
                        $userClanInfo->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                        $userClanInfo->user_name = $v->usr_user->user_name;
                        $userClanInfo->sys_sex_id = $v->usr_user->sys_sex_id;
                        $userClanInfo->address = $v->usr_user->address;
                    } else {
                        $userClanInfo->indexs = 0;
                        $userClanInfo->marathon = 0;
                        $userClanInfo->user_img = 0;
                        $userClanInfo->user_name = 0;
                        $userClanInfo->sys_sex_id = 0;
                        $userClanInfo->address = 0;
                    }
                }
                foreach ($userAchievement as $k => $v) {
                    $v->marathons = (string)(RankController::timeFormat($v->marathon));
                    unset($v->marathon);
                    $v->usr_user->user_img = !empty($v->usr_user->user_img) ? StaticDataController::$_server_url . "/" . $v->usr_user->user_img : '';
                }
                break;
            default:
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }

        return [
            'user_clan_avg' => $userClanAvg,
            'user_clan_info' => $userClanInfo,
            'user_clan_list' => data_list_format($userAchievement)
        ];
    }

    /**
     * 获取他人个人资料
     * @param $param
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/12/10 16:49
     */
    public function getUserOthersInfo($param)
    {
        $getUserOthersInfo = UsrUser::selectRaw("user_id,user_name,user_img,birthday,user_height,user_weight,address,self_description,created_time,sys_sex_id,wechart_id,photo_text")
            ->withCount(['user_clan_members as clan_count' => function ($query) {
                $query->where('status', 2);
            }])
            ->with([
                'user_achievement_one:user_id,speed_max,speed_max_time,exponent_molecular,exponent_molecular_time,runball_exponent,runball_exponent_time,marathon,marathon_time',
                'user_clan_members' => function ($query) {
                    $query->with('user_clan:id,title,clan_avatar,address')
                        ->selectRaw("user_clan_id,user_id,is_captain,status");
                }
            ])
            ->where('user_id', $param['user_id'])
            ->withCasts([
                "created_time" => 'datetime:Y-m-d'
            ])
            ->first();

        $getUserOthersInfo->user_img = !empty($getUserOthersInfo->user_img) ? StaticDataController::$_server_url . "/" . $getUserOthersInfo->user_img : '';
        if (isset($getUserOthersInfo->user_clan_members->user_clan)) {
            $getUserOthersInfo->user_clan_members->title = $getUserOthersInfo->user_clan_members->user_clan->title;
            $getUserOthersInfo->user_clan_members->clan_avatar = !empty($getUserOthersInfo->user_clan_members->user_clan->clan_avatar) ? StaticDataController::$_server_url . "/" . $getUserOthersInfo->user_clan_members->user_clan->clan_avatar : '';
            $getUserOthersInfo->user_clan_members->address = $getUserOthersInfo->user_clan_members->user_clan->address;
            $getUserOthersInfo->user_clan_members->clan_count = $getUserOthersInfo->clan_count;
            unset($getUserOthersInfo->user_clan_members->user_clan, $getUserOthersInfo->clan_count);
        }

        $getUserOthersInfo->age = empty($getUserOthersInfo->birthday) ? 0 : (int)(date('Y') - explode('-', $getUserOthersInfo->birthday)[0]);
        empty($getUserOthersInfo->user_clan_members) ? $getUserOthersInfo->user_clan_members = null : '';

        return $getUserOthersInfo;
    }

    /**
     * 根据用户id获取用户成就
     * @param $param
     * User: zxw
     * Date: 2021/12/15 17:40
     */
    public function getUserAchievement($param)
    {
        return UserAchievement::where('user_id', $param['user_id'])->first();
    }

}
