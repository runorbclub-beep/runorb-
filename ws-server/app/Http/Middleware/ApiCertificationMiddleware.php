<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PublicFunction\LanguageController;
use Closure;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ApiCertificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $_log_data = array(
            "url"=>$request->path(),
            "data"=>$request->input(),
            "token"=>$request->header("token"),
        );
//        存储日志
        Log::info(json_encode($_log_data));

        Redis::select(1);
        $_data = $request->input();

        $_language = isset($_data['language'])?$_data['language']:'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);
        return $next($request);
//        没有token，或者未找到用户，直接返回
        if($_user_token == null || $_usr_user == null){
            if($_user_token == null){
                $_return_data = array(
                    "code"=>0,
                    "msg"=>LanguageController::getLanguage($_language,"lack_token"),
                );
            }

            if($_usr_user == null){
                $_return_data = array(
                    "code"=>0,
                    "msg"=>LanguageController::getLanguage($_language,"user_not_found"),
                );
            }

            echo json_encode($_return_data);exit();
        }

        return $next($request);
    }
}
