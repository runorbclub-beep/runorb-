
<?php
/**
 * Created by PhpStorm.
 * User: ns210
 * Date: 2019/11/28
 * Time: 13:39
 */

namespace App\Http\Middleware;


use Closure;

class admin
{
    public function handle($request,Closure $next){
        return $next($request);
    }
}