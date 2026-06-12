<?php

namespace App\Http\Controllers\Api\Clans;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Requests\Api\Clans\ClanPhotoAddPhotoRequest;
use App\Services\ClanService;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * 战队相片管理
 */
class ClanPhotoController extends Controller
{
    /**
     * 新增、批量新增相片
     * @param ClanPhotoAddPhotoRequest $request
     * @param PhotoService $photoService
     * @param ClanService $clanService
     * @return JsonResponse
     * @throws BusinessException
     */
    public function addPhoto(ClanPhotoAddPhotoRequest $request, PhotoService $photoService, ClanService $clanService): JsonResponse
    {
        $data = $request->all();
        $map = [];
        $_user_token = $request->header("token");
        $data['language'] = $request->header('language') !== null ? $request->header('language') : 'zh-CN';
        if( $_user_token == null ){
            return $this->error(0,LanguageController::getLanguage($data['language'],"lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        //根据战队ID获取战队信息
        $getUserClanMember = $clanService->getUserClanMember([
            'user_clan_id' => $data['user_clan_id'],
            'user_id' => $_usr_user['user_id'],
            'is_captain' => 1,
            'status' => 2
        ]);
        //判断当前操作用户是否为队长
        if (empty($getUserClanMember)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.you_are_not_the_captain_error'));
        //组装数据
        foreach ($data['photo_url'] as $k => $v) {
            $map[] = [
                'type' => $data['type'],
                'user_clan_id' => $data['user_clan_id'],
                'user_id' => $_usr_user['user_id'],
                'photo_name' => $data['photo_name'] ?? null,
                'photo_url' => $v['photo_url'],
                'photo_location' => $data['photo_location'] ?? null,
                'is_top' => $data['is_top'] ?? 0,
                'sort' => $data['sort'] ?? 0,
                'created_at' => Carbon::now()->toDateTimeString()
            ];
        }
        //写入数据库
        $list = $photoService->addPhoto($map);
        return $this->success($list);
    }
}
