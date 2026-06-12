<?php
namespace App\Http\Controllers\Admin;

use App\Http\CommonClass\RandName;
use App\Http\CommonClass\Rsa2;
use App\Http\CommonClass\SMSController;
use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\SysHttp;
use App\Http\CommonClass\TimeFormatController;
use App\Http\Controllers\Controller;


use App\Http\Controllers\PublicFunction\AppFontController;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\UserPlayFunction;
use App\Http\Controllers\PublicFunction\UsrUserController;
use App\Models\AdminUser;
use App\Models\AdminUserRole;
use App\Models\Answers;
use App\Models\CountryBaseLanguage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\Question;
use App\Models\SpiderHis;
use App\Models\SysCountry;
use App\Models\SysMedal;
use App\Models\SysSex;
use App\Models\SysUserType;
use App\Models\UserMedalAssociated;
use App\Models\UserPkList;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Models\UsrUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

use Image;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;


/**
 * Created by PhpStorm.
 * User: pengjl
 * Date: 2020/2/27
 * Time: 14:35
 */
class testController extends Controller
{

    public function home(Request $request){
        return array($request);
    }


    public function getTest(Request $request){

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>'aaa'
        );

        //国际短信发送
        $url = "https://api-gateway.zthysms.com/ims/v1/send";
        $phone = "85363534800";
        #发送内容
        $content = "【RunBall】Your registration verification code is 369852";
        #定时时间。留空则表示立即发送，时间格式为：yyyy-MM-dd HH:mm:ss
        $sendTime = "";
        #扩展号
        $ext = "";
        #短信类型 固定值：YZM（验证码） YX(营销)
        $type  = "YZM";
        $date     = array(
            'phone'  => $phone, //用户名
            'content'  => $content, //密码
            'type'      => $type,   //发送类型   必填
            'ext'   => $ext,        //可为空
            'sendTime'   => $sendTime  //定时时间。留空则表示立即发送
        );
        #账号
        $username = "lishanghy";
        #密码
        $password = 'YK5Khn8M';
        $auth = 'Basic '.base64_encode("$username:$password");
        $curl = curl_init(); // 启动一个CURL会话

        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($date)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HEADER, false); //开启header

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: '.$auth
        )); //类型为json
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Error POST' . curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return array($result); // 返回数据

        return array(
            "data"=>$_result,
            "time"=>date("Y-m-d H:i:s",time())
        );


        phpinfo();exit();

        $_img_url = "medal_image/2021/02/2021-02-09/勋章模板_1-13@3x.png";
        $img_base = Image::make($_img_url);


        $_text = "Lv.1";
        $_text_len = mb_strlen($_text,"UTF8");
        $_font_size = 30;
        $img_base->text($_text);
        $_file_name = "_live_img.png";
//            $img_base->resize($img_base->width()/2,$img_base->height()/2);
        $img_base->save($_file_name);


        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_file_name
        );

    }


}


