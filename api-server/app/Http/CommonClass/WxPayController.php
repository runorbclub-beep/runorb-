<?php


namespace App\Http\CommonClass;


class WxPayController
{


    public static $_mchid = env("WECHAT_PAY_MCHID", "");
    public static $_appid = env("WECHAT_PAY_APPID", "");
    public static $serial_no = "469BC8FB67E68FD8B5D4BD072B74BB6C598740A9";
    public static $_pk = env("WECHAT_PAY_PRIVATE_KEY", "");  // Private key moved to .env

//
//    public static $_mchid = ""  // moved to .env;
//    public static $_appid = ""  // moved to .env;
//    public static $serial_no = "38CF0C4ECA59564410A13AA5EC7B36F54518604E";
//    public static $_pk = env("WECHAT_PAY_PRIVATE_KEY", "");  // Private key moved to .env


    /**
     * @author pengjl
     * @time 2021/6/22 19:53
     * @abstract _获取签名字符串
     */
    public static function getSign($_http_match, $_url, $body, $_out_trade_no = null, $_time = null)
    {
        if ($_time == null) {
            $_time = time();
        }
        if ($_out_trade_no == null) {
            $_out_trade_no = date("Ymd", $_time) . rand(10000, 99999);
        }

        $_sign = array($_http_match, $_url, $_time, $_out_trade_no, $body);

        $_sign_str = "";
        foreach ($_sign as $value) {
            $_sign_str .= $value . "\n";
        }

        openssl_sign($_sign_str, $raw_sign, self::$_pk, "sha256WithRSAEncryption");

        $signstr = base64_encode($raw_sign);

        return $signstr;

    }


    /**
     * @author pengjl
     * @time 2021/6/22 19:53
     * @abstract _获取微信签名header
     */
    public static function getAuthorization($signstr, $_out_trade_no = null, $_time = null)
    {
        if ($_time == null) {
            $_time = time();
        }
        if ($_out_trade_no == null) {
            $_out_trade_no = date("Ymd", $_time) . rand(10000, 99999);
        }

        $_arr = array(
            "mchid" => self::$_mchid,
            "nonce_str" => $_out_trade_no,
            "timestamp" => $_time,
            "serial_no" => self::$serial_no,
            "signature" => $signstr,
        );

        $token = "";
        foreach ($_arr as $key => $value) {
            $token .= ',' . $key . '="' . $value . '"';
        }

        $_authorization = "WECHATPAY2-SHA256-RSA2048 " . substr($token, 1);

        return $_authorization;
    }


    /**
     * @abstract 发起http POST 请求，
     * @param $data array 请求内容
     * @param $url string 请求的接口地址
     * @return mixed
     */
    public static function post($url, $data = null, $header = null)
    {
        //初使化init方法
        $_sys_curl = curl_init();

        //指定URL
        curl_setopt($_sys_curl, CURLOPT_URL, $url);

        //设定请求后返回结果
        curl_setopt($_sys_curl, CURLOPT_RETURNTRANSFER, 1);

        //声明使用POST方式来进行发送
        curl_setopt($_sys_curl, CURLOPT_POST, 1);

        //发送数据
        if ($data != null) {
            curl_setopt($_sys_curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        //忽略证书
        curl_setopt($_sys_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_sys_curl, CURLOPT_SSL_VERIFYHOST, false);

        //设置header头信息
        if ($header != null) {
            curl_setopt($_sys_curl, CURLOPT_HTTPHEADER, $header);
        }

        //设置超时时间
        curl_setopt($_sys_curl, CURLOPT_TIMEOUT, 10);


        //发送请求
        $output = curl_exec($_sys_curl);

        //关闭curl
        curl_close($_sys_curl);

        //返回数据
        return json_decode($output, true);

    }

    /**
     * @abstract 发起http POST 请求，
     * @param $data array 请求内容
     * @param $url string 请求的接口地址
     * @return mixed
     */
    public static function get($url, $data = null, $header = null)
    {

        //初使化init方法
        $_sys_curl = curl_init();

        if ($data != null && count($data) > 0) {
            $querystring = http_build_query($data);
            $url = $url . '?' . $querystring;
        }

        if ($header != null) {
            curl_setopt($_sys_curl, CURLOPT_HTTPHEADER, $header);
        }

        //指定URL
        curl_setopt($_sys_curl, CURLOPT_URL, $url);
        curl_setopt($_sys_curl, CURLOPT_HEADER, false);
        curl_setopt($_sys_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_sys_curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($_sys_curl, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($_sys_curl); //执行并获取HTML文档内容
        curl_close($_sys_curl); //释放curl句柄
        return json_decode($output, true);

    }


}
