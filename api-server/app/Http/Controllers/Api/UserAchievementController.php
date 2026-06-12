<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\TimeFormatController;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UserPkList;
use App\Models\UserPlay;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class UserAchievementController extends Controller
{


    /**
     * @abstract 获取我的成就
     * @param Request $request
     * @return array
     */
    public function postMyAchievement(Request $request){
        $_data = $request->input();

        $_language = $request->header("language")!=null?$request->header("language"):'zh-CN';
        $_user_token = $request->header("token");
        if($_user_token==null){
            return array(
                "code"=>0,
                "msg"=>LanguageController::getLanguage($_language,"lack_token")
            );
        }

        $_achievement_type = isset($_data["type"])?$_data["type"]:'week';

//        开始时间
        $_this_time = isset($_data["stop_date"])?$_data["stop_date"]:date("Y-m-d",time());

        $_arrOfDate = array();
        $_last_day = 0;
        if($_achievement_type=='week'){
            $_last_day = 6;
        }else if($_achievement_type=='month'){
            $_last_day = 29;
        }else if($_achievement_type=='year'){
            $_last_day = 330;
        }

        for ($_i=$_last_day;$_i>=0;$_i--){
            $_date_timestamp = strtotime($_this_time.'-'.$_i.'day');
            $_str_date = date("Y-m-d H:i:s",$_date_timestamp);


            if(!array_key_exists($_str_date,$_arrOfDate)){
                $_arrOfDate[$_str_date] = array(
                    "timestamp"=>$_date_timestamp,
                    "date_format"=>date("m/d",$_date_timestamp),
                    "speed_max"=>0
                );
            }
        }


//        限定时间范围内的最高成绩
        $_circle_count = 0;
        $_duration = 0;
        $_endurance_max = 0;
        $_speed_max = 0;
        $_start_time = strtotime($_this_time.'-'.$_last_day.'day');


        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user",$_user_token),true);

        $_arrOfUserPlay = UserPlay::where([
            "status"=>1,
            "user_id"=>$_usr_user["user_id"]
        ])->whereBetween("start_time",[$_start_time,strtotime($_this_time." 23:59:59")])->select(
            "user_play_id","status","duration","speed_max","circle_count","endurance_max","compare_last","start_time","stop_time","distance","user_id"
            ,"user_pk_list_id"
        )->orderBy("start_time","DESC")->get();


        $_arrOfUserPkListId = array();
        $_arrOfUserPlayKey = array();
        foreach ($_arrOfUserPlay as $value){
            if($value["user_pk_list_id"]!=""){
                array_push($_arrOfUserPkListId,$value["user_pk_list_id"]);
            }
            $_timestamp = strtotime(date("Y-m-d",$value["start_time"])." 00:00:00");

            if(!array_key_exists($_timestamp,$_arrOfUserPlayKey)){
                $_arrOfUserPlayKey[$_timestamp] = array();
            }
            array_push($_arrOfUserPlayKey[$_timestamp],$value);
        }

        foreach (array_keys($_arrOfDate) as $value){
            $_timestamp = strtotime($value);

            $_redis_data = array();
            if(array_key_exists($_timestamp,$_arrOfUserPlayKey)){
                $_redis_data = $_arrOfUserPlayKey[$_timestamp];
            }

            $_node_speed_max = 0;
            foreach ($_redis_data as $node_data){
//                $node_data = json_decode($node,true);
                if(isset($node_data["duration"])){
                    $node_data["circle_count"] = isset($node_data["circle_count"])?$node_data["circle_count"]:0;
                    $_circle_count = $_circle_count<$node_data["circle_count"]?$node_data["circle_count"]:$_circle_count;
                    $_duration = $_duration<$node_data["duration"]?$node_data["duration"]:$_duration;
                    $_endurance_max = $_endurance_max<$node_data["endurance_max"]?$node_data["endurance_max"]:$_endurance_max;
                    $_speed_max = $_speed_max<$node_data["speed_max"]?$node_data["speed_max"]:$_speed_max;
                    $_node_speed_max = $_node_speed_max<$node_data["speed_max"]?$node_data["speed_max"]:$_node_speed_max;
                }
            }
            $_arrOfDate[$value]["speed_max"] = $_node_speed_max;
        }


//        图表数据
        $_chart_data = array_values($_arrOfDate);

//        月成就数据格式化，
        if($_achievement_type=='month'){
            $_new_chart_data = array();
            for ($_i=0;$_i<count($_chart_data);$_i=$_i+7){
//                连续4个7天的数据
                $_node_speed_max = 0;
                for ($_j = 0;$_j<7;$_j++){
                    if(array_key_exists($_j+$_i,$_chart_data)&&$_node_speed_max<$_chart_data[$_j+$_i]["speed_max"]){
                        $_node_speed_max = $_chart_data[$_j+$_i]["speed_max"];
                    }
                }
                array_push($_new_chart_data,array(
                    "date_format"=>$_chart_data[$_i]["date_format"],
                    "timestamp"=>$_chart_data[$_i]["timestamp"],
                    "speed_max"=>$_node_speed_max,
                ));

//                最后两天的数据
                if(($_i+7)>=count($_chart_data)){
                    $_node_speed_max = 0;
                    for ($_j = $_i;$_j<count($_chart_data);$_j++){
                        if(array_key_exists($_j,$_chart_data)&&$_node_speed_max<$_chart_data[$_j]["speed_max"]){
                            $_node_speed_max = $_chart_data[$_j]["speed_max"];
                        }
                    }
                    array_push($_new_chart_data,array(
                        "date_format"=>$_chart_data[count($_chart_data)-1]["date_format"],
                        "timestamp"=>$_chart_data[count($_chart_data)-1]["timestamp"],
                        "speed_max"=>$_node_speed_max,
                    ));
                }
            }
            $_chart_data = $_new_chart_data;
        }

//        年成就数据格式化
        if($_achievement_type=='year'){
            $_new_data = array();
            foreach ($_chart_data as $value){
                $_year_month = date("y/m",$value["timestamp"]);
                if(!array_key_exists($_year_month,$_new_data)){
                    $_new_data[$_year_month] = array();
                }
                array_push($_new_data[$_year_month],$value);
            }

            $_new_chart_data = array();
            foreach ($_new_data as $key=>$value){
                $_node_speed_max = 0;
                foreach ($value as $node){
                    $_node_speed_max = $_node_speed_max<$node["speed_max"]?$node["speed_max"]:$_node_speed_max;
                }
                array_push($_new_chart_data,array(
                    "date_format"=>$key,
                    "timestamp"=>$value[0]["timestamp"],
                    "speed_max"=>$_node_speed_max,
                ));
            }
            $_chart_data = $_new_chart_data;
        }


//        图表数据格式化
        $_format_year = date("y",time());
        foreach ($_chart_data as $key=>$value){
            if($key>0&&$value["speed_max"]<$_chart_data[$key-1]["speed_max"]){
                $value["speed_max"] = $_chart_data[$key-1]["speed_max"];
            }
            if($_achievement_type=='year'){
                $value["date_format"] = date("m月",$value["timestamp"]);
            }

            if($_format_year!=date("y",$value["timestamp"])){
                $_format_year = date("y",$value["timestamp"]);
                if($_achievement_type=='week'||$_achievement_type=="month"){
                    $value["date_format"] = date("y/m/d",$value["timestamp"]);
                }else{
                    $value["date_format"] = date("y/m月",$value["timestamp"]);
                }

            }
            $value["speed_max_format"] = number_format($value["speed_max"]);

            $_chart_data[$key] = $value;
        }


        $_win_num = 0;
        $_arrOfUserPkList = UserPkList::where([
            "status"=>1
        ])->whereIn("user_pk_list_id",$_arrOfUserPkListId)->select("user_group","group_win")->get();
        foreach ($_arrOfUserPkList as $value){
            if($value["user_group"] === $value["group_win"]){
                $_win_num ++;
            }
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "achievement"=>array(
                    "circle_count"=>$_circle_count,
                    "circle_count_format"=>number_format($_circle_count/1000,3),
                    'circle_count_unit'=>LanguageController::getLanguage($_language,"circle_count_unit"),
                    "distance"=>$_circle_count*StaticDataController::$_circle_distance/100,
                    "distance_format"=>number_format($_circle_count*StaticDataController::$_circle_distance/100/1000,3),
                    'distance_unit'=>LanguageController::getLanguage($_language,"distance_unit"),
                    "duration"=>$_duration,
                    "duration_format"=>TimeFormatController::formatSecondToTime($_duration),
                    "endurance_max"=>$_endurance_max,
                    "endurance_max_unit"=>"s",
                    "speed_max"=>$_speed_max,
                    "speed_max_format"=>number_format($_speed_max),
                    "speed_max_unit"=>'rpm',
                    "win_num"=>$_win_num,
                ),
                "chart_data"=>$_chart_data,
            )
        );
    }
}
