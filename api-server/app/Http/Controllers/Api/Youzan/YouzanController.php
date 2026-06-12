<?php

namespace App\Http\Controllers\Api\Youzan;

use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\BrandRedeemLog;
use App\Models\UsrUser;
use App\Services\RedeemService;
use App\Services\YouzanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 第三方有赞API对接服务
 */
class YouzanController extends Controller
{
    protected $url = SettingMessage::SET_YOU_ZAN_URL;//有赞请求URL域名地址
    protected $clientId = SettingMessage::SET_CLIENT_ID;//有赞云颁发给开发者的应用ID
    protected $clientSecret = SettingMessage::SET_CLIENT_SECRET;//有赞云颁发给开发者的应用secret
    protected $authorizeType = SettingMessage::SET_AUTHORIZE_TYPE;//授权方式（固定为 “silent”）
    protected $grantId = SettingMessage::SET_GRANT_ID;//授权店铺id（即kdt_id），API接口对接传店铺id，支付商户对接传mchId
    protected $accessTokenData = '';
    protected $client;
    protected $youzanService;

    public function __construct(YouzanService $youzanService)
    {
        $this->youzanService = $youzanService;
    }

    /**
     * 有赞回调地址
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/22 10:44
     */
    public function newsPush(Request $request): JsonResponse
    {
        $data = $request->all();
        Log::info('有赞消息推送' . json_encode($data, true));

        //有赞推送服务消息接收
        $myClientId = $this->clientId; //应用的 client_id
        $myClientSecret = $this->clientSecret; //应用的 client_secret
        $httpRequestBody = file_get_contents('php://input');
        $httpSign = $_SERVER['HTTP_EVENT_SIGN'];
        $httpType = $_SERVER['HTTP_EVENT_TYPE'];
        $httpClientId = $_SERVER['HTTP_CLIENT_ID'];
        // 判断消息是否合法，若合法则返回成功标识
        $sign = md5(sprintf('%s%s%s', $myClientId, $httpRequestBody, $myClientSecret));
        if ($sign != $httpSign) {
            // sign校验失败
            return $this->error(0,"faild");
        }

        // 根据 Type 来识别消息事件类型, 具体的 type 值以文档为准
        switch ($httpType){
            case "POINTS"://积分
                try {
                    // 建议异步处理业务逻辑
                    $httpData = json_decode($httpRequestBody, true);
                    // msg内容经过 urlencode 编码，需进行解码
                    $msg = json_decode(urldecode($httpData['msg']), true);
                    Log::info('有赞消息推送,打印消息内容11' . json_encode($msg, true));
                }catch (\Throwable $ex){
                    Log::info('有赞消息推送,打印消息内容222' . $ex);
                    return $this->error(0,"faild");
                }

                //有赞商城积分消耗，回调通知扣减，APP平台扣减积分并记录日志
                if ($msg['event_type'] == 302){//积分兑换消耗积分
                    try {
                        $usrUser = UsrUser::where('phone',$msg['mobile'])->where('status',1)->first();

                        $integral_balance = $usrUser['integral'] - $msg['amount'];
                        $redeemService = new RedeemService();
                        $redeemService->pointsWithdrawalLog([
                            'order_no' => $msg['biz_value'],
                            'user_id' => $usrUser->user_id,
                            'integral' => $msg['amount'],
                            'pay_name' => '有赞商城',
                            'remark' => '有赞商城积分兑换',
                            'integral_bill_type' => 3,
                            'create_time' => $msg['create_time'],
                        ],$integral_balance,2);

                        $usrUser = $usrUser->decrement('integral',$msg['amount']);

                        Redis::select(1);
                        $_usr_user = Redis::hget("usr_user", $usrUser['access_token']);
                        $_usr_user['integral'] = $integral_balance;
                        Redis::hset("usr_user", $usrUser['access_token'],json_encode($_usr_user));

                    }catch (\Throwable $ex){
                        Log::info('有赞商城积分兑换失败=========：'.json_encode($ex));
                        return $this->error(0,"faild");
                    }
                }

                break;
            case "scrm_points_change_push"://积分变更消息扩展点
                Log::info('有赞消息推送,打印消息内容' . json_encode('', true));
                break;
            case "SCRM_CUSTOMER_EVENT"://客户消息事件
                Log::info('有赞消息推送,打印消息内容' . json_encode('', true));
                break;
            default:
                return $this->error(0,"faild");
        }

        return $this->json(["code" => 0, "success" => "success"]);
    }

    /**
     * 创建客户到店铺(根据客户信息，创建客户到店铺中)
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/6 15:28
     */
    public function createCustomer(): JsonResponse
    {
        $createCustomer = $this->youzanService->createCustomer([
            'phone' => (string)15112692951,
            'birthday' => date('Y-m-d 00:00:00'),
            'sys_sex_id' => 0,
            'user_name' => 'ssss',
            'remark' => 'ssssss',
            'created_time' => date('Y-m-d H:i:s')
        ]);
        return $this->success($createCustomer);
    }

    /**
     * 同步客户积分
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/6 10:25
     * @throws BusinessException
     */
    public function syncUserPoints(): JsonResponse
    {
        $usrUser = UsrUser::where('status',1)->where('integral','>',0)->get()->toArray();
        $syncUserPoints = [];
        /*foreach ($usrUser as $k=>$v) {
            $syncUserPoints[] = $this->youzanService->syncUserPoints([
                'phone' => $v['phone'],
                'biz_value' => get_order_number('YJY-'),
                'reason' => "摇加油积分捐赠，积分清零"
            ]);
        }*/
        return $this->json($syncUserPoints);
    }


/*===========================================  以下都是伪代码（没有后端管理时应给有赞商城急增减积分用）  =================================================*/

    /**
     * 给用户加积分
     * User: zxw
     * Date: 2021/12/24 09:15
     */
//    public function increaseUserPoints(): JsonResponse
//    {
//        $user['user_id'] = 89062416159084544;
//        $youzanService = new YouzanService();
//        //给对应有赞平台的账户加对应积分与记录积分账单
//        $userData = UsrUser::selectRaw('user_id,integral,phone')->where('user_id', $user['user_id'])->first();
//        try {
//            //生产订单号
//            $orders = get_order_number('YJY-');
//            $list = $youzanService->increaseUserPoints([
//                'reason' => '“全云动” 摇加油积分发放',
//                'points' => 50,
//                'account_id' => $userData['phone'],
//                'biz_value' => $orders
//            ]);
//            //记录积分账单
//            BrandRedeemLog::create([
//                'order_no' => $orders,
//                'change_code' => $orders,
//                'type' => 1,
//                'integral_bill_type' => 2,
//                'user_id' => $userData['user_id'],
//                'integral' => 50,
//                'integral_balance' => $userData['integral'],
//                'project_name' => '“全云动” 摇加油积分发放',
//                'remark' => '“全云动” 摇加油积分发放',
//            ]);
//            if ($list['code'] == 200) {
//                Log::info(date('Y-m-d H:i:s') . '--摇加油给对应有赞平台的账户加对应积分:', $list);
//            }
//        } catch (\Throwable $ex) {
//            Log::info(date('Y-m-d H:i:s') . '--摇加油给对应有赞平台的账户加对应积分:' . json_encode($ex, true));
//        }
//
//        return $this->json($list);
//    }

    /**
     * 给用户减积分
     * @return JsonResponse
     */
//    public function decreaseUserPoints(): JsonResponse
//    {
//        $user['user_id'] = 89062416159084544;
//        $param['points'] = 50;
//        $youzanService = new YouzanService();
//        //给对应有赞平台的账户加对应积分与记录积分账单
//        $userData = UsrUser::selectRaw('user_id,integral,phone')->where('user_id', $user['user_id'])->first();
//        //生产订单号
//        $orders = get_order_number('YJY-');
//        $list = $youzanService->decreaseUserPoints([
//            'reason' => '有赞积分兑换',
//            'points' => $param['points'],
//            'account_id' => $userData['phone'],
//            'biz_value' => $orders,
//            'user_id' => $userData['user_id'],
//        ], true);
//        return $this->json($list);
//    }


}
