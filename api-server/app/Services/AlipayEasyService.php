<?php

namespace App\Services;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\SMSController;
use App\Models\PayChannel;
use App\Models\UsrUser;
use App\Models\WithdrawOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 支付宝SDK Service
 */
class AlipayEasyService
{
    protected $config;
    protected $alipayUserId;
    protected $textParams;
    protected $payChannels;

    public function __construct()
    {
        $this->payChannels = PayChannel::where('pay_code','zfb_pay')->where('status', 1)->first();
        $this->config = $this->getOptions();
        Factory::setOptions($this->config);//1. 设置参数（全局只需设置一次）
        $this->alipayUserId = $this->payChannels['zfb_alipay_user_id'];//'2088141469778252';//蚂蚁统一会员ID
        $this->textParams = [//公共请求参数
            'app_id' => $this->config->appId,
            'method' => '',
            'charset' => "UTF-8",
            'sign_type' => $this->config->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
    }

    /**
     * 支付宝SDK设置参数（全局只需设置一次）
     * @return Config
     * User: zxw
     * User: zxw
     * Date: 2022/1/18 14:22
     */
    protected function getOptions(): Config
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = $this->payChannels['zfb_appid'];//'2021002156660486';//<-- 请填写您的AppId，例如：2019022663440152 -->

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = $this->payChannels['zfb_merchant_private_key'];//'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCSFUtdI7vi1se26qVP8Pg/Vv0JXNzMRllDvwIN/cGDov/3Xz2OoLn+kbe8nV8J+fIG8PlS2PA6+zsXcMRlP4BOwfFF/EJKkUOPxrxvoS8lLx6C/zIhGC0A/reuidY/ranXtSNhcycrycJ7TLLMSIDGC3XRiZDi3AdyBWTpvnjlqmIgUxuGLFm/8LcF8xdyNZ2WTtcrSipkro8uFCJqZwFWPJ3TRx0uMAQThNGCyql0NcDpppkVNDJ5tDh2S3UIci4FGsbzy71Yqd3rOPtgcNE966NVXaOwgVlVHE7BPv3O/JuTqsonUa3BuoKRsmPKCkrs21wYfV6ZgSvI2BWeIx+jAgMBAAECggEAbddxKYF6/x+8X7+jua5ZG1dPQEEBDOBAsn3nD5okbdScXubQJHSaJd5vp3U1Rw0XfTyoXDEewVqynfd+1RqgYZfW0WbSebssb+lhOxaZmn4JlTpJ+TRycnMUrjqaTJtKQBXFmrq5U2WLxKZxMsW5fjCT0JB9zvbe6k9AB7neseD9/PcfQyZZ3zoUrWskkETrKBYSlju1AQvk7E2ujT6LKgVEnrvlAgXEuQkCI8wcHHuzpHelH6qGl1Vfry7WCe2lmVqLxRNyTq7G4kxQsXuIpIEBpUmXniJisDo7uj2OSJmozBfcrJv04c5aRbDue1Yhr0/pEg2+PziFaEwMKS520QKBgQDP94MI6Qo9x5Uy5f7R5k0FEsTkr4/3kaiYTx2UBDqMVOIxWEv2Ra2c5OgAWcD6AqmiOo4xf44BzhDw2hmk2nczDHL2Ji7xnOcCk+mqd6fb4X1HXDt6wpTMOfI8eogvnQTCuOY1KeMYRzgjzYJujR+ijf8yQUCixTdv2bJgem6zRQKBgQCz0scNkBXapnP+2r9DBljvjmksbUtHTJT+YcU9n0gvmNEdu/0jSFUmjUm2IbOAH89XvBjShbkb6XLDbN2DNM7rLwPy18craiGltmdP1xbvWv0bbcP4M5l9L3YwYlL5rTmzxhlEZFzcdD9/4RC8KgOT/PpFkmt6FiV9jFSdvsCBxwKBgCo1LMYBLg/t0s0ausX0/Mq7zXQwYYK4cERBQlqJJSzYCXREXF5mM9804hU4Ih9brPv88GEBZ1vca7nGOhAoOqsEqsxkYYCt/ICcbn8ne8z3jcqO4I+AsFxmolA9+ifXsWCn0CkYEDwcMDur+P3g7Hu8X//eGHUwm5i60SYdkxwVAoGAWxIpu4W4e7cHUhApA2HoktJ2E4j6sg5n+vk7Mn1Dys9DQSLfDgppDZBKv5IL3Zy+nrllfOE6oZc2hyDQgs2w6c0y279KYINsrQdXBUlylSBoxYZu1HoVhyANZG23hjmj2pc+XrPRj9jT/AjZN+KzUzSw76E7C2bB7/atOALObisCgYEAvrbDaVF3Chv1Btq0AQqe3GkiSbsKG4wRrsbrU6sRdL9Vcq6jXIyyDI6JRmAInXjBKtFwbA08zg61Nag2elrmU1IXdoLy+MwonqtqoInWJQUfMRHVAsbiNvibjb6ojZxds3FVu2PzUTK4F9/Qas76bx6nwt91rc5ZQ2KsvwzHm+I=';//<-- 请填写您的应用私钥，例如：MIIEvQIBADANB ... ... -->

        $options->alipayCertPath = './../foos/alipayCert/alipayCertPublicKey_RSA2.crt';//<-- 请填写您的支付宝公钥证书文件路径，例如：/foos/alipayCertPublicKey_RSA2.crt -->
        $options->alipayRootCertPath = './../foos/alipayCert/alipayRootCert.crt';//<-- 请填写您的支付宝根证书文件路径，例如：/foos/alipayRootCert.crt" -->
        $options->merchantCertPath = './../foos/alipayCert/appCertPublicKey_2021002156660486.crt';//<-- 请填写您的应用公钥证书文件路径，例如：/foos/appCertPublicKey_2019051064521003.crt -->

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // $options->alipayPublicKey = '<-- 请填写您的支付宝公钥，例如：MIIBIjANBg... -->';
        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = $this->payChannels['_zfb_notify_url'];//"https://api.hisport.cloud/api/alipay/notify-url";//<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $options->encryptKey = $this->payChannels['zfb_encrypt_key'];//"JpCnu2cYu24QIOx/AB6F7Q==";//<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->
        return $options;
    }

    /**
     * @return string
     * User: zxw
     * Date: 2022/1/19 9:24
     */
    public function alipayAsynurl()
    {
        return '';
    }

    /**
     * 支付宝通知回调验签
     * @param $param
     * @return bool
     * User: zxw
     * Date: 2022/1/20 18:07
     */
    public function isVerifyNotify($param): bool
    {
        return Factory::payment()->common()->verifyNotify($param);
    }

    /**
     * 支付宝提现
     * @param $param
     * @return bool
     * User: zxw
     * Date: 2022/1/18 14:04
     * @throws BusinessException
     */
    public function alipayWithdrawal($param): bool
    {
        //TODO 根据需求看是否需要条件15分钟内条件限制...
        $withdrawOrder = WithdrawOrder::where([
            'id' => $param['id'],
            'user_id' => $param['user_id'],
            'status' => 10
        ])->first();
        if (!$withdrawOrder) return false;//不存在直接终止

        $usrUser = UsrUser::where('user_id', $param['user_id'])->where('status', 1)->first();

        //支付宝资金账户资产查询
        $alipayFundAccountQuery = $this->alipayFundAccountQuery();
        if ($alipayFundAccountQuery['data'] == false){
            $withdrawOrder->status = 20;//修改支付失败状态

            $msg = ['msg' => $alipayFundAccountQuery['msg']];
            $withdrawOrder->msg_info = json_encode($msg);
            $withdrawOrder->save();

            $integral_balance = $param['integral'] + $usrUser['integral'];
            //写入积分日志
            $redeemService = new RedeemService();
            $param['remark'] = $alipayFundAccountQuery['msg'];
            $redeemService->pointsWithdrawalLog($param, $integral_balance,1);

            //用户积分账户提增返还
            UsrUser::where('user_id',$withdrawOrder['user_id'])->increment('integral',$param['integral']);

            Redis::select(1);
            $_usr_user = json_decode(Redis::hget("usr_user", $usrUser['access_token']),true);
            $_usr_user['integral'] = $integral_balance;
            Redis::hset("usr_user", $usrUser['access_token'],json_encode($_usr_user));

            $this->sendSmsPa($usrUser['phone'], $withdrawOrder['pay_amount'],false);
            return false;//直接终止
        }
        if ($param['pay_amount'] > $alipayFundAccountQuery['data']->available_amount) {//资金账号不足，返还积分并取消订单
            $withdrawOrder->status = 20;//修改支付失败状态
            $msg = ['msg' => '资金账号不足，返还积分'];
            $withdrawOrder->msg_info = json_encode($msg);
            $withdrawOrder->save();

            $integral_balance = $param['integral'] + $usrUser['integral'];
            //写入积分日志
            $redeemService = new RedeemService();
            $param['remark'] = $withdrawOrder->msg_info;
            $redeemService->pointsWithdrawalLog($param, $integral_balance,1);

            //用户积分账户提增返还
            UsrUser::where('user_id',$param['user_id'])->increment('integral',$param['integral']);

            Redis::select(1);
            $_usr_user = json_decode(Redis::hget("usr_user", $usrUser['access_token']),true);
            $_usr_user['integral'] = $integral_balance;
            Redis::hset("usr_user", $usrUser['access_token'],json_encode($_usr_user));

            $this->sendSmsPa($usrUser['phone'], $withdrawOrder['pay_amount'],false);
            return false;//直接终止
//            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.insufficient_available_balance_in_merchant_funds_account_error'));
        }

        //支付转账操作
        $alipayFundTransUniTransfer = $this->alipayFundTransUniTransfer($param);
        Log::info("支付转账操作请求返回结果，记录日志：".json_encode($alipayFundTransUniTransfer));

        if ($alipayFundTransUniTransfer['data'] == false){
            $withdrawOrder->status = 20;//修改支付失败状态
            $msg = ['msg' => $alipayFundTransUniTransfer['msg']];
            $withdrawOrder->msg_info = json_encode($msg);
            $withdrawOrder->save();

            $integral_balance = $param['integral'] + $usrUser['integral'];
            //写入积分日志
            $redeemService = new RedeemService();
            $param['remark'] = $alipayFundTransUniTransfer['msg'];
            $redeemService->pointsWithdrawalLog($param, $integral_balance,1);

            //用户积分账户提增返还
            UsrUser::where('user_id',$param['user_id'])->increment('integral',$param['integral']);

            Redis::select(1);
            $_usr_user = json_decode(Redis::hget("usr_user", $usrUser['access_token']),true);
            $_usr_user['integral'] = $integral_balance;
            Redis::hset("usr_user", $usrUser['access_token'],json_encode($_usr_user));

            $this->sendSmsPa($usrUser['phone'], $withdrawOrder['pay_amount'],false);
            return false;//直接终止
        }

        $withdrawOrder->pay_order_no = $alipayFundTransUniTransfer['data']->order_id;//回写支付订单号
        $withdrawOrder->pay_fund_order_id = $alipayFundTransUniTransfer['data']->pay_fund_order_id;//回写支付资金流水号
        $withdrawOrder->status = 11;//修改已支付成功状态
        $msg = ['msg' => '支付成功'];
        $withdrawOrder->msg_info = json_encode($msg,true);
        $withdrawOrder->save();

        return true;
    }



    /**
     * 支付宝资金账户资产查询
     * @return array
     * User: zxw
     * Date: 2022/1/18 18:30
     */
    public function alipayFundAccountQuery(): array
    {
        $code = ErrorCode::SEVER_ERROR;
        $msg = '系统错误';
        $this->textParams['method'] = "alipay.fund.account.query";
        $responseApiName = str_replace(".", "_", $this->textParams['method']) . "_response";
        $bizParams = [//请求参数
            'alipay_user_id' => $this->alipayUserId,
            'account_type' => 'ACCTRANS_ACCOUNT'
        ];

        try {
            $result = Factory::util()->generic()->execute($this->textParams['method'], $this->textParams, $bizParams);
            if (empty($result->code) || $result->code != 10000) {
                $msg = $result->msg . "：" . $result->subMsg;
                $data = false;
//                throw new BusinessException(ErrorCode::SEVER_ERROR, $result->msg . "：" . $result->subMsg);
            }
            $result->httpBody = json_decode($result->httpBody);
            $data = $result->httpBody->$responseApiName;
            if ($data->code == 10000){
                $code = ErrorCode::SEVER_SUCCESS;
                $msg = 'ok';
            }else{
                $msg = $data->sub_msg;
                $data = false;
            }

        } catch (\Throwable $ex) {
            $msg = $ex->getMessage();
            $data = false;
            //throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * 单笔转账接口
     * @param $param
     * @return array
     * User: zxw
     * Date: 2022/1/18 18:47
     */
    public function alipayFundTransUniTransfer($param): array
    {
        $code = ErrorCode::SEVER_ERROR;
        $msg = '系统错误';
        $this->textParams['method'] = "alipay.fund.trans.uni.transfer";
        $responseApiName = str_replace(".", "_", $this->textParams['method']) . "_response";
        $bizParams = [//请求参数
            'out_biz_no' => $param['order_no'],
            'trans_amount' => $param['pay_amount'],
            'product_code' => 'TRANS_ACCOUNT_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER',
            'order_title' => $param['remark'] ?? "摇加油积分提现,全云动APP代发",
            'remark' => $param['remark'] ?? "摇加油积分提现,全云动APP代发",
            'payee_info' => [//收款方信息
                'identity' => $param['account'],
                'identity_type' => 'ALIPAY_LOGON_ID',
                'name' => $param['actual_name']
            ],
            'business_params' => '{"payer_show_name_use_alias":"全云动APP"}'
        ];

        try {
            $result = Factory::util()->generic()->execute($this->textParams['method'], $this->textParams, $bizParams);
            if (empty($result->code) || $result->code != 10000) {
                $msg = $result->msg . "：" . $result->subMsg;
                $data = false;
                //throw new BusinessException(ErrorCode::SEVER_ERROR, $result->msg . "：" . $result->subMsg);
            }
            $result->httpBody = json_decode($result->httpBody);
            $data = $result->httpBody->$responseApiName;
            if ($data->code == 10000){
                $code = ErrorCode::SEVER_SUCCESS;
                $msg = 'ok';
            }else{
                $msg = $data->sub_msg;
                $data = false;
            }
        } catch (\Throwable $ex) {
            $msg = $ex->getMessage();
            $data = false;
            //throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * 提现状态查询
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/24 10:25
     */
    public function withdrawalStatusQuery($param)
    {
        return WithdrawOrder::where([
            'user_id' => $param['user_id'],
            'order_no' => $param['order_no']
        ])->first();
    }

    /**
     * 助通科技短信积分提现短信送接口
     * @param $phone
     * @param $amount
     * @param bool $is_ok
     * @return mixed
     * User: zxw
     * Date: 2022/1/7 15:00
     */
    public function sendSmsPa($phone, $amount, bool $is_ok = true)
    {
        if ($is_ok == true){
            $content = "【全云动】尊敬的用户您好,您的积分提现 ".$amount." 已成功到账。";
        }else{
            $content = "【全云动】尊敬的用户您好,您的积分提现 ".$amount." 失败，提现积分已返还至账号，请进入APP查阅。";
        }
        $SMSController = new SMSController();
        return $SMSController->sendSmsPa([
            'phone' => [$phone],
            'content' => $content,
        ]);
    }

}
