<?php


namespace App\Http\CommonClass;


use App\Http\Controllers\PublicFunction\StaticDataController;
use Illuminate\Support\Facades\Redis;

class SMSController
{


    /**
     * @abstract 助通科技短信发送接口
     * @param $_phone
     * @param $_number
     * @param $_smg_temp_id
     * @return mixed
     */
    public static function sendMsg($_phone,$_number,$_smg_temp_id){
        //模板信息发送demo sms_temp_id = 30268
        $url = "http://api.mix2.zthysms.com/v2/sendSmsTp";
        $records = array(array(
            "mobile"=>$_phone,
            "tpContent"=>array(
                "valid_code"=>$_number
            )
        ));

        $tKey     = time();
        $password = md5(md5(StaticDataController::$_zhutong_password) . $tKey);
        $date     = array(
            'tpId'      => $_smg_temp_id, //模板id
            'username'  => StaticDataController::$_zhutong_user_name, //用户名
            'password'  => $password, //密码
            'tKey'      => $tKey, //tKey
            'signature' => '【'.StaticDataController::$_zhutong_sign_name.'】',
            'records'   => $records
        );

        return json_decode(self::httpPost($url, $date),true);
    }

    public static function httpPost($url, $date) { // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
//        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($date)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HEADER, false); //开启header
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        )); //类型为json
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Error POST' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $result; // 返回数据
    }

    /**
     * @abstract 校验短信验证码是否存在
     * @param $_phone
     * @param $_number
     * @param $_type
     * @return bool
     */
    public static function checkNumber($_phone,$_number,$_type){
        Redis::select(1);
        $_phone_sms_check = json_decode(Redis::get("phone_sms:".$_phone),true);

        if(isset($_phone_sms_check["number"])&&$_phone_sms_check["number"]==$_number&&$_phone_sms_check["sms_type"]==$_type){
            return true;
        }

        return false;
    }

}
