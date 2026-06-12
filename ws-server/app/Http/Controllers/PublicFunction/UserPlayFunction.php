<?php


namespace App\Http\Controllers\PublicFunction;


class UserPlayFunction
{


    /**
     * @abstract 用户停止运动后，格式化运动数据
     * @param array $_user_play
     * @param array $_last_play
     * @param int $_start_time
     * @param int $_interval
     * @return array
     */
    public static function StopPlayFormatData(array $_user_play,array $_last_play,int $_start_time,int $_interval){

//        获取区间数组
        $_arr_section = array();
        $_speed_section = StaticDataController::$_speed_section;
        for ($_i=0;$_i<count($_speed_section);$_i++){
            if($_i>0){
                $_key = $_speed_section[$_i-1]."-".$_speed_section[$_i];
                $_arr_section[$_key] = array(
                    "start_section"=>$_speed_section[$_i-1],
                    "stop_section"=>$_speed_section[$_i],
                    "speed_detail"=>array(),
                    "section_duration"=>0
                );
            }
        }

        $_circle_detail = $_user_play["circle_detail"];
        $_speed_detail = $_user_play["speed_detail"];


        $_user_play_detail = array();
        $_max_speed = 0;
        $_circle_count = $_circle_detail[count($_circle_detail)-1];
        $_arr_endurance_max = [];

//        循环圈数数组，
        for ($_i=0;$_i<count($_speed_detail);$_i++){
//            当前速度 rpm  （当前圈数-上一秒圈数）*60秒*(1000 / 时间间隔 毫秒)，
//            $_speed = ($_circle_detail[$_i]-$_circle_detail[$_i-1])*60*(1000/$_interval);
            $_speed = $_speed_detail[$_i];
            if($_max_speed<$_speed){
                $_max_speed = $_speed;
            }
            if($_speed>10000){
                array_push($_arr_endurance_max,$_speed);
            }

            $_moment = $_user_play["start_time"]*1000+$_interval*$_i;
            array_push($_user_play_detail,array(
                "moment"=>$_moment,
                "speed"=>$_speed,
            ));

            foreach ($_arr_section as $key=>$node){
                if($_speed>=$node["start_section"]&&$_speed<$node["stop_section"]){
                    array_push($node["speed_detail"],$_speed);
                }
                $_arr_section[$key] = $node;
            }
        }


        $_user_play["duration"] = round(count($_circle_detail)*$_interval/1000-1,3);
        $_user_play["speed_max"] = $_max_speed;
        $_user_play["circle_count"] = $_circle_count;
        $_user_play["endurance_max"] = round(count($_arr_endurance_max)*$_interval/1000,3);
        $_user_play["stop_time"] = $_start_time+$_user_play["duration"];
        $_user_play["user_play_detail"] = $_user_play_detail;
        unset($_user_play["circle_detail"]);

        $_max_section_duration = 0;
        foreach ($_arr_section as $key=>$value){
            $value["section_duration"] = round(count($value["speed_detail"])*$_interval/1000);
            unset($value["speed_detail"]);
            $_arr_section[$key] = $value;
            if($_max_section_duration<$value["section_duration"]){
                $_max_section_duration = $value["section_duration"];
            }
        }

        foreach ($_arr_section as $key=>$value){
            $_percentage = $_max_section_duration>0?round($value["section_duration"]/$_max_section_duration*100):$_max_section_duration;
            $value["percentage"] = $_percentage;
            $_arr_section[$key] = $value;
        }
        $_user_play["section_duration"] = $_arr_section;


        $_compare_last = 0;
        if(isset($_last_play["speed_max"])){
            if($_user_play["speed_max"]<$_last_play["speed_max"]){
                $_compare_last = -1;
            }
            if($_user_play["speed_max"]>$_last_play["speed_max"]){
                $_compare_last = 1;
            }
        }
        $_user_play["compare_last"] = $_compare_last;

//        距离，米
        $_user_play["distance"] = $_user_play["circle_count"]*StaticDataController::$_circle_distance/10;

        return $_user_play;
    }


    /**
     * @abstract 获取日期时间范围
     * @param String $_start_date
     * @param String $_type
     * @return array
     */
    public static function getLastDate(String $_start_date,String $_type){
        $_arrOfDate = array();
        $_year = date("Y",strtotime($_start_date));


        switch ($_type){
            case "day":
                for ($_i=0;$_i<60;$_i++){
                    $_start_time = strtotime($_start_date." -".$_i."day");
                    $_format_title = date("m/d",$_start_time);
                    if($_year!=date("Y",$_start_time)){
                        $_year = date("Y",$_start_time);
                        $_format_title = date("y/m/d",$_start_time);
                    }
                    array_push($_arrOfDate,array(
                        "start_time"=>$_start_time,
                        "stop_time"=>$_start_time,
                        "format_title"=>$_format_title
                    ));
                }
                break;
            case 'week':
                for ($_i=0;$_i<70;$_i=$_i+7){
                    $_start_time = strtotime($_start_date." -".$_i."day");
                    $w = strftime('%u', $_start_time);//获取是周几的数字1-7
                    $_stop_time = strtotime(date("Y-m-d",$_start_time)." +".(7-$w)." day");

                    $_start_time = $_stop_time-6*86400;

                    $_format_title = date("m/d",$_start_time)."-".date("m/d",$_stop_time);
                    if($_year!=date("Y",$_start_time)){
                        $_year = date("Y",$_start_time);
                        $_format_title = date("y/m/d",$_start_time)."-".date("m/d",$_stop_time);
                    }
                    array_push($_arrOfDate,array(
                        "start_time"=>$_start_time,
                        "stop_time"=>$_stop_time,
                        "format_title"=>$_format_title
                    ));
                }
                break;
            case "month":
                for ($_i=0;$_i<12;$_i++){
                    $_start_time = strtotime(date("Y-m",strtotime($_start_date))."-01 -".$_i." month");

                    $_stop_time = strtotime(date("Y-m-d",$_start_time).' +1 month -1 day');

                    $_format_title = date("m月",$_start_time);
                    if($_year!=date("Y",$_start_time)){
                        $_year = date("Y",$_start_time);
                        $_format_title = date("y/m月",$_start_time);
                    }
                    array_push($_arrOfDate,array(
                        "start_time"=>$_start_time,
                        "stop_time"=>$_stop_time,
                        "format_title"=>$_format_title
                    ));
                }
                break;
            case "year":
                $_sys_start_time = 2021;
                $_last_year_length = (int)date("Y",time())-$_sys_start_time;

                for ($_i=0;$_i<$_last_year_length+1;$_i++){
                    $_start_time = strtotime(date("Y",strtotime($_start_date))."-01-01 -".$_i." year");

                    $_stop_time = strtotime(date("Y-m-d",$_start_time).' +1 year -1 day');

                    $_format_title = date("m月",$_start_time);
                    if($_year!=date("Y",$_start_time)){
                        $_year = date("Y",$_start_time);
                        $_format_title = date("y/m月",$_start_time);
                    }
                    array_push($_arrOfDate,array(
                        "start_time"=>$_start_time,
                        "stop_time"=>$_stop_time,
                        "format_title"=>$_format_title
                    ));
                }
                break;
        }

        return $_arrOfDate;
    }


    /**
     * @abstract 获取起止日期范围内的完整周范围
     * @param String $_start_date
     * @param String $_stop_date
     * @return array
     */
    public static function getWeekDateRange(String $_start_date,String $_stop_date){
        $_start_time = strtotime($_start_date);

        $_arrOfDate = array($_start_time);

        $_sub_day = round((strtotime($_stop_date)-strtotime($_start_date))/86400);
        $_sub_day = $_sub_day>0?$_sub_day+1:0;

        for ($_i=6;$_i<=$_sub_day;$_i+=7){
            array_push($_arrOfDate,$_start_time+$_i*86400);
        }

        $_arrOfDateKey = array();
        for ($_i=1;$_i<count($_arrOfDate);$_i++){
            $_key = date("Y_m_d",$_arrOfDate[$_i-1]+($_i==1?0:86400))."-".date("Y_m_d",$_arrOfDate[$_i]);
            $_arrOfDateKey[$_key] = array(
                "start_date"=>$_arrOfDate[$_i-1]+($_i==1?0:86400),
                "stop_date"=>$_arrOfDate[$_i],
            );
        }

        return $_arrOfDateKey;
    }

}
