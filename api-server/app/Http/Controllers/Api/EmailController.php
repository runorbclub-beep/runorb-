<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Mail\SendEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class EmailController extends Controller
{


    /**
     * @param Request $request
     * @return array
     * @author
     */
    public function bindEmail(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

//        字段缺失验证
        if (!isset($_data["email"]) || !isset($_data["msg_type"])) {
            return SystemErrorController::paramtersError($_language);
        }
        $email = $_data["email"];
        $email_preg = '/^([a-zA-Z0-9]+[_|\\_|\\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\\_|\\.|-]?)*[a-zA-Z0-9]+\\.[a-zA-Z]{2,3}$/';
        if (!preg_match($email_preg, $email)) {
            return ['code' => 1, 'msg' => $_language == 'zh-CN' ? '邮箱格式错误' : 'Email format error'];
        }

        $_subject = "";
        switch ($_data["msg_type"]){
            case "login":
                $_subject = 'runorb verification code';
                break;
        }

        $verify_code = rand(100000, 999999);
        Mail::to($email)->send(new SendEmail($verify_code, $_subject));
//        Mail::to($email)->queue(new SendEmail($verify_code, $_subject));
        $_data = [
            'msg_type' => $_data["msg_type"],
            'verify_code' => $verify_code,
            'email' => $email,
        ];
        $_send_result_type = "success";
        Redis::setex("bindEmail:" . $email, 300, json_encode($_data));

        return [
            'code' => 1,
            'msg' => LanguageController::getLanguage($_language, $_send_result_type),
            'data' => []
        ];
    }

    public static function checkNumber($_email, $_number, $_type)
    {
        Redis::select(1);
        $_email_check = json_decode(Redis::get("bindEmail:" . $_email), true);

        if (isset($_email_check["verify_code"]) && $_email_check["verify_code"] == $_number && $_email_check["msg_type"] == $_type) {
            return true;
        }

        return false;
    }


}
