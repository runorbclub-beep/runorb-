<?php

namespace App\Http\Controllers\Api\Clans;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Requests\Api\Clans\ClanEditUserClanRequest;
use App\Http\Requests\Api\Clans\ClanGetClanDetailsListRequest;
use App\Http\Requests\Api\Clans\ClanGetClanMemberListRequest;
use App\Http\Requests\Api\Clans\ClanGetClanNewMemberListRequest;
use App\Http\Requests\Api\Clans\ClanGetClanRankingListRequest;
use App\Http\Requests\Api\Clans\ClanGetUserOthersInfoRequest;
use App\Http\Requests\Api\Clans\ClanPostApplyJoinClanRequest;
use App\Http\Requests\Api\Clans\ClanPostApplyTeamRequest;
use App\Http\Requests\Api\Clans\ClanPostHandoverClanLeaderRequest;
use App\Http\Requests\Api\Clans\ClanPostReviewApplyClanMemberRequest;
use App\Http\Requests\Api\Clans\ClanPostUnJoinClanRequest;
use App\Models\UserClanMember;
use App\Services\ClanService;
use App\Services\CrontabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * 战队
 * Class ClanController
 * @package App\Http\Controllers\Api\Clan
 * User: zxw
 * Date: 2021/12/06 11:37
 */
class ClanController extends Controller
{
    /**
     * 获取战队列表
     * @param Request $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/06 17:22
     * @throws BusinessException
     */
    public function getClanList(Request $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getClanList($data);
        foreach ($list['list'] as $k => $v) {
            $list['list'][$k]['clan_avatar'] = !empty($v['clan_avatar']) ? StaticDataController::$_server_url . "/" . $v['clan_avatar'] : '';
        }
        return $this->success($list);
    }

    /**
     * 申请战队
     * @param ClanPostApplyTeamRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/06 16:16
     * @throws BusinessException
     */
    public function postApplyTeam(ClanPostApplyTeamRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        //验证是否重名
        $clans = $service->getUserClan(['title' => $data['title']]);
        if (!empty($clans)) $this->error(ErrorCode::SEVER_ERROR, trans('messages.title_already_exists_error'));

        //获取用户成绩
        $userAchievement = $service->getUserAchievement(['user_id' => $data['user_id']]);

        try {
            DB::transaction(function () use ($userAchievement, $data, $service) {
                $userClan = $service->addUserClan($data);
                $service->addUserClanMember([
                    'user_clan_id' => $userClan->id,
                    'user_id' => $data['user_id'],
                    'is_captain' => 1,
                    'remark' => $data['remark'],
                    'avg_speed_max' => !empty($userAchievement->speed_max) ? $userAchievement->speed_max : 0,
                    'avg_exponent_molecular' => !empty($userAchievement->exponent_molecular) ? $userAchievement->exponent_molecular : 0,
                    'avg_runball_exponent' => !empty($userAchievement->runball_exponent) ? $userAchievement->runball_exponent : 0,
                    'avg_marathon' => !empty($userAchievement->marathon) ? $userAchievement->marathon : 0,
                ]);
            },5);
        } catch (\Throwable $ex) {
            Log::info('postApplyTeam=========：'.json_encode($ex));
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $this->success(true);
    }

    /**
     * 取消申请战队
     * @param ClanPostApplyJoinClanRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/08 18:46
     * @throws BusinessException
     */
    public function withdrawClan(ClanPostApplyJoinClanRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->withdrawClan($data);

        return $this->success($list);
    }

    /**
     * 编辑战队
     * @param ClanEditUserClanRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/06 16:19
     * @throws BusinessException
     */
    public function editUserClan(ClanEditUserClanRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->editUserClan($data);
        return $this->success($list);
    }

    /**
     * 获取战队详情
     * @param Request $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/07 15:29
     * @throws BusinessException
     */
    public function getUserClanInfo(Request $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        //获取战队信息
        $list = $service->getUserClan($data);
        if (empty($list)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.team_does_not_exist_error'));

        $list['avg_achievement'] = [
            'avg_speed_max' => 0,
            'avg_speed_max_unit' => 0,
            'avg_exponent_molecular' => 0,
            'avg_exponent_molecular_unit' => 0,
            'avg_runball_exponent' => 0,
            'avg_marathon' => 0,
        ];

        //获取队长信息
        $list->captain_info = $service->getUserClanMember(['user_clan_id' => $data['id'],'is_captain' => 1]);
        if (empty($list->captain_info)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.request_parameter_error'));
        $list->captain_info = $list->captain_info->usr_user;
        $list->captain_info->user_img = !empty($list->captain_info->user_img) ? StaticDataController::$_server_url . "/" . $list->captain_info->user_img : '';
        $list->clan_avatar = !empty($list->clan_avatar) ?StaticDataController::$_server_url . "/" . $list->clan_avatar : '';
        $list->captain_info->is_captain = 1;
        if ($list->captain_info->user_id == $_usr_user['user_id']){
            //增加对是队长的账号返回待审核人数量
            $list->review_count = UserClanMember::where('user_clan_id',$data['id'])->where('status',1)->count();
        }else{
            $list->review_count = 0;
        }

        //查询自己所在战队状态 'user_clan_id' => $data['id'],
        $userStatus = $service->getUserClanMember(['user_clan_id' => $data['id'],'user_id' => $_usr_user['user_id']]);
        if (!empty($userStatus)){
            $list->user_status = $userStatus->status;//1待审核 2已加入
            $list->is_user_captain = $userStatus->is_captain;//0否  1是
        }else{
            $list->user_status = 3;//未加入
            $list->is_user_captain = 2;//尚未加入战队
        }

        //追加审核通过后需要展示的数据
        if ($list->status == 1){
            Redis::select(1);
            if (Redis::exists('getAvgAchievementV2_'.$data['id'])){
                $list->avg_achievement = json_decode(Redis::get('getAvgAchievementV2_'.$data['id']),true);
            }else{
                $list->avg_achievement = $service->getAvgAchievementV2($list);
                $list->user_count = $list->avg_achievement['user_count'];

                //设置缓存
                Redis::setex('getAvgAchievementV2_'.$data['id'],60,json_encode($list->avg_achievement,true));
            }

        }
        return $this->success($list);
    }

    /**
     * 获取战队详情==不含战队成绩
     * @param Request $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/3/23 15:27
     * @throws BusinessException
     */
    public function getUserClanInfoV2(Request $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        //获取战队信息
        $list = $service->getUserClan($data);
        if (empty($list)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.team_does_not_exist_error'));

        //获取队长信息
        $list->captain_info = $service->getUserClanMember(['user_clan_id' => $data['id'],'is_captain' => 1]);
        if (empty($list->captain_info)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.request_parameter_error'));
        $list->captain_info = $list->captain_info->usr_user;
        $list->captain_info->user_img = !empty($list->captain_info->user_img) ? StaticDataController::$_server_url . "/" . $list->captain_info->user_img : '';
        $list->clan_avatar = !empty($list->clan_avatar) ?StaticDataController::$_server_url . "/" . $list->clan_avatar : '';
        $list->captain_info->is_captain = 1;
        if ($list->captain_info->user_id == $_usr_user['user_id']){
            //增加对是队长的账号返回待审核人数量
            $list->review_count = UserClanMember::where('user_clan_id',$data['id'])->where('status',1)->count();
        }else{
            $list->review_count = 0;
        }

        //查询自己所在战队状态 'user_clan_id' => $data['id'],
        $userStatus = $service->getUserClanMember(['user_clan_id' => $data['id'],'user_id' => $_usr_user['user_id']]);
        if (!empty($userStatus)){
            $list->user_status = $userStatus->status;//1待审核 2已加入
            $list->is_user_captain = $userStatus->is_captain;//0否  1是
        }else{
            $list->user_status = 3;//未加入
            $list->is_user_captain = 2;//尚未加入战队
        }

        return $this->success($list);
    }

    public function getUserClanInfoAvgAchievement(Request $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        Redis::select(1);
        if (Redis::exists('getAvgAchievementV2_'.$data['id'])){
            $list = json_decode(Redis::get('getAvgAchievementV2_'.$data['id']),true);
        }else{
            //获取战队信息
            $getUserClan = $service->getUserClan($data);
            if (empty($getUserClan)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.team_does_not_exist_error'));

            $list['avg_achievement'] = [
                'avg_speed_max' => 0,
                'avg_speed_max_unit' => 0,
                'avg_exponent_molecular' => 0,
                'avg_exponent_molecular_unit' => 0,
                'avg_runball_exponent' => 0,
                'avg_marathon' => 0,
                'indexs_speed_max' => 0,
                'indexs_exponent_molecular' => 0,
                'indexs_runball_exponent' => 0,
                'indexs_marathon' => 0,
                'user_count' => 0,
            ];

            //追加审核通过后需要展示的数据
            if ($getUserClan->status == 1){
                $list = $service->getAvgAchievementV2($data);
            }


            //设置缓存
            Redis::setex('getAvgAchievementV2_'.$data['id'],60,json_encode($list,true));
        }

        return $this->success($list);
    }

    /**
     * 申请加入战队
     * @param ClanPostApplyJoinClanRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/07 10:57
     * @throws BusinessException
     */
    public function postApplyJoinClan(ClanPostApplyJoinClanRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        //验证战队是否存在
//        $getUserClan = $service->getUserClan(['id' => $data['user_clan_id']]);
//        if (empty($getUserClan)){
//            return $this->error(ErrorCode::SEVER_ERROR,trans('messages.team_does_not_exist_error'));
//        }

        //获取用户成绩
        $userAchievement = $service->getUserAchievement(['user_id' => $data['user_id']]);
        $data['avg_speed_max'] = !empty($userAchievement->speed_max) ? $userAchievement->speed_max : 0;
        $data['avg_exponent_molecular'] = !empty($userAchievement->exponent_molecular) ? $userAchievement->exponent_molecular : 0;
        $data['avg_runball_exponent'] = !empty($userAchievement->runball_exponent) ? $userAchievement->runball_exponent : 0;
        $data['avg_marathon'] = !empty($userAchievement->marathon) ? $userAchievement->marathon : 0;

        $service->addUserClanMember($data);
        return $this->success(true);
    }

    /**
     * 取消申请加入战队
     * @param ClanPostUnJoinClanRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/07 11:04
     * @throws BusinessException
     */
    public function postUnJoinClan(ClanPostUnJoinClanRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        $service->delUserClanMember($data);
        return $this->success(true);
    }

    /**
     * 根据战队ID获取战队成员
     * @param ClanGetClanMemberListRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/07 18:03
     * @throws BusinessException
     */
    public function getClanMemberList(ClanGetClanMemberListRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getClanMemberList($data);
        foreach ($list['list'] as $k => $v) {
            $v['usr_user']['remark'] = $v['remark'];
            $list['list'][$k] = $v['usr_user'];
            !empty($list['list'][$k]['user_img']) ? $list['list'][$k]['user_img'] = StaticDataController::$_server_url . "/" . $list['list'][$k]['user_img'] : '';
        }
        return $this->success($list);
    }

    /**
     * 队长审核战队成员
     * @param ClanPostReviewApplyClanMemberRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/07 19:01
     * @throws BusinessException
     */
    public function postReviewApplyClanMember(ClanPostReviewApplyClanMemberRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];
        $data['is_captain'] = 1;
        $data['status'] = $data['status'] ?? 0;
        $data['reply_remark'] = $data['reply_remark'] ?? '';

        //判断当前操作用户是否为队长
        $getUserClanMember = $service->getUserClanMember($data);
        if (empty($getUserClanMember)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.you_are_not_the_captain_error'));

        if (!empty($data['status'])){//审核
            $service->editClanMember([
                'user_clan_id' => $data['user_clan_id'],
                'user_id' => $data['member_user_id'],
                'status' => $data['status'],
                'reply_remark' => $data['reply_remark']
            ]);
        }else{//拒绝，直接删除申请数据
            $service->delClanMember([
                'user_clan_id' => $data['user_clan_id'],
                'user_id' => $data['member_user_id'],
                'status' => $data['status'],
                'reply_remark' => $data['reply_remark']
            ]);
        }

        return $this->success(true);
    }

    /**
     * 移交队长
     * @param ClanPostHandoverClanLeaderRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/08 18:55
     * @throws BusinessException
     */
    public function postHandoverClanLeader(ClanPostHandoverClanLeaderRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];
        $data['is_captain'] = 1;

        //判断当前操作用户是否为队长
        $getUserClanMember = $service->getUserClanMember($data);
        if (empty($getUserClanMember)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.you_are_not_the_captain_error'));

        //移交队长
        $service->postHandoverClanLeader($data);
        return $this->success(true);
    }

    /**
     * 获取战队排行榜
     * @param ClanGetClanRankingListRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/08 14:27
     * @throws BusinessException
     */
    public function getClanRankingList(ClanGetClanRankingListRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->getClanRankingList($data);

        return $this->success($list);
    }

    /**
     * 获取战队详情列表
     * @param ClanGetClanDetailsListRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/08 18:53
     * @throws BusinessException
     */
    public function getClanDetailsList(ClanGetClanDetailsListRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->getClanDetailsList($data);
        return $this->success($list);
    }

    /**
     * 获取他人个人资料
     * @param ClanGetUserOthersInfoRequest $request
     * @param ClanService $service
     * User: zxw
     * Date: 2021/12/10 16:26
     * @return JsonResponse
     * @throws BusinessException
     */
    public function getUserOthersInfo(ClanGetUserOthersInfoRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $list = $service->getUserOthersInfo(['user_id' => $data['member_user_id']]);
        return $this->success($list);
    }

    /**
     * 获取战队模块介绍信息
     * User: zxw
     * Date: 2021/12/15 18:20
     */
    public function getClansIntroduction(Request $request): JsonResponse
    {
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        App::setLocale($_language);//根据传参设置多语言

        $list = [
            'clans_introduction' => trans('messages.clans_introduction'),//战队介绍
            'captains_rights' => trans('messages.captains_rights'),//队长权益
            'create_condition' => trans('messages.create_condition'),//创建条件
            'casually_shake' => trans('messages.casually_shake'),//随手摇--随手摇规则说明
            'shake_race' => trans('messages.shake_race'),//摇跑赛事--赛事规则说明
        ];
        return $this->success($list);
    }

    /**
     * 删除战队成员
     * @param ClanPostHandoverClanLeaderRequest $request
     * @param ClanService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/15 18:36
     * @throws BusinessException
     */
    public function delUserClanMember(ClanPostHandoverClanLeaderRequest $request, ClanService $service): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $data['user_id'] = $_usr_user['user_id'];
        $data['is_captain'] = 1;

        if ($data['user_id'] !== $data['member_user_id']){//相等是退出战队接口，否则是队长删除战队成员接口
            //判断当前操作用户是否为队长
            $getUserClanMember = $service->getUserClanMember($data);
            if (empty($getUserClanMember)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.you_are_not_the_captain_error'));
        }

        $list = $service->delUserClanMember(['user_id' => $data['member_user_id']]);
        return $this->success($list);
    }

}
