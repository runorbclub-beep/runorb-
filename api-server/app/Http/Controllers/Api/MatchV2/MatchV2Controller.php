<?php

namespace App\Http\Controllers\Api\MatchV2;

use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Requests\Api\MatchV2\MatchV2GetMatchlistRequest;
use App\Http\Requests\Api\MatchV2\MatchV2GetSysMatchDetailsRequest;
use App\Services\MatchV2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 赛事改版
 * Class MatchV2Controller
 * @package App\Http\Controllers\Api\MatchV2
 * User: zxw
 * Date: 2021/10/12 14:40
 */
class MatchV2Controller extends Controller
{
    /**
     * 根据状态获取赛事列表 type：1未开始  2进行中 3已结束
     * @param MatchV2GetMatchlistRequest $request
     * @param MatchV2Service $service
     * @param MatchController $matchController
     * @return JsonResponse
     * @throws BusinessException User: zxw
     * Date: 2021/10/12 15:35
     */
    public function getMatchList(MatchV2GetMatchlistRequest $request, MatchV2Service $service, MatchController $matchController): JsonResponse
    {
        $data = $request->all();
        $data['token'] = $request->header('token');
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $data['token'] == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        //获取数据
        $list = $service->getSysMatchList($data);
        //数据处理，组装用户报名状态数据
        foreach ($list as $k=>$v){
            $v->user_join_status = $matchController->UserIsJoinMatch($v->sys_match_id,$v->is_group,$data['token']);
        }
        return $this->success(data_list_format($list));
    }

    /**
     * 根据赛事ID获取赛事详情 type：1未开始  2进行中 3已结束
     * @param MatchV2GetSysMatchDetailsRequest $request
     * @param MatchV2Service $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/10/13 10:52
     */
    public function getSysMatchDetails(MatchV2GetSysMatchDetailsRequest $request, MatchV2Service $service): JsonResponse
    {
        $data = $request->all();
        $data['token'] = $request->header('token');
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $data['token'] == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        //获取数据
        $list = $service->getSysMatchDetails($data);

        return $this->success($list);
    }

    /**
     * 获取报名团队标签
     * @param Request $request
     * @return string[]
     * User: zxw
     * Date: 2021/10/13 17:18
     */
    public function getSignUpTeamTag(Request $request): array
    {
        return SettingMessage::TEAM_TAG_ZH_CN;
    }

    /**
     * 关联赛事插入假数据
     * @param Request $request
     * @param MatchV2Service $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/10/27 9:27
     */
    public function sysMatchInsertFakeData(Request $request, MatchV2Service $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->sysMatchInsertFakeData($data);
        return $this->success($list);
    }

}
