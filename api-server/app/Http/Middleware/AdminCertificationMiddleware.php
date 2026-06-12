<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PublicFunction\LanguageController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AdminCertificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $_data = $request->input();

        $_log_data = array(
            "url"=>$request->path(),
            "data"=>$_data,
            "token"=>$request->header("token"),
        );
//        存储日志
        Log::info("请求数据：".json_encode($_log_data));

        Redis::select(1);

        $_language = isset($_data['language'])?$_data['language']:'zh-CN';
        $_user_token = $request->header("token");
        $_token_key = "admin_user_token:".$_user_token;

        $_admin_user = json_decode(Redis::get($_token_key),true);

//        没有token，或者未找到用户，直接返回
        if($_user_token == null || $_admin_user == null){
            if($_user_token == null){
                $_return_data = array(
                    "code"=>0,
                    "msg"=>LanguageController::getLanguage($_language,"lack_token"),
                );
            }

            if($_admin_user == null){
                $_return_data = array(
                    "code"=>2,
                    "msg"=>LanguageController::getLanguage($_language,"user_not_found"),
                );
            }

            return response()->json($_return_data, 401);
        }

//        有效请求，续上token生命周期,测试使用24小时
        Redis::setex($_token_key,3600*24,json_encode($_admin_user));

        $response = $next($request);

        Log::info("返回数据：".$response);

        return $response;
    }
}
