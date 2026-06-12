<?php


namespace App\Http\Controllers\Admin;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\Match;
use App\Models\MatchsType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MatchsTypeController extends Controller
{

    /**
     * @abstract 新增赛事类型
     * @param Request $request
     * @return array
     */
    public function postMatchTypeAdd(Request $request){
        $_data = $request->input();

        if(!isset($_data["matchs_type_title"]) || trim($_data["matchs_type_title"])==""){
            return array(
                "code"=>0,
                "msg"=>"缺少参数"
            );
        }
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

        $_arrOfMatchsTypeData = array(
            "matchs_type_title"=>$_data["matchs_type_title"],
            "status"=>1
        );

//        编辑
        if(isset($_data['matchs_type_id'])){
            $_arrOfMatchsTypeData["updated_uid"] = $_admin_user["admin_user_id"];
            MatchsType::where([
                "matchs_type_id"=>$_data['matchs_type_id']
            ])->update($_arrOfMatchsTypeData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功",
            );
        }else{
            $_snowflake = new Snowflake(StaticDataController::$_workId);
            $_rangeId = $_snowflake->nextId();

            $_arrOfMatchsTypeData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfMatchsTypeData["matchs_type_id"] = $_rangeId;
            MatchsType::create($_arrOfMatchsTypeData);

            return array(
                "code"=>1,
                "msg"=>"创建成功",
            );
        }
    }


    /**
     * @abstract 查询赛事类型列表
     * @param Request $request
     * @return array
     */
    public function postMatchTypeList(Request $request){
        $_data = $request->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;
        $_type = isset($_data['type'])?$_data["type"]:'page';


        $_arrOfMatchTypeQuery = MatchsType::where([
            "status"=>1,
        ]);

        //        如果存在搜索筛选
        if($_search!=''){
            $_arrOfMatchTypeQuery = $_arrOfMatchTypeQuery->where(function($query) use ($_search){
                $query->where("matchs_type_title","like",'%'.$_search."%");
//                    ->orwhere("store.store_number","like",'%'.$_search."%")
//                    ->orwhere("performance.performance_id","=",$_search)
//                    ->orwhere("store.store_id","=",$_search);
            });
        }
        $_arrOfMatchTypeQuery = $_arrOfMatchTypeQuery->select(
            "matchs_type_id","matchs_type_title"
        )->orderBy("matchs_type_id","ASC");

        $_arrOfMatchTypeCount = $_arrOfMatchTypeQuery->get();

        if($_type=='page'){
            $_arrOfMatchType = $_arrOfMatchTypeQuery->skip($_offset)->take($_limit)->get();
        }else{
            $_arrOfMatchType = $_arrOfMatchTypeQuery->get();
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfMatchTypeCount),
                "list"=>$_arrOfMatchType,
            )
        );
    }

    /**
     * @abstract 删除赛事类型
     * @param Request $request
     * @return array
     */
    public function postMatchTypeDelete(Request $request){
        $_data = $request->input();

        if(!isset($_data["matchs_type_id"])){
            return array(
                "code"=>0,
                "msg"=>"缺少参数"
            );
        }
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

        $_arrOfMatch = MatchsType::where([
            "status"=>1,
            "matchs_type_id"=>$_data["matchs_type_id"]
        ])->select("matchs_type_id")->get();


        if(count($_arrOfMatch)>0){
            return array(
                "code"=>0,
                "msg"=>"已被赛事引用，不可删除"
            );
        }else{
            MatchsType::where([
                "matchs_type_id"=>$_data["matchs_type_id"]
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
