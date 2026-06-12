<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\MatchsAward;
use App\Models\MatchsUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 赛事用户奖章Service
 */
class MatchAwardService
{
    /**
     * 根据赛事ID获取用户例赛奖章列表
     * @param $param
     * @return LengthAwarePaginator
     * User: zxw
     * Date: 2022/4/2 14:28
     */
    public function getMatchAward($param): LengthAwarePaginator
    {
        $param['limit'] = $param['limit'] ?? 15;

        return MatchsAward::with('usr_user:user_id,user_name')
            ->select('id', 'user_id', 'sys_match_id', 'title', 'title_en', DB::raw("CONCAT('".StaticDataController::$_server_url . "/',award_img) as award_img"), DB::raw("CONCAT('".StaticDataController::$_server_url . "/',back_img) as back_img"))
            ->where('sys_match_id',$param['sys_match_id'])
            ->paginate($param['limit']);
    }

    /**
     * 新增用户例赛奖章
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/4/2 14:30
     * @throws BusinessException
     */
    public function addMatchAward($param)
    {
        try {
            $addMatchAward = MatchsAward::create($param);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $addMatchAward;
    }

    /**
     * 删除用户例赛奖章
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/4/2 15:19
     * @throws BusinessException
     */
    public function delMatchAward($param)
    {
        try {
            $postMatchAward = MatchsAward::where('id',$param['id'])
                ->where('sys_match_id',$param['sys_match_id'])
                ->delete();
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return $postMatchAward;
    }

    /**
     * 根据赛事ID获取用户例赛赛点列表
     * @param $param
     * @return LengthAwarePaginator
     * User: zxw
     * Date: 2022/4/2 14:55
     */
    public function getMatchPointList($param): LengthAwarePaginator
    {
        $param['limit'] = $param['limit'] ?? 15;

        return MatchsUser::with('usr_user_one:user_id,user_name')
            ->select('matchs_user_id','sys_sys_match_id as sys_match_id','user_id','s_match_point')
            ->where('status',1)
            ->where('is_join',1)
            ->where('sys_sys_match_id',$param['sys_match_id'])
            ->paginate($param['limit']);
    }

    /**
     * 新增用户例赛赛点/清零赛点/删除报名数据
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/4/2 14:58
     * @throws BusinessException
     */
    public function postMatchPoint($param)
    {
        $matchsUser = MatchsUser::where(['status' => 1,'sys_sys_match_id' => $param['sys_match_id'],'user_id' => $param['user_id']])->first();
        if (empty($matchsUser)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));

        try {
            isset($param['s_match_point']) ? $matchsUser->s_match_point = $param['s_match_point'] : '';
            isset($param['status']) ? $matchsUser->status = -1 : '';//存在status 表示需要删除
            $matchsUser->save();
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $matchsUser;
    }



}
