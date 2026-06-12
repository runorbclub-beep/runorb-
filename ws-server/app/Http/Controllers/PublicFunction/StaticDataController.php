<?php
/**
 * Created by PhpStorm.
 * User: ns210
 * Date: 2020/4/6
 * Time: 10:47
 */

namespace App\Http\Controllers\PublicFunction;


use App\Http\Controllers\Controller;

class StaticDataController extends Controller
{
//    机器ID
    public static $_workId = 1;

//    同一赛事，同一用户 最多参加的项目数
    public static $_match_max_sign_count = 2;

//    服务端URL地址
    public static $_server_url = "https://api-all-sporter.megacombine.com";

//    区间速度起止间隔
    public static $_speed_section = array(0,2000,6000,10000,14000,18000,22000);

//    每圈运动的米数
    public static $_circle_distance = 16.5876089;

//    短信相关账号  管理后台：http://mix2.zthysms.com/login，接口文档：https://doc.zthysms.com/web/#/1?page_id=4
    public static $_zhutong_user_name = "bazu88hy";
    public static $_zhutong_password = env("ZHUTONG_PASSWORD", "");
    public static $_zhutong_sign_name = "全云动";

    public static $_err_play_speed = array(
        array(
            "time"=>1,
            "max_speed"=>5000
        ),
        array(
            "time"=>5.5,
            "max_speed"=>7640
        ),
        array(
            "time"=>9,
            "max_speed"=>11200
        )
    );
}
