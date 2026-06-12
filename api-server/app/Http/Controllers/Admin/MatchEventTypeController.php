<?php


namespace App\Http\Controllers\Admin;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\Match;
use App\Models\MatchsEventType;
use App\Models\MatchsStageRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MatchEventTypeController extends Controller
{

    /**
     * @abstract 查询比赛项目列表
     * @param Request $request
     * @return array
     */
    public function postMatchEventTypeList(Request $request){
        $_data = $request->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;


        $_arrOfMatchEventTypeQuery = MatchsEventType::where([
            "status"=>1,
        ]);

        //        如果存在搜索筛选
        if($_search!=''){
            $_arrOfMatchEventTypeQuery = $_arrOfMatchEventTypeQuery->where(function($query) use ($_search){
                $query->where("match_events_type_title","like",'%'.$_search."%");
//                    ->orwhere("store.store_number","like",'%'.$_search."%")
//                    ->orwhere("performance.performance_id","=",$_search)
//                    ->orwhere("store.store_id","=",$_search);
            });
        }
        $_arrOfMatchEventTypeQuery = $_arrOfMatchEventTypeQuery->select(
            "matchs_event_type_id","created_time","match_events_type_title","match_events_distance_value"
        )->orderBy("matchs_event_type_id","ASC");

        $_arrOfMatchEventTypeCount = $_arrOfMatchEventTypeQuery->get();
        $_arrOfMatchEventType = $_arrOfMatchEventTypeQuery->skip($_offset)->take($_limit)->get();

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfMatchEventTypeCount),
                "list"=>$_arrOfMatchEventType,
                "param"=>$_data
            )
        );
    }

    /**
     * @abstract 创建比赛项目类型
     * @param Request $request
     * @return array
     */
    public function postMatchEventTypeAdd(Request $request){
        $_data = $request->input();
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

//        生成主键
        $_snowflake = new Snowflake(StaticDataController::$_workId);
        $_rangeId = $_snowflake->nextId();

        $_arrOfMatchsEventsTypeData = array(
            "match_events_type_title"=>$_data["match_events_title"],
            "match_events_distance_value"=>$_data["match_events_distance_value"],
            "status"=>1,
            "matchs_event_type_id"=>$_rangeId,
            "created_time"=>time(),
            "created_uid"=>$_admin_user["admin_user_id"],
        );

        MatchsEventType::create($_arrOfMatchsEventsTypeData);

        return array(
            "code"=>1,
            "msg"=>"创建成功",
        );
    }

    /**
     * @abstract 删除比赛项目
     * @param Request $request
     * @return array
     */
    public function postMatchEventTypeDelete(Request $request){
        $_data = $request->input();

        if(!isset($_data["matchs_event_type_id"])){
            return array(
                "code"=>0,
                "msg"=>"缺少参数"
            );
        }
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

        $_arrOfMatch = Match::where([
            "status"=>1,
            "matchs_event_type_id"=>$_data["matchs_event_type_id"]
        ])->select("matchs_id")->get();

        if(count($_arrOfMatch)>0){
            return array(
                "code"=>0,
                "msg"=>"已被赛事引用，不可删除"
            );
        }else{
            MatchsEventType::where([
                "matchs_event_type_id"=>$_data["matchs_event_type_id"]
            ])->update([
                "updated_time"=>time(),
                "updated_uid"=>$_admin_user["admin_user_id"],
                "status"=>0
            ]);

            return array(
                "code"=>1,
                "msg"=>"删除成功"
            );
        }


    }

}
