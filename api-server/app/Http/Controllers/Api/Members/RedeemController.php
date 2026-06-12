<?php

namespace App\Http\Controllers\Api\Members;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Members\Redeem\RedeemGetBrandRedeemLogDetailRequest;
use App\Http\Requests\Api\Members\Redeem\RedeemGetQRCodeInfoRequest;
use App\Http\Requests\Api\Members\Redeem\RedeemGetRedeemBillRequest;
use App\Http\Requests\Api\Members\Redeem\RedeemGetSourceOfPointsRequest;
use App\Http\Requests\Api\Members\Redeem\RedeemGetUserIntegralLogDetailRequest;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

/**
 * 管理后台--积分兑换
 * Class RedeemController
 * @package App\Http\Controllers\Api\Members
 * User: zxw
 * Date: 2021/9/14 17:21
 */
class RedeemController extends Controller
{
    /**
     * 获取积分二维码信息
     * @param RedeemGetQRCodeInfoRequest $request
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/14 13:29
     */
    public function getQRCodeInfo(RedeemGetQRCodeInfoRequest $request): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        //组装URL（有实效限制,默认3分钟后失效）
        $qrcodeUrl = get_request_agreement() . $request->server('HTTP_HOST') . '/api/merchants/redeem/postRedeem?url_token=' . url_encrypt('user_id=' . $_usr_user['user_id']);
        $list = [
            'integral' => $_usr_user['integral'] ?? 0,
            'qrcode_url' => $qrcodeUrl
        ];

        return $this->success($list);
    }

    /**
     * 获取用户积分来源
     * @param RedeemGetSourceOfPointsRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException User: zxw
     * Date: 2021/9/24 8:57
     */
    public function getSourceOfPoints(RedeemGetSourceOfPointsRequest $request, RedeemService $service): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $service->getUserIntegralLogList($data);

        return $this->success(data_list_format($list));
    }

    /**
     * 根据ID获取用户积分来源详情
     * @param RedeemGetUserIntegralLogDetailRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException User: zxw
     * Date: 2021/9/22 14:14
     */
    public function getUserIntegralLogDetail(RedeemGetUserIntegralLogDetailRequest $request, RedeemService $service): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $service->getUserIntegralLogDetail($data);
        return $this->success($list);
    }

    /**
     * 获取用户积分兑换记录
     * @param RedeemGetRedeemBillRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 10:28
     */
    public function getRedeemBill(RedeemGetRedeemBillRequest $request, RedeemService $service): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $service->getBrandRedeemLogList($data);

        return $this->success(data_list_format($list));
    }

    /**
     * 根据ID获取用户积分兑换详情
     * @param RedeemGetBrandRedeemLogDetailRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 14:14
     */
    public function getBrandRedeemLogDetail(RedeemGetBrandRedeemLogDetailRequest $request, RedeemService $service): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        if (empty($_usr_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['user_id'] = $_usr_user['user_id'];
        $list = $service->getBrandRedeemLogDetail($data);
        return $this->success($list);
    }


}
