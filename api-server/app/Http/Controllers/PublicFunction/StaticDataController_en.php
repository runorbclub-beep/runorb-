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
    public static $_server_url = "https://api.runorb.us";//https://api.runorb.com  https://api.hisport.cloud

    //国服host，把持与国服域名一致
    public static $_server_url_zh = "https://api.runorb.us";

    //国际服host，把持与国际域名一致
    public static $_server_url_en = "https://api.runorb.us";

//    机器ID
    public static $_workId = 1;

//    同一赛事，同一用户 最多参加的项目数
    public static $_match_max_sign_count = 2;

//    区间速度起止间隔
    public static $_speed_section = array(0,2000,6000,10000,14000,18000,22000);

//    每圈运动的米数
    public static $_circle_distance = 16.587608928;//16.5876089

//    短信相关账号  管理后台：http://mix2.zthysms.com/login，接口文档：https://doc.zthysms.com/web/#/1?page_id=4
    public static $_zhutong_user_name = "bazu88hy";
    public static $_zhutong_password = env("ZHUTONG_PASSWORD", "");
    public static $_zhutong_sign_name = "全云动";

//    初始化运动圈数
    public static $_init_circle_count = 28;

//    青年起止年龄
    public static $_yang_start_age = 0;//13
    public static $_yang_stop_age = 18;

//    默认用户信息修改次数
    public static $_user_detail_change_num = array(
        "user_name_change"=>3,
        "user_img_change"=>3,
        "user_city_change"=>3,
        "user_birthday_change"=>3,
        "sys_sex_id_change"=>3,
    );
//    异常检测
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

//    会员申请表默认数据
    public static $_befor_members_title_default = array(
        "zh-CN"=>array(
            "members_title"=>"摇跑俱乐部会员申请表",
            "members_description"=>"报名成为会员可以参加以后任何一项比赛，赢取更多比赛奖励",
            "members_fee"=>"300元",
            "members_preferential"=>"在抖音、快手等直播平台，体育健身类粉丝数量过1万，可免会员费"
        ),
        "en-US"=>array(
            "members_title"=>"RunOrb club application",
            "members_description"=>"报名成为会员可以参加以后任何一项比赛，赢取更多比赛奖励",
            "members_fee"=>"300 RMB",
            "members_preferential"=>"在抖音、快手等直播平台，体育健身类粉丝数量过1万，可免会员费"
        )
    );

//    榜单类型列表
    public static $_ranking_title_list = array(
        "max_speed"=>array(
            "title_zh"=>"摇跑转速",
            "title_en"=>"YP rpm",
            "type"=>"max_speed",
            "index"=>1
        ),
        "onemin"=>array(
            "title_zh"=>"摇跑1分钟",
            "title_en"=>"YP 1 minutes",
            "type"=>"onemin",
            "index"=>2
        ),
        "exponent"=>array(
            "title_zh"=>"活力指数",
            "title_en"=>"ei",
            "type"=>"exponent",
            "index"=>3
        ),
        "marathon"=>array(
            "title_zh"=>"摇跑马拉松",
            "title_en"=>"YP marathon",
            "type"=>"marathon",
            "index"=>4
        ),
    );

//    国家手机号区号
    public static $_overseas_code = array(
        array("name_cn"=>"中国","name_en"=>"China","code"=>"86"),
        array("name_cn"=>"中国香港","name_en"=>"Hong Kong, China","code"=>"852"),
        array("name_cn"=>"中国澳门","name_en"=>"Macau, China","code"=>"853"),
        array("name_cn"=>"俄罗斯","name_en"=>"Russia","code"=>"7"),
        array("name_cn"=>"新加坡","name_en"=>"Singapore","code"=>"65"),
//        array("name_cn"=>"加拿大","name_en"=>"Canada","code"=>"1"),
        array("name_cn"=>"瑞士","name_en"=>"Switzerland","code"=>"41"),
        array("name_cn"=>"哥伦比亚","name_en"=>"Colombia","code"=>"57"),
        array("name_cn"=>"古巴","name_en"=>"Cuba","code"=>"53"),
        array("name_cn"=>"泰国","name_en"=>"Thailand","code"=>"66"),
        array("name_cn"=>"德国","name_en"=>"Germany","code"=>"49"),
        array("name_cn"=>"丹麦","name_en"=>"Denmark","code"=>"45"),
        array("name_cn"=>"美国","name_en"=>"America","code"=>"1"),
        array("name_cn"=>"西班牙","name_en"=>"Spain","code"=>"34"),
        array("name_cn"=>"法国","name_en"=>"France","code"=>"33"),
        array("name_cn"=>"英国","name_en"=>"Britain","code"=>"44"),
        array("name_cn"=>"印度","name_en"=>"India","code"=>"91"),
        array("name_cn"=>"意大利","name_en"=>"Italy","code"=>"39"),
        array("name_cn"=>"日本","name_en"=>"Japan","code"=>"81"),
        array("name_cn"=>"墨西哥","name_en"=>"Mexico","code"=>"52"),
        array("name_cn"=>"马来西亚","name_en"=>"Malaysia","code"=>"60"),
        array("name_cn"=>"新西兰","name_en"=>"New Zealand","code"=>"64"),
        array("name_cn"=>"波兰","name_en"=>"Poland","code"=>"48"),
    );


    //异常指标
    public static $_abnormal_index = [
        'exponent_molecular' => 2487,       //摇跑1分钟
        'runball_exponent' => 203.6,        //摇跑指数
        'exponent_speed_max' => 100000,
        'exponent_denominator' => 612,      //半马时间
        'marathon' => 2000,                 //全马时间 因 user_play_id 567847928870211584 指标由1524调整为2000
    ];


}
