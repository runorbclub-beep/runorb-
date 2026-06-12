<?php


namespace App\Http\Controllers\PublicFunction;


class LanguageController
{
    static $_language = [
        'zh-CN'=>[
            'wait_release'=>"待发布",
            "sign_up"=>"报名中",
            "stop"=>"已结束",
            "all"=>"不限",
            "create_success"=>"创建成功",
            "update_success"=>"编辑成功",
            "delete_success"=>"删除成功",
            "delete_error"=>"删除失败",
            "delete_error_release_match"=>"删除失败，已发布的赛事/赛段不能删除",
            "delete_error_end_match"=>"删除失败，已结束的赛事不能删除",
            "release_success"=>"发布成功",
            "unrelease_success"=>"取消发布成功",
            "lack_parameter"=>"缺少参数",
            "lack_token"=>"缺少TOKEN",
            "none_data"=>"无数据",
            "system_error"=>"系统错误",
            "none_user"=>"未找到用户",
            "no_file"=>"未获取到文件",
            "none_device_delete"=>"无可删除设备",
            "get_user_info_error"=>"获取用户信息失败",
            "this_day"=>"今天",
            "this_week"=>"本周",
            "this_month"=>"本月",
            "this_year"=>"本年",
            "match_max_count"=>"同一赛事只允许参与两个项目",
            "match_user_sign_exit"=>"已报名",
            "match_user_join_success"=>"报名成功",
            "like_success"=>"关注成功",
            "unlike_success"=>"取消关注成功",
            "like_error"=>"关注失败",
            "unlike_error"=>"取消关注失败",
            "user_not_found"=>"未找到用户，请重新登录",
            "endurance_max"=>"耐力值",
            "circle_count"=>"最高圈数",
            "speed_max"=>"最高转速",
            "duration_time"=>"持续时间",
            "circle_count_unit"=>"千圈",
            "room_number_error"=>"房间号错误",
            "join_pk_group_success"=>"加入成功",
            "join_pk_group_error"=>"加入失败",
            "join_pk_group_full"=>"房间已满",
            "not_found"=>"未找到",
            "pk_is_start"=>"PK 已开始，不可加入",
            "pk_group_choose"=>"请选择队伍",
            "sys_data_error"=>"系统数据错误",
            "success"=>"执行成功",
            "sms_not_found"=>"校验失败，请重新发送",
        ],
        'en-US'=>[
            'wait_release'=>"Wait Release",
            "sign_up"=>"Sign Up",
            "stop"=>"Finish",
            "all"=>"All",
            "create_success"=>"Create Success",
            "update_success"=>"Update Success",
            "delete_success"=>"Delete Success",
            "delete_error"=>"Delete Error",
            "delete_error_release_match"=>"Delete Error，The Match Is Release",
            "delete_error_end_match"=>"Delete Error，The Match Is End",
            "release_success"=>"Release Success",
            "unrelease_success"=>"UnRelease Success",
            "lack_parameter"=>"Lack Parameter",
            "lack_token"=>"Lack Token",
            "none_data"=>"Sorry No Data",
            "system_error"=>"System Error",
            "none_user"=>"Not Found User",
            "no_file"=>"No File",
            "none_device_delete"=>"None Device Can Delete",
            "get_user_info_error"=>"User Not Found",
            "this_day"=>"toDay",
            "this_week"=>"This Week",
            "this_month"=>"This Month",
            "this_year"=>"This Year",
            "match_max_count"=>"Exceeded Max Event",
            "match_user_sign_exit"=>"Already Sign",
            "match_user_join_success"=>"Join Success",
            "like_success"=>"Like Success",
            "unlike_success"=>"Unlike Success",
            "like_error"=>"Like Error",
            "unlike_error"=>"Unlike Error",
            "user_not_found"=>"User Not Found,Please Login Again",
            "endurance_max"=>"Endurance Value",
            "circle_count"=>"Circle Max",
            "speed_max"=>"Speed Max",
            "duration_time"=>"Duration Time",
            "circle_count_unit"=>"Thousand/Circle",
            "room_number_error"=>"Sorry! Number Error",
            "join_pk_group_success"=>"Join Success",
            "join_pk_group_error"=>"Join Error",
            "join_pk_group_full"=>"Group Person Full",
            "not_found"=>"Not Found",
            "pk_is_start"=>"This PK is Start Can't Join",
            "pk_group_choose"=>"Please Choose Group",
            "sys_data_error"=>"System Data Error",
            "success"=>"success",
            "sms_not_found"=>"Check Error,Please Sent Again",
        ]
    ];

    /**
     * @abstract 获取多语种
     * @param $_language_type
     * @param $_language_key
     * @return string
     */
    public static function getLanguage($_language_type,$_language_key){
        if(isset(self::$_language[$_language_type])){

            if(isset(self::$_language[$_language_type][$_language_key])){
                return self::$_language[$_language_type][$_language_key];
            }else{
                return "Sorry！ Not found this language";
            }
        }else{
            return "Sorry！ Not found this language";
        }

    }

}
