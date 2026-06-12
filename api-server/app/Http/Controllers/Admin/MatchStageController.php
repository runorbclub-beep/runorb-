<?php


namespace App\Http\Controllers\Admin;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\AdminUser;
use App\Models\MatchsStageRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * @author pengjl
 * Class MatchStageController
 * @package App\Http\Controllers\Admin
 */
class MatchStageController extends Controller
{

    /**
     * @abstract 查询赛段晋级规则列表
     * @param Request $request
     * @return array
     */
    public function postMatchStageRulesList(Request $request){
        $_data = $request->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;


        $_arrOfMatchStageRuleQuery = MatchsStageRule::where([
            "status"=>1,
        ]);

        //        如果存在搜索筛选
        if($_search!=''){
            $_arrOfMatchStageRuleQuery = $_arrOfMatchStageRuleQuery->where(function($query) use ($_search){
                $query->where("match_rules_title","like",'%'.$_search."%");
//                    ->orwhere("store.store_number","like",'%'.$_search."%")
//                    ->orwhere("performance.performance_id","=",$_search)
//                    ->orwhere("store.store_id","=",$_search);
            });
        }
        $_arrOfMatchStageRuleQuery = $_arrOfMatchStageRuleQuery->select(
            "matchs_stage_rules_id","created_time","match_promotion_type","match_promotion_value","match_rules_title"
        )->orderBy("matchs_stage_rules_id","ASC");

        $_arrOfMatchStageRuleCount = $_arrOfMatchStageRuleQuery->get();
        $_arrOfMatchStageRule = $_arrOfMatchStageRuleQuery->skip($_offset)->take($_limit)->get();



        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfMatchStageRuleCount),
                "list"=>$_arrOfMatchStageRule,
                "param"=>$_data
            )
        );
    }

    /**
     * @abstract 创建赛段晋级规则
     * @param Request $request
     * @return array
     */
    public function postMatchStageRulesAdd(Request $request){
        $_data = $request->input();

        if(!isset($_SERVER["HTTP_TOKEN"])){
            return array(
                "code"=>0,
                "msg"=>"缺少token"
            );
        }
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);


        $_arrOfMatchsStageRulesData = array(
            "match_promotion_type"=>$_data["martch_promotion_type_value"],
            "match_promotion_value"=>$_data["match_promotion_value"],
            "match_rules_title"=>$_data["match_rules_title"],
            "status"=>1,
        );

//        编辑
        if(isset($_data['matchs_stage_rules_id'])){
            $_arrOfMatchsStageRulesData["updated_uid"] = $_admin_user["admin_user_id"];
            MatchsStageRule::where([
                "matchs_stage_rules_id"=>$_data['matchs_stage_rules_id']
            ])->update($_arrOfMatchsStageRulesData);

            return array(
                "code"=>1,
                "msg"=>"编辑成功",
            );
        }else{

            $_snowflake = new Snowflake(StaticDataController::$_workId);
            $_rangeId = $_snowflake->nextId();
            $_arrOfMatchsStageRulesData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfMatchsStageRulesData["matchs_stage_rules_id"] = $_rangeId;
            MatchsStageRule::create($_arrOfMatchsStageRulesData);

            return array(
                "code"=>1,
                "msg"=>"创建成功",
            );
        }
    }

    /**
     * @abstract 查询赛段规则详情
     * @param Request $request
     * @return array
     */
    public function postMatchStageRulesInfo(Request $request){
        $_data = $request->input();

        if(!isset($_data["matchs_stage_rules_id"])){
            return array(
                "code"=>0,
                "msg"=>"缺少参数"
            );
        }

        $_matchs_stage_rules_id = $_data["matchs_stage_rules_id"];

        $_arrOfMatchStageRule = MatchsStageRule::where([
            "matchs_stage_rules_id"=>$_matchs_stage_rules_id
        ])->select("match_promotion_type","match_promotion_value","match_rules_title","matchs_stage_rules_id")->get();

        if(count($_arrOfMatchStageRule)==0){
            return array(
                "code"=>0,
                "msg"=>"未查询到规则",
                "matchs_stage_rules_id"=>$_matchs_stage_rules_id
            );
        }else{
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfMatchStageRule[0]
            );
        }
    }

    /**
     * @abstract 删除赛段晋级规则
     * @param Request $request
     * @return array
     */
    public function postMatchStageRulesDelete(Request $request){
        $_data = $request->input();
        if(!isset($_data["matchs_stage_rules_id"])){
            return array(
                "code"=>0,
                "msg"=>"缺少参数"
            );
        }
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);


        MatchsStageRule::where([
            "matchs_stage_rules_id"=>$_data["matchs_stage_rules_id"]
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
