<?php

namespace App\Http\Middleware;

use Closure;

class userTokenVerify
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
//        return redirect()->to("http://www.baidu.com");

        return $next($request);
    }
}
