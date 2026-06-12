<?php

namespace App\Http\Middleware;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\LanguageController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ApiCertificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws BusinessException
     */
    public function handle(Request $request, Closure $next)
    {

        $start = microtime(true);
        $_log_data = array(
            "url" => $request->path(),
            "data" => $request->input(),
            "token" => $request->header("token"),
        );
//        存储日志
        Log::info("请求数据：" . json_encode($_log_data));

        Redis::select(1);
        $_data = $request->input();
        $_data['language'] = $request->header("language");

        $_language = $_data['language'] ?? 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if(! in_array($_language,['zh-CN','en-US','en'])){
            throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.multilingual_parameter_error'));
        }
        App::setLocale($_language);//根据传参设置多语言

//        return array(
//            "code"=>1,
//            "msg"=>$_usr_user
//        );
//        return $next($request);
//        没有token，或者未找到用户，直接返回
        if ($_user_token == null || $_usr_user == null) {
            if ($_user_token == null) {
                $_return_data = array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "lack_token"),
                );
            }

            if ($_usr_user == null) {
                $_return_data = array(
                    "code" => 2,
                    "msg" => LanguageController::getLanguage($_language, "user_not_found"),
                );
            }

            echo json_encode($_return_data);
            exit();
        }

        $response = $next($request);
        $end = microtime(true);
        Log::info("返回数据：(" . ($end - $start) . ")" . $response);

        return $response;
    }
}
