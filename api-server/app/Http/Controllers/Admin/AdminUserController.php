<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AdminUserController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/10 14:39
     * @abstract _管理后台用户登录
     */
    public function postUserLogin(Request $request){




//        dd($request->header());
        $_data = request()->input();
//var_dump(file_get_contents("php://input"));
//        return array(
//            "code"=>1,
//            "data"=>$_data
//        );


        $_arrOfAdminUser = AdminUser::where([
            "admin_user.user_name"=>$_data["username"],
            "admin_user.password"=>$_data["password"]
        ])->join("admin_user_role",function ($join){
            $join->on("admin_user.admin_user_role_id","=","admin_user_role.admin_user_role_id");
        })->select("admin_user.admin_user_id","admin_user.user_name","admin_user.nick_name","admin_user_role.role_name"
        ,"admin_user_role.role_code","admin_user.user_img")->get();

//        return array(
//            "code"=>1,
//            "msg"=>"success",
//            "data"=>$_arrOfAdminUser
//        );
        $_arrOfAdminUserData = array();
        if(count($_arrOfAdminUser)==1){
            $_arrOfAdminUserData = $_arrOfAdminUser[0];
            $_user_token = md5($_arrOfAdminUserData["admin_user_id"].$_arrOfAdminUserData["user_name"]);
            $_arrOfAdminUserData["token"] = $_user_token;

            $_token_key = "admin_user_token:".$_user_token;

            Redis::select(1);
//            token 一小时过期
            Redis::setex($_token_key,3600,json_encode($_arrOfAdminUserData));

            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfAdminUserData
            );
        }else{
            return array(
                "code"=>0,
                "msg"=>"账号或密码错误",
            );
        }

    }

    /**
     * @author pengjl
     * @time 2021/5/10 14:39
     * @abstract _获取用户信息
     */
    public function getUserInfo(Request $request){
        $_user_token = $request->header("token");

        Redis::select(1);
        $_redis_key = "admin_user_token:".$_user_token;

        $_admin_user = json_decode(Redis::get($_redis_key),true);

        $_admin_user["permission"] = array($_admin_user["role_code"]);

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_admin_user
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/10 14:39
     * @abstract _用户退出登录
     */
    public function postAuthLogout(Request $request){
        $_user_token = $request->header("token");

        Redis::select(1);
        $_redis_key = "admin_user_token:".$_user_token;

        $_admin_user = json_decode(Redis::get($_redis_key),true);
//      退出登录
        Redis::setex($_redis_key,1,json_encode($_admin_user));

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array()
        );
    }
}
