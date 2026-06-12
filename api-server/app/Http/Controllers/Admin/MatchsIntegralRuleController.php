<?php


namespace App\Http\Controllers\Admin;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\MatchsEventType;
use App\Models\MatchsIntegralRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redis;

class MatchsIntegralRuleController extends Controller
{

    /**
     * @abstract 创建积分获取规则
     * @param Request $request
     * @return array
     */
    public function postMatchIntegralRuleAdd(Request $request){
        $_data = $request->input();

        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

        //        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        $_matchs_integral_rule_id = isset($_data['matchs_integral_rule_id'])?$_data["matchs_integral_rule_id"]:"";

        $_arrOfMatchsIntegralRuleData = array(
            "integral_rules_title"=>$_data["integral_rules_title"],
            "max_integral"=>$_data["max_integral"],
            "sub_integral"=>$_data["sub_integral"],
            "get_integral_type"=>$_data["get_integral_type"],
            "get_integral_value"=>$_data["get_integral_value"],
            "status"=>1,
        );

        if($_matchs_integral_rule_id!=""){
//            编辑
            $_arrOfMatchsIntegralRuleData["updated_uid"] = $_admin_user["admin_user_id"];
            MatchsIntegralRule::where([
                "matchs_integral_rule_id"=>$_matchs_integral_rule_id
            ])->update($_arrOfMatchsIntegralRuleData);
            return array(
                "code"=>1,
                "msg"=>LanguageController::getLanguage($_language,"update_success")
            );
        }else{

            $_snowflake = new Snowflake(StaticDataController::$_workId);
            $_rangeId = $_snowflake->nextId();
            $_arrOfMatchsIntegralRuleData["matchs_integral_rule_id"] = $_rangeId;
            $_arrOfMatchsIntegralRuleData["created_uid"] = $_admin_user["admin_user_id"];

            MatchsIntegralRule::create($_arrOfMatchsIntegralRuleData);

            return array(
                "code"=>1,
                "msg"=>LanguageController::getLanguage($_language,"create_success")
            );
        }

    }

    /**
     * @abstract 查询比赛项目列表
     * @param Request $request
     * @return array
     */
    public function postMatchIntegralRuleList(Request $request){
        $_data = $request->input();

        $_search = isset($_data["search"])?$_data["search"]:'';
        $_page = isset($_data["page"])?$_data["page"]:1;
        $_limit = isset($_data["limit"])?$_data["limit"]:10;
        $_offset = ($_page-1)*$_limit;


        $_arrOfMatchsIntegralRuleQuery = MatchsIntegralRule::where([
            "status"=>1,
        ]);

        //        如果存在搜索筛选
        if($_search!=''){
            $_arrOfMatchsIntegralRuleQuery = $_arrOfMatchsIntegralRuleQuery->where(function($query) use ($_search){
                $query->where("integral_rules_title","like",'%'.$_search."%");
//                    ->orwhere("store.store_number","like",'%'.$_search."%")
//                    ->orwhere("performance.performance_id","=",$_search)
//                    ->orwhere("store.store_id","=",$_search);
            });
        }
        $_arrOfMatchsIntegralRuleQuery = $_arrOfMatchsIntegralRuleQuery->select(
            "matchs_integral_rule_id","integral_rules_title","max_integral","sub_integral","get_integral_type","get_integral_value"
        )->orderBy("matchs_integral_rule_id","ASC");

        $_arrOfMatchsIntegralRuleCount = $_arrOfMatchsIntegralRuleQuery->get();
        $_arrOfMatchsIntegralRule = $_arrOfMatchsIntegralRuleQuery->skip($_offset)->take($_limit)->get();

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "count"=>count($_arrOfMatchsIntegralRuleCount),
                "list"=>$_arrOfMatchsIntegralRule,
            )
        );
    }

    /**
     * @abstract 查询积分规则详情
     * @param Request $request
     * @return array
     */
    public function postMatchIntegralRuleInfo(Request $request){
        $_data = $request->input();

        $_matchs_integral_rule_id = isset($_data["matchs_integral_rule_id"])?$_data["matchs_integral_rule_id"]:'';

        $_arrOfMatchsIntegralRule = MatchsIntegralRule::where([
            "matchs_integral_rule_id"=>$_matchs_integral_rule_id
        ])->select(
            "matchs_integral_rule_id","integral_rules_title","max_integral","sub_integral","get_integral_type","get_integral_value"
        )->get();

        if(count($_arrOfMatchsIntegralRule)==1){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>$_arrOfMatchsIntegralRule[0]
            );
        }

        return array(
            "code"=>1,
            "msg"=>"error"
        );

    }

    /**
     * @abstract 删除积分规则列表
     * @param Request $request
     * @return array
     */
    public function postMatchIntegralRuleDelete(Request $request){
        $_data = $request->input();
        $_token_key = "admin_user_token:".$request->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key),true);

//        语言
        $_language = isset($_data['language'])?$_data['language']:'zh-CN';

        $_matchs_integral_rule_id = isset($_data['matchs_integral_rule_id'])?$_data["matchs_integral_rule_id"]:"";

        MatchsIntegralRule::where([
            "matchs_integral_rule_id"=>$_matchs_integral_rule_id
        ])->update([
            "status"=>0,
            "updated_uid"=>$_admin_user["admin_user_id"]
        ]);

        return array(
            "code"=>1,
            "msg"=>LanguageController::getLanguage($_language,"delete_success")
        );
    }


}
