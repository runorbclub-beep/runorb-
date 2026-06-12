<?php

namespace App\Http\Controllers\Api\PointsWithdrawal;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Jobs\PointsWithdrawalQueue;
use App\Models\SysSetting;
use App\Models\UsrUser;
use App\Models\WithdrawOrder;
use App\Services\AlipayEasyService;
use App\Services\RedeemService;
use App\Services\YouzanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 摇加油积分提现
 */
class PointsWithdrawalController extends Controller
{
    /**
     * 支付宝异步回调通知
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/19 9:57
     */
    public function alipayAsynurl(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::info('支付宝异步回调通知：11111111111111111');
        Log::info('支付宝异步回调通知：11111111111111111' . json_encode($data));
        //TODO 有需要再开发 。。。

        return $this->success();
    }

    /**
     * 支付宝异步回调通知
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/19 9:57
     */
    public function notifyUrl(Request $request, AlipayEasyService $alipayEasyService): JsonResponse
    {
        $data = $request->all();

        //回调示例参数
        /*$parameters  = [
            "charset" => "UTF-8",
            "biz_content" => "{\"pay_date\":\"2022-01-19 18:30:01\",\"biz_scene\":\"DIRECT_TRANSFER\",\"action_type\":\"FINISH\",\"pay_fund_order_id\":\"20220119110070001506250088945520\",\"origin_interface\":\"alipay.fund.trans.uni.transfer\",\"out_biz_no\":\"ZFB-SJ-JFDH-202201191830008822543732\",\"trans_amount\":\"0.10\",\"product_code\":\"TRANS_ACCOUNT_NO_PWD\",\"order_id\":\"20220119110070000006250063947373\",\"status\":\"SUCCESS\"}",
            "utc_timestamp" => "1642588201981",
            "sign" => "c+TSaaIzkA96SP6EzX2EikZJuGVgHnhDc3KKD3Pm+iBEFDJ7vTKWw+Oxnayu6CDWggQ1DU1GpZzqbKC2qHgnyVLyEBJTcslKEp3XxKeXChBEbdZQOd1l88+Yg+b6brnux1T/XEFZIyHV3M1MmUo4kkEZpIFhPugOrecrXi/EY1ttqzQmxoQzwByMsWBGJ/YXduomdYSSD0joE+8E4QFSnIRHNf61adXuGMuGR7zAqmRI01YS6M90n2ud+IMBMmlHWd02IOaVUS+KL4bI4oi6O20W7fAAIJfB2B3Wq2wgT6+rIJKtcqYBjxoEkdgNDTlpUIbIn2SslMyFbQuhFRggUg==",
            "app_id" => "2021002156660486",
            "version" => "1.1",
            "sign_type" => "RSA2",
            "notify_id" => "2022011900222183001063841410260953",
            "msg_method" => "alipay.fund.trans.order.changed"
        ];*/

        //验签
        try {
            if ($alipayEasyService->isVerifyNotify($data) == true) {//验签成功
                $data['biz_content'] = json_decode($data['biz_content'], true);
                //处理消息业务
                if ($data['biz_content']['status'] == "SUCCESS"){
                    $withdrawOrder = WithdrawOrder::where([
                        'order_no' => $data['biz_content']['out_biz_no'],
//                        'pay_fund_order_id' => $data['biz_content']['pay_fund_order_id'],
//                        'biz_scene' => $data['biz_content']['biz_scene'],
                        'status' => 10,
                        'pay_time' => null,
                    ])->first();
                    if ($withdrawOrder) {
                        $usrUser = UsrUser::where('user_id', $withdrawOrder['user_id'])->where('status', 1)->first();

                        $withdrawOrder->status = 11;
                        $withdrawOrder->pay_order_no = $data['biz_content']['order_id'];
                        $withdrawOrder->pay_time = $data['biz_content']['pay_date'];
                        $withdrawOrder->save();
                        Log::info('支付宝异步回调通知成功：'.json_encode($data));

                        //发送提现成功短信通知
                        $alipayEasyService->sendSmsPa($usrUser['phone'], $withdrawOrder['pay_amount'],true);
                    }
                }
            }
        } catch (\Throwable $ex) {
            //TODO 支付失败的返还有赞积分商城（提现订单状态为20的考虑定时任务返还） 。。。
            Log::error('支付宝异步回调通知失败：' . json_encode($data));
            Log::error('支付宝异步回调通知失败信息：' . json_encode($ex));
        }

        return $this->success();
    }

    /**
     * 摇加油积分提现
     * @param Request $request
     * @param AlipayEasyService $alipayEasyService
     * @param RedeemService $redeemService
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/18 14:01
     * @throws BusinessException
     */
    public function pointsWithdrawal(Request $request, AlipayEasyService $alipayEasyService, RedeemService $redeemService): JsonResponse
    {
        $data = $request->all();
        $_token = $request->header('token');
        if (empty($_token)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.lack_token'));

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);
        $data['user_id'] = $_usr_user['user_id'];

        //验证用户积分是否大于提现积分
        $usrUser = UsrUser::where('user_id', $data['user_id'])->where('status', 1)->first();
        if ($data['integral'] > $usrUser['integral']) {
            $alipayEasyService->sendSmsPa($usrUser['phone'],false);
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.insufficient_balance_of_points_available_error'));
        }

        //获取积分换算人民币比例配置与积分换算人民币
        $sysSetting = SysSetting::where('sys_setting_id', 48216651664986112)->first();
        $data['trans_amount'] = $data['integral'] / $sysSetting->integral_exchange_ratio;

        //提现下单写入订单
        DB::beginTransaction();
        try {
            //提现成功写入提现订单（写入队列记录）
            $list = WithdrawOrder::create([
                'user_id' => $data['user_id'],
                'account' => $data['account'],
                'pay_channel_id' => $data['pay_channel_id'],
                'user_name' => $_usr_user['user_name'],
                'actual_name' => $data['actual_name'],
                'order_no' => get_order_number('ZFB-SJ-JFDH-'),//生成平台侧唯一订单号
                'order_num' => 1,//提现固定写死1
                'pay_amount' => $data['trans_amount'],
                'integral' => $data['integral'],
                'integral_exchange_ratio' => $sysSetting->integral_exchange_ratio,
                'order_time' => date('Y-m-d H:i:s'),
                'status' => 10,
                'remark' => "摇加油积分提现,全云动APP代发",
                'created_at' => Carbon::now()->toDateTimeString()
            ]);
            $usrUser['integral'] = $usrUser['integral'] - $data['integral'];
            //写入积分日志
            $redeemService->pointsWithdrawalLog($list, $usrUser['integral'],2);
            //用户积分账户提减
            UsrUser::where('user_id',$data['user_id'])->decrement('integral',$data['integral']);
            Redis::select(1);
            $_usr_user['integral'] = $usrUser['integral'];
            Redis::hset("usr_user", $_token,json_encode($_usr_user));

            //执行有赞积分提现扣减请求
            $youzanService = new YouzanService();
            $youzanService->decreaseUserPoints([
                'reason' => "支付宝积分提现",
                'biz_value' => $list->order_no,
                'points' => $list->integral,
                'account_id' => $usrUser['phone']
            ]);

            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            Log::error('提现下单失败，但写入订单日志失败:' . json_encode($ex));
            $alipayEasyService->sendSmsPa($usrUser['phone'], $data['trans_amount'],false);
            return $this->error(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }

        //写入队列
        $this->dispatch(new PointsWithdrawalQueue($list));

        //直接执行
//            $list = $alipayEasyService->alipayWithdrawal($list);

        return $this->success($list);
    }

    /**
     * 提现状态查询
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/24 10:03
     */
    public function withdrawalStatusQuery(Request $request, AlipayEasyService $alipayEasyService): JsonResponse
    {
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header('token');
        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }
        if (empty($_data['order_no'])) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.request_parameter_error'));
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $_data['user_id'] = $_usr_user['user_id'];

        $list = $alipayEasyService->withdrawalStatusQuery($_data);
        if (!empty($list) && $list['status'] == 20){
            $list['msg_info'] = json_decode($list['msg_info'],true);
        }

        return $this->success($list);
    }

}
