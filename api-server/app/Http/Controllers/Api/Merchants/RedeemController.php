<?php

namespace App\Http\Controllers\Api\Merchants;

use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\SMSController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Members\Redeem\RedeemGetBrandRedeemLogDetailRequest;
use App\Http\Requests\Api\Members\Redeem\RedeemGetUsrUserInfoRequest;
use App\Http\Requests\Api\Merchants\Redeem\GetRedeemBillRequest;
use App\Http\Requests\Api\Merchants\Redeem\RedeemPostLoginPhoneRequest;
use App\Http\Requests\Api\Merchants\Redeem\RedeemPostRedeemRequest;
use App\Services\LoginService;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * H5商户端-积分兑换
 * Class RedeemController
 * @package App\Http\Controllers\Api\Merchants
 * User: zxw
 * Date: 2021/9/26 13:46
 */
class RedeemController extends Controller
{
    /**
     * 品牌分店短息登录
     * @param RedeemPostLoginPhoneRequest $request
     * @param LoginService $loginService
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 16:55
     */
    public function postLoginPhone(RedeemPostLoginPhoneRequest $request, LoginService $loginService): JsonResponse
    {
        $data = $request->all();
        $_is_check = SMSController::checkNumber($data["phone"], $data["number"], "merchant_login_phone");
        if (trim($data["phone"]) == "17328767742") {   //app审核专用账户，请勿改动或删除
            $_is_check = true;
        }
        if (!$_is_check) {
            return $this->error(ErrorCode::SEVER_ERROR, trans('messages.sms_not_found'));
        }
        $brandInfo = $loginService->getBrandUserInfo($data);
        $list = [
            'token' => $brandInfo->access_token,
            'nickname' => $brandInfo->nickname,
            'user_img' => $brandInfo->user_img,
            'brand_logo' => $brandInfo->brand->brand_logo ?? null,
            'brand_name' => $brandInfo->brand->brand_name ?? null,
            'shop_name' => $brandInfo->brand_shop->shop_name ?? null,
            'shop_address' => $brandInfo->brand_shop->shop_name ?? null,
        ];
        return $this->success($brandInfo);
    }

    /**
     * 获取品牌分店信息
     * @param Request $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/27 15:53
     */
    public function getBrandShopInfo(Request $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $data['token'] = $request->header('token');
        Redis::select(1);
        $_brand_user = json_decode(Redis::hget('brand_user', $data['token']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (empty($_brand_user)) return $this->error(ErrorCode::SEVER_ERROR, trans('messages.user_not_found'));

        $data['id'] = $_brand_user['id'];
        $list = $service->getBrandUserInfo($data);

        return $this->success($list);
    }

    /**
     * 根据ID获取用户头像和昵称
     * @param RedeemGetUsrUserInfoRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/24 10:55
     */
    public function getUsrUserInfo(RedeemGetUsrUserInfoRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        //解密获取user_id
        if (empty(url_decrypt($data['url_token']))) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.QR_code_has_expired'));
        $urlToken = url_decrypt($data['url_token']);
        $data['user_id'] = substr($urlToken, strrpos($urlToken, '=') + 1);//截取被扫用户ID

        $data['token'] = $request->header('token');
        Redis::select(1);
        $list = json_decode(Redis::hget('brand_user', $data['token']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (empty($list))
            return $this->error(ErrorCode::SEVER_ERROR, trans('messages.user_not_found'));

        $list = $service->getUsrUserInfo($data['user_id']);
        return $this->success($list);
    }

    /**
     * 商户端-积分兑换
     * @param RedeemPostRedeemRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException User: zxw
     * Date: 2021/9/23 18:40
     */
    public function postRedeem(RedeemPostRedeemRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        if (empty(url_decrypt($data['url_token']))) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.QR_code_has_expired'));

        $_user_token = $request->header("token");
        Redis::select(1);
        $_brand_user = json_decode(Redis::hget("brand_user", $_user_token), true);
        if (empty($_brand_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));
        $urlToken = url_decrypt($data['url_token']);

        $data['user_id'] = substr($urlToken, strrpos($urlToken, '=') + 1);//截取被扫用户ID
        $data['brand_id'] = $_brand_user['brand_id'];
        $data['brand_shop_id'] = $_brand_user['brand_shop_id'];
        $data['brand_user_id'] = $_brand_user['id'];
        $data['operate_user_id'] = $_brand_user['user_id'];
        $data['order_no'] = get_order_number(SettingMessage::SET_MERCHANT_ORDER_NO_PREFIX);
        $data['type'] = 1;

        $list = $service->postBrandRedeemLog($data);
        return $this->success($list);
    }

    /**
     * 获取积分兑换账单 type：1入账单 2出账单
     * @param GetRedeemBillRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException User: zxw
     * Date: 2021/9/22 10:28
     */
    public function getRedeemBill(GetRedeemBillRequest $request, RedeemService $service): JsonResponse
    {
        $_user_token = $request->header("token");
        Redis::select(1);
        $_brand_user = json_decode(Redis::hget("brand_user", $_user_token), true);
        if (empty($_brand_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['brand_id'] = $_brand_user['brand_id'];
        $data['brand_shop_id'] = $_brand_user['brand_shop_id'];
        $list = $service->getBrandRedeemLogList($data);
        return $this->success(data_list_format($list));
    }

    /**
     * 根据ID获取账单详情
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
        $_brand_user = json_decode(Redis::hget("brand_user", $_user_token), true);
        if (empty($_brand_user)) throw new BusinessException(ErrorCode::ERROR_TOKEN_INVALID, trans('messages.user_not_found'));

        $data = $request->all();
        $data['brand_id'] = $_brand_user['brand_id'];
        $data['brand_shop_id'] = $_brand_user['brand_shop_id'];
        $list = $service->getBrandRedeemLogDetail($data);
        return $this->success($list);
    }

}
