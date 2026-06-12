<?php

namespace App\Http\Controllers\Api\Users;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\UserPayAccountAddUserPayAccountRequest;
use App\Http\Requests\Api\Users\UserPayAccountEidtUserPayAccountRequest;
use App\Models\SysSetting;
use App\Models\WithdrawOrder;
use App\Services\UserPayAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 用户支付
 */
class UserPayAccountController extends Controller
{
    /**
     * 获取平台支付渠道列表
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 9:59
     */
    public function getPayChannel(Request $request, UserPayAccountService $userPayAccountService): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];
        $list = $userPayAccountService->getPayChannel($data);
        return $this->success($list);
    }

    /**
     * 新增用户支付渠道
     * @param UserPayAccountAddUserPayAccountRequest $request
     * @param UserPayAccountService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 11:44
     * @throws BusinessException
     */
    public function addUserPayAccount(UserPayAccountAddUserPayAccountRequest $request, UserPayAccountService $service): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->addUserPayAccount($data);

        return $this->success($list);
    }

    /**
     * 编辑用户支付渠道
     * @param UserPayAccountEidtUserPayAccountRequest $request
     * @param UserPayAccountService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 12:01
     * @throws BusinessException
     */
    public function editUserPayAccount(UserPayAccountEidtUserPayAccountRequest $request, UserPayAccountService $service): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->editUserPayAccount($data);

        return $this->success($list);
    }

    /**
     * 删除用户支付渠道
     * @param Request $request
     * @param UserPayAccountService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 13:48
     * @throws BusinessException
     */
    public function delUserPayAccount(Request $request, UserPayAccountService $service): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        if (empty($data['id']) || empty($data['user_id']) || empty($data['pay_channel_id'])) return $this->error(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));

        $list = $service->delUserPayAccount($data);

        return $this->success($list);
    }

    /**
     * 用户获取用户支付渠道列表
     * @param Request $request
     * @param UserPayAccountService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 13:55
     */
    public function getUserPayAccountList(Request $request, UserPayAccountService $service): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        $list = $service->getUserPayAccountList($data);

        return $this->success($list);
    }

    /**
     * 获取积分详情
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/26 9:18
     */
    public function getPointsDetails(Request $request, UserPayAccountService $service): JsonResponse
    {
        $data = $request->all();
        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        $sysSetting = SysSetting::where('sys_setting_id', 48216651664986112)->first();
        if (!empty($sysSetting['points_withdrawal_rule'])) {
            $sysSetting['points_withdrawal_rule'] = json_decode($sysSetting['points_withdrawal_rule'], true);
        }

        //今日已提现额度
        $integralSum = WithdrawOrder::where('user_id', $data['user_id'])
            ->whereIn('status', [10, 11])
            ->whereDate('updated_at', date('Y-m-d'))
            ->sum('integral');
        $integralSum = (int)$integralSum / $sysSetting['integral_exchange_ratio'];

        //可提现总金额
        $can_withdraw_amount = (int)$_usr_user['integral'] / $sysSetting['integral_exchange_ratio'];

        //今日剩余可提现额度
        $today_already_integral = $sysSetting['points_withdrawal_rule']['daily_withdrawal_limit'] - $integralSum;
        $today_already_integral = $today_already_integral ? $today_already_integral : 0;//为负数时为0

        $list = [
            'integral_balance' => $_usr_user['integral'],//剩余积分
            'can_withdraw_amount' => $can_withdraw_amount,//可提现总金额
            'today_already_integral' => $integralSum,//今日已提现总额
            'today_can_withdraw_amount' => $today_already_integral,//今日剩余可提现额度
            'integral_exchange_ratio' => $sysSetting['integral_exchange_ratio'],//现金兑换比例
            'points_withdrawal_rule' => $sysSetting['points_withdrawal_rule'],//可提现选项
        ];

        return $this->success($list);
    }

}
