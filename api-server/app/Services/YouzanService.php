<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Models\BrandRedeemLog;
use App\Models\UsrUser;
use Illuminate\Support\Facades\Log;
use Youzan\Open\Client;

/**
 * 第三方有赞API对接Service
 */
class YouzanService
{
    protected $url = SettingMessage::SET_YOU_ZAN_URL;//有赞请求URL域名地址
    protected $clientId = SettingMessage::SET_CLIENT_ID;//有赞云颁发给开发者的应用ID
    protected $clientSecret = SettingMessage::SET_CLIENT_SECRET;//有赞云颁发给开发者的应用secret
    protected $authorizeType = SettingMessage::SET_AUTHORIZE_TYPE;//授权方式（固定为 “silent”）
    protected $grantId = SettingMessage::SET_GRANT_ID;//授权店铺id（即kdt_id），API接口对接传店铺id，支付商户对接传mchId
    protected $accessTokenData = '';
    protected $client;

    /**
     * @throws BusinessException
     */
    public function __construct()
    {
        $this->accessTokenData = $this->getYouZanAccessToken();
        $this->client = new Client($this->accessTokenData['access_token']);
    }

    /**
     * 有赞获取和刷新 access_token
     * @return mixed
     * User: zxw
     * Date: 2022/1/25 10:51
     * @throws BusinessException
     */
    public function getYouZanAccessToken()
    {
        $this->url = $this->url . "/auth/token";
        $data = [
            'client_id' => $this->clientId,//有赞云颁发给开发者的应用ID
            'client_secret' => $this->clientSecret,//有赞云颁发给开发者的应用secret
            'authorize_type' => $this->authorizeType,//授权方式（固定为 “silent”）
            'grant_id' => $this->grantId,//授权店铺id（即kdt_id），API接口对接传店铺id，支付商户对接传mchId
            'refresh' => false,//是否刷新，默认为false，如需刷新access_token则值为true
        ];

        //请求有赞获取access_token
        $accessToken = postCurl($this->url, $data);
        if ($accessToken['code'] == 200) {
            if ($accessToken['data']['expires'] <= (int)(microtime(true)*1000)){//刷新token
                $data['refresh'] = true;//设置刷新token
                $accessToken = postCurl($this->url, $data);
            }
            $accessToken = json_encode($accessToken['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            throw new BusinessException(ErrorCode::SEVER_ERROR, $accessToken['message']);
        }
        return json_decode($accessToken, true);
    }

    /**
     * 给用户加积分
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/25 10:51
     */
    public function increaseUserPoints($param)
    {
        $data = [
            'params' => [
                'reason' => $param['reason'],//Y 积分变动原因
                'biz_value' => $param['biz_value'],//业务唯一标示，外部开发者自定义 IN20190820001
                'check_customer' => true,//是否检验客户信息 默认false
                'points' => $param['points'],//Y 积分变动值
                'is_do_ext_point' => true,//是否需要走扩展点，默认：true (false走内部逻辑)
                'user' => [
                    'account_type' => '2',//帐号类型（支持的用户账号类型 1-有赞粉丝id(有赞不同的合作渠道会生成不同渠道对应在有赞平台下的fans_id) ; 2-手机号; 3-三方帐号(原open_user_id:三方App用户ID，该参数仅限购买App开店插件的商家使用) ;5-有赞用户id，用户在有赞的唯一id。推荐使用）
                    'account_id' => $param['account_id'],//帐号ID（account_id+account_type和yz_open_id二选一）
                ]
                //'biz_value' => '',//外部业务标识；用于幂等支持（幂等时效7天, 超过7天的相同值调用不保证幂等）[即相同帐号、业务标识字段的重复调用在7天内不会重复发放积分]
                //'yz_open_id' => '',//有赞对外openId【可通过youzan.user.openid.get接口获取】（account_id+account_type和yz_open_id二选一）
            ]
        ];

        $method = 'youzan.crm.customer.points.increase';
        $apiVersion = '4.0.0';
        return $this->client->post($method, $apiVersion, $data);
    }

    /**
     * 给用户减积分
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/25 10:51
     */
    public function decreaseUserPoints($param)
    {
        $data = [
            'params' => [
                'reason' => $param['reason'],//Y 积分变动原因
                'biz_value' => $param['biz_value'],//业务唯一标示，外部开发者自定义 IN20190820001
                'check_customer' => true,//是否检验客户信息 默认false
                'points' => $param['points'],//Y 积分变动值
                'is_do_ext_point' => true,//是否需要走扩展点，默认：true (false走内部逻辑)
                'user' => [
                    'account_type' => '2',//帐号类型（支持的用户账号类型 1-有赞粉丝id(有赞不同的合作渠道会生成不同渠道对应在有赞平台下的fans_id) ; 2-手机号; 3-三方帐号(原open_user_id:三方App用户ID，该参数仅限购买App开店插件的商家使用) ;5-有赞用户id，用户在有赞的唯一id。推荐使用）
                    'account_id' => $param['account_id'],//帐号ID（account_id+account_type和yz_open_id二选一）
                ]
                //'biz_value' => '',//外部业务标识；用于幂等支持（幂等时效7天, 超过7天的相同值调用不保证幂等）[即相同帐号、业务标识字段的重复调用在7天内不会重复发放积分]
                //'yz_open_id' => '',//有赞对外openId【可通过youzan.user.openid.get接口获取】（account_id+account_type和yz_open_id二选一）
            ]
        ];
        $method = 'youzan.crm.customer.points.decrease';
        $apiVersion = '4.0.0';

        return $this->client->post($method, $apiVersion, $data);
    }

    /**
     * 同步客户积分(根据传参覆盖掉用户当前积分值)
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/25 10:50
     * @throws BusinessException
     */
    public function syncUserPoints($param)
    {
        $map = ['status' => 1];
        isset($param['user_id']) ? $map['user_id'] = $param['user_id'] : '';
        isset($param['phone']) ? $map['phone'] = $param['phone'] : '';

        //查询用户积分
        $user = UsrUser::where($map)->first();
        if (empty($user)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.account_does_not_exist'));
        $data = [
            'points' => $user['integral'],//Y 积分变动值
            'reason' => $param['reason'],//Y 积分变动原因
            'biz_value' => $param['biz_value'],//业务唯一标示，外部开发者自定义 IN20190820001
            'is_do_ext_point' => true,//是否需要走扩展点，默认：true (false走内部逻辑)
            'user' => [
                'account_type' => '2',//帐号类型（支持的用户账号类型 1-有赞粉丝id(有赞不同的合作渠道会生成不同渠道对应在有赞平台下的fans_id) ; 2-手机号; 3-三方帐号(原open_user_id:三方App用户ID，该参数仅限购买App开店插件的商家使用) ;5-有赞用户id，用户在有赞的唯一id。推荐使用）
                'account_id' => $user['phone'],//帐号ID（account_id+account_type和yz_open_id二选一）
            ]
        ];
        $method = 'youzan.crm.customer.points.sync';
        $apiVersion = '4.0.0';

        return $this->client->post($method, $apiVersion, $data);
    }

    /**
     * 创建客户到店铺(根据客户信息，创建客户到店铺中)
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/25 10:53
     */
    public function createCustomer($param)
    {
        Log::info('创建客户到店铺的传参：'.json_encode($param,true));
        $data = [
            "label_info" => [//客户标识信息
                "srcChannel" => 2000,//客户来源渠道（不传或0：其他，2000：三方门店）
                "src_way" => 2008 //客户来源方式（不传或0：其他，2008：系统打通）
            ],
            "customer_create" => [//客户创建结构体
                "birthday" => $param['birthday'] ? $param['birthday'].'00:00:00' : date('Y-m-d 00:00:00'),//生日(日期格式:yyyy-MM-dd HH:mm:ss)
                "gender" => $param['sys_sex_id'],//性别，0：未知；1：男；2：女
                "name" => $param['user_name'],//用户昵称
                "remark" => $param['remark'],//客户信息备注
                "ascriptionKdtId" => $this->grantId //归属分店
            ],
            "mobile" => $param['phone'],//注册手机号（仅支持中国大陆地区手机号码）
            "is_do_ext_point" => false,////是否需要走扩展点，默认：true (false走内部逻辑)
            "create_date" => date('Y-m-d H:i:s'),//用户创建时间(日期格式:yyyy-MM-dd HH:mm:ss)
            "scrm_channel_type" => 0 //scrm渠道类型（2：伯俊），其他开发者无需使用该字段
        ];

        $method = 'youzan.scrm.customer.create';
        $apiVersion = '3.0.0';
        return $this->client->post($method, $apiVersion, $data);
    }



}
