<?php


namespace App\Http\Controllers\Api;


use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\SMSController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PhoneController extends Controller
{


    /**
     * @param Request $request
     * @return array
     * @throws BusinessException
     * @author
     */
    public function bindPhone(Request $request){
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language")!=null?$request->header("language"):'zh-CN';
//        $_user_token = $request->header("token");
//        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);

//        字段缺失验证
        if(!isset($_data["phone"])||!isset($_data["msg_type"])){
            return SystemErrorController::paramtersError($_language);
        }
        $_number = rand(100000,999999);

        $_msg_template_id = "";
        switch ($_data["msg_type"]){
            case "login"://登录-模板
                if ($_language == "zh-CN"){
                    $_msg_template_id = 33760;
                }else{
                    $_msg_template_id = 48384;
                }
                break;
            case "bind_phone"://变更手机号-模板
                if ($_language == "zh-CN"){
                    $_msg_template_id = 33761;
                }else{
                    $_msg_template_id = 48383;
                }
                break;
            case "change_phone"://
                if ($_language == "zh-CN"){
                    $_msg_template_id = 33761;
                }else{
                    $_msg_template_id = 48383;
                }
                break;
            case "merchant_login_phone"://积分兑换-商户登录发送验证码
                if ($_language == "zh-CN"){
                    $_msg_template_id = 33760;
                }else{
                    $_msg_template_id = 48384;
                }
                break;
            default://非法请求
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }

        if($_msg_template_id==""){
            return array(
                "code"=>0,
                "msg"=>SystemErrorController::paramtersError($_language)
            );
        }

        $_phone_prefix = "86";
        if(isset($_data["phone_prefix"])){
            $_phone_prefix = $_data["phone_prefix"];
        }
        
        Log::info("=====",$_data);

        if($_phone_prefix==="86"){
//            国内短信接口
            $_send_result = SMSController::sendMsg($_data["phone"],$_number,$_msg_template_id);
        }else{
//            国际短信接口
            $_send_result = json_decode(SMSController::sendOverseasMsg($_data["phone_prefix"],$_data["phone"],$_number),true);
        }

        Log::info("====2222",$_send_result);

        $_send_result_type = "error";
        if(array_key_exists("code",$_send_result) && $_send_result["code"]==200){
            $_phone_data = array(
                "msg_type"=>$_data["msg_type"],
                "number"=>$_number,
                "phone_prefix"=>$_phone_prefix,
            );

            $_send_result_type = "success";

            Redis::setex("phone_sms:".$_data["phone"],300,json_encode($_phone_data));
        }

        return array(
            "code"=>1,
            "msg"=>LanguageController::getLanguage($_language,$_send_result_type),
            "data"=>$_send_result
        );
    }

    /**
     * 助通科技短信群发送接口
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/7 15:00
     */
    public function sendSmsPa(SMSController $SMSController): JsonResponse
    {
        $content = '【全云动】尊敬的用户您好，后台现在做测试短信群发推送功能，如有打扰，敬请谅解。';
        $list = $SMSController->sendSmsPa([
            'phone' => [15112692950,18676739603,13535387224,16620845166],
            'content' => $content,
        ]);

        return $this->success($list);
    }


}
