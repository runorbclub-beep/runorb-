<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class TestMiddleware
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
//        Redis::select(1);
//        $_data = $request->input();
//
//        $_language = isset($_data['language'])?$_data['language']:'zh-CN';
//        $_user_token = $request->header("token");
//        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);
//
//
//        $data = array(
//            "code"=>1,
//            "msg"=>"success",
//            "data"=>array(
//                "data"=>$_data,
//                "token"=>$_user_token,
//                "language"=>$_language,
//                "user"=>$_usr_user
//            )
//        );
//        echo json_encode($data);exit;
//        return redirect('admin/home');
        return $next($request);
    }
}
