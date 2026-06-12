<?php


namespace App\Http\Controllers\Admin\Website;

use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\UserAchievement;
use App\Models\UsrUser;
use App\Models\WebMatchRanking;
use App\Models\WebMatchRankingDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function foo\func;

class WebMatchRankingController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/6/19 17:56
     * @abstract _管理后台查询官网榜单列表
     */
    public function postWebMatchRankingList(){
        $_data = request()->input();

        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;

        $_rank_type = isset($_data["rank_type"])?$_data["rank_type"]:"";


        $_arrOfWebMatchRankingQuery = WebMatchRanking::where([
            "status"=>1,
        ])->select(
            "ranking_title as match_ranking_title","web_match_ranking_id","ranking_type","start_time","stop_time","ranking_time_type", "ranking_title_en"
        )->orderBy("start_time","DESC");

        if(isset($_data["rank_type"])){
            $_arrOfWebMatchRankingQuery = $_arrOfWebMatchRankingQuery->where(["ranking_type"=>$_rank_type]);
        }

        $_arrOfWebMatchRankingCount = $_arrOfWebMatchRankingQuery->count();
        $_arrOfWebMatchRanking = $_arrOfWebMatchRankingQuery->skip($_offset)->take($_limit)->get();



        $_arrOfWebMatchRankingId = array();
        $_arrOfWebMatchRankingKey = array();
        foreach ($_arrOfWebMatchRanking as $key=>$value){
            array_push($_arrOfWebMatchRankingId,$value["web_match_ranking_id"]);

            $value["ranking_title"] = StaticDataController::$_ranking_title_list[$value["ranking_type"]];
            $value["user_count"] = 0;
            $value["start_date"] = date("Y-m-d",$value["start_time"]);
            $value["stop_date"] = date("Y-m-d",$value["stop_time"]);

            switch ($value["ranking_time_type"]){
                case "week":
                    $value["ranking_time_title"] = "周榜";
                    break;
                case "month":
                    $value["ranking_time_title"] = "月榜";
                    break;
                case "quarter":
                    $value["ranking_time_title"] = "季榜";
                    break;
                case "year":
                    $value["ranking_time_title"] = "年榜";
                    break;
            }

            switch ($value["ranking_type"]){
                case "max_speed":
                    $value["input_unit"] = "s";
                    break;
                case "onemin":
                    $value["input_unit"] = "m";
                    break;
                case "exponent":
                    $value["input_unit"] = "";
                    break;
                case "marathon":
                    $value["input_unit"] = "s";
                    break;
            }

            $_arrOfWebMatchRankingKey[$value["web_match_ranking_id"]] = $value;
        }

//        查询每个榜单对应的入榜人数
        $_arrOfWebMatchRankingDetail = DB::table("web_match_ranking_detail")->where("status","=",1)
            ->whereIn("web_match_ranking_id",$_arrOfWebMatchRankingId)->selectRaw("count(usr_user_id) as count,web_match_ranking_id")
            ->groupBy("web_match_ranking_id")->get();

        foreach ($_arrOfWebMatchRankingDetail as $value){
            $_arrOfWebMatchRankingKey[$value->web_match_ranking_id]["user_count"] = $value->count;
        }


        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>$_arrOfWebMatchRankingCount,
                "list"=>array_values($_arrOfWebMatchRankingKey)
            )
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/19 17:56
     * @abstract 榜单列表新增，编辑
     */
    public function postWebMatchRankingAdd(){
        $_data = request()->input();

        Redis::select(1);
        $_token_key = "admin_user_token:".request()->header("token");
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';


        if(!isset($_data["ranking_title"]) || !isset($_data["ranking_type"]) || !isset($_data["start_time"]) || !isset($_data["stop_time"]) || !isset($_data["ranking_time_type"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebMantchRankingData = array(
            "ranking_title"=>$_data["ranking_title"],
            "ranking_type"=>$_data["ranking_type"],
            "ranking_time_type"=>$_data["ranking_time_type"],
            "start_time"=>strtotime($_data["start_time"]),
            "stop_time"=>strtotime($_data["stop_time"]),
            "status"=>1,
            "ranking_title_en"=>$_data["ranking_title_en"]
        );

        if(isset($_data["web_match_ranking_id"])){
            $_arrOfWebMantchRankingData["updated_uid"] = $_admin_user["admin_user_id"];
//            编辑
            WebMatchRanking::where([
                "web_match_ranking_id"=>$_data["web_match_ranking_id"]
            ])->update($_arrOfWebMantchRankingData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功"
            );
        }else{
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfWebMantchRankingData["web_match_ranking_id"] = $_sno->nextId();
            $_arrOfWebMantchRankingData["created_uid"] = $_admin_user["admin_user_id"];

            WebMatchRanking::create($_arrOfWebMantchRankingData);


            return array(
                "code"=>1,
                "msg"=>"创建成功"
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/6/19 17:56
     * @abstract 榜单列表删除
     */
    public function postWebMatchRankingDelete(){
        $_data = request()->input();

        Redis::select(1);
        $_token_key = "admin_user_token:".request()->header("token");
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';


        if(!isset($_data["web_match_ranking_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebMatchRanking::where([
            "web_match_ranking_id"=>$_data["web_match_ranking_id"]
        ])->update([
            "status"=>0,
            "created_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"删除成功"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/19 18:04
     * @abstract _查询具体绑定那的用户人数
     */
    public function postWebMatchRankingDetail(){

        $_data = request()->input();
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';


        if(!isset($_data["web_match_ranking_id"]) || !isset($_data["rank_type"])){
            return SystemErrorController::paramtersError($_language);
        }

        return self::WebMatchRankingDetail($_data["web_match_ranking_id"],$_data["rank_type"]);


    }

    /**
     * @author pengjl
     * @time 2021/6/19 18:30
     * @abstract _公共方法，获取榜单列表
     */
    public static function WebMatchRankingDetail($_web_match_ranking_id,$_rank_type){

        $_arrOfUserListQuery = WebMatchRankingDetail::where([
            "status"=>1,
            "web_match_ranking_id"=>$_web_match_ranking_id,
        ])->select(
            "web_match_ranking_detail_id","web_match_ranking_id","user_name","usr_user_id","user_img","value","value_format","unit","join_time"
        );


//        摇跑全马榜单，根据用时 升序
        if($_rank_type == "marathon"){
            $_arrOfUserListQuery = $_arrOfUserListQuery->orderBy("value","ASC");
        }else{
            $_arrOfUserListQuery = $_arrOfUserListQuery->orderBy("value","DESC");
        }

        $_arrOfUserList = $_arrOfUserListQuery->get();

        foreach ($_arrOfUserList as $key=>$value){
            $value["user_img"] = StaticDataController::$_server_url."/".$value["user_img"];
            $value["join_time_format"] = date("Y-m-d H:i:s",$value["join_time"]);
            $value["index"] = $key+1;
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfUserList),
                "list"=>$_arrOfUserList
            )
        );

    }


    /**
     * @author pengjl
     * @time 2021/6/19 18:11
     * @abstract _用户添加到指定榜单
     */
    public function postWebMatchRankingUserAdd(){

        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["user_id"]) || !isset($_data["web_match_ranking_id"]) || !isset($_data["ranking_type"]) || !isset($_data["value"]) || !isset($_data["join_time"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebMatchRanking = WebMatchRankingDetail::where([
            "usr_user_id"=>$_data["user_id"],
            "status"=>1,
            "web_match_ranking_id"=>$_data["web_match_ranking_id"]
        ])->select("web_match_ranking_detail_id")->get();

        if(count($_arrOfWebMatchRanking)>0){
            return array(
                "code"=>0,
                "msg"=>"用户已在当前榜单"
            );
        }


        $_arrOfUsrUser = UsrUser::where([
            "user_id"=>$_data["user_id"]
        ])->select("user_name","user_img")->get();

        $_sno = new Snowflake(StaticDataController::$_workId);

        $_arrOfWebMatchRankingDetail = array(
            "web_match_ranking_detail_id"=>$_sno->nextId(),
            "status"=>1,
            "web_match_ranking_id"=>$_data["web_match_ranking_id"],
            "usr_user_id"=>$_data["user_id"],
            "user_name"=>$_arrOfUsrUser[0]["user_name"],
            "user_img"=>$_arrOfUsrUser[0]["user_img"],
            "value"=>$_data["value"],
            "join_time"=>strtotime($_data["join_time"]),
        );

        switch ($_data["ranking_type"]){
            case "max_speed":
                $_arrOfWebMatchRankingDetail["unit"] = "rpm";
                $_arrOfWebMatchRankingDetail["value_format"] = $_arrOfWebMatchRankingDetail["value"];
                break;
            case "onemin":
                $_arrOfWebMatchRankingDetail["unit"] = "m";
                $_arrOfWebMatchRankingDetail["value_format"] = $_arrOfWebMatchRankingDetail["value"];
                break;
            case "exponent":
                $_arrOfWebMatchRankingDetail["unit"] = "";
                $_arrOfWebMatchRankingDetail["value_format"] = $_arrOfWebMatchRankingDetail["value"];
                break;
            case "marathon":
                $_arrOfWebMatchRankingDetail["unit"] = "";
                $_arrOfWebMatchRankingDetail["value_format"] = RankController::timeFormat($_arrOfWebMatchRankingDetail["value"]);
                break;
        }

        WebMatchRankingDetail::create($_arrOfWebMatchRankingDetail);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/21 11:09
     * @abstract _用户榜单数据变更
     */
    public function postWebMatchRankingUserUpdate(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["web_match_ranking_detail_id"]) || !isset($_data["rank_type"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebRankingDetailData = array();

        if (isset($_data["user_name"])){
            $_arrOfWebRankingDetailData["user_name"] = $_data["user_name"];
        }
        if (isset($_data["value"])){
            $_arrOfWebRankingDetailData["value"] = $_data["value"];
        }
        if (isset($_data["join_time"])){
            $_arrOfWebRankingDetailData["join_time"] = strtotime($_data["join_time"]);
        }

        $_arrOfWebRankingDetailData["updated_uid"] = $_admin_user["admin_user_id"];

        $_arrOfWebRankingDetailData["join_time"] = strtotime($_data["join_time"]);
        switch ($_data["rank_type"]){
            case "max_speed":
                $_arrOfWebRankingDetailData["value_format"] = $_arrOfWebRankingDetailData["value"];
                break;
            case "onemin":
                $_arrOfWebRankingDetailData["value_format"] = $_arrOfWebRankingDetailData["value"];
                break;
            case "exponent":
                $_arrOfWebRankingDetailData["value_format"] = $_arrOfWebRankingDetailData["value"];
                break;
            case "marathon":
                $_arrOfWebRankingDetailData["value_format"] = RankController::timeFormat($_arrOfWebRankingDetailData["value"]);
                break;
        }

        WebMatchRankingDetail::where([
            "web_match_ranking_detail_id"=>$_data["web_match_ranking_detail_id"]
        ])->update($_arrOfWebRankingDetailData);


        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/19 18:15
     * @abstract _用户从榜单删除
     */
    public function postWebMatchRankingUserDelete(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["web_match_ranking_detail_id"])){
            return SystemErrorController::paramtersError($_language);
        }

        WebMatchRankingDetail::where([
            "web_match_ranking_detail_id"=>$_data["web_match_ranking_detail_id"]
        ])->update([
            "status"=>0,
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>"success"
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/30 19:17
     * @abstract _榜单添加用户，查询用户列表
     */
    public function postWebMatchRankingUserList(){
        $_data = request()->input();

        $_token_key = "admin_user_token:".request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);
//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        if(!isset($_data["ranking_type"]) || !isset($_data["start_time"]) || !isset($_data["stop_time"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_start_time = $_data["start_time"];
        $_stop_time = strtotime(date("Y-m-d",$_data["stop_time"])." 23:59:59");


        $_user_type_id = isset($_data["sys_user_type_id"])?$_data["sys_user_type_id"]:"";
        $_user_members_status = isset($_data["members_status"])?$_data["members_status"]:"";

        $_arrOfUserPlayQuery = UsrUser::where([
            "user_play.status"=>1
        ])->join("user_play",function ($join){
            $join->on("usr_user.user_id","=","user_play.user_id");
        })->join("sys_user_type",function ($join){
            $join->on("sys_user_type.sys_user_type_id","=","usr_user.sys_user_type_id");
        })->join("sys_sex",function ($join){
            $join->on("sys_sex.sys_sex_id","=","usr_user.sys_sex_id");
        });

//        用户类型
        if($_user_type_id != ""){
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("usr_user.sys_user_type_id","=",$_user_type_id);
        }
//        会员状态
        if($_user_members_status != ""){
            $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("usr_user.members_status","=",$_user_members_status);
        }

//        数据类型筛选
        switch ($_data["ranking_type"]){
            case "onemin":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("user_play.exponent_molecular",">",0);
                break;
            case "exponent":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("user_play.exponent",">",0);
                break;
            case "marathon":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->where("user_play.marathon",">",0);
                break;
        }

//        时间筛选
        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->whereBetween("user_play.start_time",array($_start_time,$_stop_time));

        $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->select(
            "usr_user.user_id","usr_user.user_name","usr_user.user_img","usr_user.phone","usr_user.real_name","usr_user.members_status"
            ,"usr_user.address"
            ,"user_play.speed_max","user_play.speed_max","user_play.exponent_molecular","user_play.exponent","user_play.marathon"
            ,"user_play.start_time","sys_user_type.user_type_name","sys_sex.sex_name"
        );

        // 数据类型筛选
        switch ($_data["ranking_type"]){
            case "max_speed":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play.speed_max","DESC");
                break;
            case "onemin":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play.exponent_molecular","DESC");
                break;
            case "exponent":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play.exponent","DESC");
                break;
            case "marathon":
                $_arrOfUserPlayQuery = $_arrOfUserPlayQuery->orderBy("user_play.marathon","ASC");
                break;
        }

//        先查询所有运动数据
        $_arrOfUserPlay = $_arrOfUserPlayQuery->get();

        $_arrOfUserPlayKey = array();
        foreach ($_arrOfUserPlay as $value){
            if(!array_key_exists($value["user_id"],$_arrOfUserPlayKey)){
                $_members_title = "";
                switch ($value["members_status"]){
                    case -1:
                        $_members_title = "未申请";
                        break;
                    case 0:
                        $_members_title = "待审核";
                        break;
                    case 1:
                        $_members_title = "会员";
                        break;
                    case 2:
                        $_members_title = "已驳回";
                        break;
                }

                $_arrOfUserPlayKey[$value["user_id"]] = array(
                    "user_id"=>$value["user_id"],
                    "user_name"=>$value["user_name"],
                    "user_img"=>StaticDataController::$_server_url."/".$value["user_img"],
                    "phone"=>$value["phone"],
                    "real_name"=>$value["real_name"],
                    "start_time"=>$value["start_time"],
                    "user_type_name"=>$value["user_type_name"],
                    "sex_name"=>$value["sex_name"],
                    "members_title"=>$_members_title,
                    "ranking_value"=>0,
                    "index"=>0,
                    "ranking_value_format"=>"",
                    "unit"=>"",
                );
            }

            switch ($_data["ranking_type"]){
                case "max_speed":
                    if($value["speed_max"] > $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"]){
                        $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"] =  $value["speed_max"];
                        $_arrOfUserPlayKey[$value["user_id"]]["start_time"] =  $value["start_time"];
                    }
                    break;
                case "onemin":
                    if($value["exponent_molecular"] > $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"]){
                        $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"] =  $value["exponent_molecular"];
                        $_arrOfUserPlayKey[$value["user_id"]]["start_time"] =  $value["start_time"];
                    }
                    break;
                case "exponent":
                    if($value["exponent"] > $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"]){
                        $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"] =  $value["exponent"];
                        $_arrOfUserPlayKey[$value["user_id"]]["start_time"] =  $value["start_time"];
                    }
                    break;
                case "marathon":
//                    马拉松耗时，用时短优先
                    if($value["marathon"] < $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"]){
                        $_arrOfUserPlayKey[$value["user_id"]]["ranking_value"] =  $value["marathon"];
                        $_arrOfUserPlayKey[$value["user_id"]]["start_time"] =  $value["start_time"];
                    }
                    break;
            }
        }

//        数据排序
        $_arrOfUserRankValueKey = array();
        foreach ($_arrOfUserPlayKey as $key=>$value){
            $_arrOfUserRankValueKey[$key] = $value["ranking_value"];
        }


//        马拉松 比用时，降序
        if($_data["ranking_type"] == "marathon"){
            asort($_arrOfUserRankValueKey);
        }else{
            arsort($_arrOfUserRankValueKey);
        }



//        格式化返回数据
        $_arrOfReturn = array();
        foreach ($_arrOfUserRankValueKey as $key=>$value){
            $_int_len = count($_arrOfReturn);

            if($_int_len <= 100){
                $_return_value = $_arrOfUserPlayKey[$key];
                $_return_value["index"] = $_int_len+1;

                $_unit = "";
                $_ranking_value_format = "";
                $_time = "";
                $_format = "Y-m-d H:i:s";
                switch ($_data["ranking_type"]){
                    case "max_speed"://个人最高速度
                        $_unit = "rpm";
                        $_ranking_value_format = (string)$_return_value["ranking_value"];
                        $_time = date($_format,$_return_value["start_time"]);
                        break;
                    case "exponent"://摇跑指数
                        $_unit = "";
                        $_ranking_value_format = (string)$_return_value["ranking_value"];
                        $_time = date($_format,$_return_value["start_time"]);
                        break;
                    case "onemin"://个人1分钟，m
                        $_unit = "m";
                        $_ranking_value_format = (string)$_return_value["ranking_value"];
                        $_time = date($_format,$_return_value["start_time"]);
                        break;
                    case "marathon"://个人马拉松
                        $_unit = "";
                        $_ranking_value_format = (string)RankController::timeFormat($_return_value["ranking_value"]);;
                        $_time = date($_format,$_return_value["start_time"]);
                        break;
                }

                $_return_value["unit"] = $_unit;
                $_return_value["ranking_value_format"] = $_ranking_value_format;
                $_return_value["start_time"] = $_time;


                $_arrOfReturn[$_int_len] = $_return_value;
            }
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfReturn),
                "list"=>$_arrOfReturn
            )
        );
    }

}
