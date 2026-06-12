<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Admin\Website\WebMatchRankingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\RankController;
use App\Models\UserAchievement;
use App\Models\UsrUser;
use App\Models\WebMatchRanking;
use App\Models\WebMatchRankingDetail;
use Illuminate\Support\Facades\Redis;

/**
 * @author pengjl
 * @time 2021/5/9 17:34
 * Class RankController
 * @package App\Http\Controllers\Web
 * @abstract _官网榜单接口
 */
class RankListController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/9 17:36
     * @abstract _摇跑指数榜单
     */
    public function getRankList(){
        Redis::select(1);

        $_data = request()->input();
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        if(!isset($_data["rank_type"])){
            return array(
                "code"=>0,
                "msg"=>LanguageController::getLanguage($_language,"lack_parameter")
            );
        }

        $_redis_key = "";
        switch ($_data["rank_type"]){
            case "exponent"://摇跑指数榜单
                $_redis_key = "rank_list_exponent";
                break;
            case "max_speed"://个人最高速度
                $_redis_key = "rank_list_max_speed";
                break;
            case "thrmin"://个人三分钟数据
                $_redis_key = "rank_list_self_thrmin";
                break;
            case "onemin"://个人1分钟数据
                $_redis_key = "rank_list_self_onemin";
                break;
            case "marathon"://个人全马
                $_redis_key = "rank_list_self_marathon";
                break;
        }


//        从redis获取数据s
        $_arrOfRedisList = Redis::zrevrange($_redis_key,0,100);

//        如果redis没有缓存数据，查询数据库存入
        if($_arrOfRedisList ==null){
//            返回redis数据结构
            $_arrOfRedisList = RankController::UserAchievement($_redis_key);

        }

//        根据Redis榜单信息，获取用户基本信息及排名
        $_arrOfUserRankList = RankController::UserRankList($_arrOfRedisList,$_redis_key);

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_arrOfUserRankList
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/9 17:36
     * @abstract _摇跑指数榜单
     */
    public function getRankListV2(){
        Redis::select(1);

        $_data = request()->input();
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

//        用户年龄类型，0：成年榜，1：青年榜
        $_user_age_type = isset($_data["user_age_type"])?$_data["user_age_type"]:0;

        //个人与团队
        $_user_type = $_data['user_type'] ?? 0;

        //城市
        $_address = $_data['address'] ?? '';

        return RankController::UserAchivementV2($_user_age_type,$_data["rank_type"], $_user_type, $_address,"web",1,100,"");
    }


    /**
     * @author pengjl
     * @time 2021/6/19 18:21
     * @abstract _获取赛事榜单列表
     */
    public function getMatchRankingList(){

        $_data = request()->input();
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        if(!isset($_data["rank_type"])){
            return array(
                "code"=>0,
                "msg"=>LanguageController::getLanguage($_language,"lack_parameter")
            );
        }

        $_arrOfWebMatchRanking = WebMatchRanking::where([
            "status"=>1,
            "ranking_type"=>$_data["rank_type"]
        ])->select("web_match_ranking_id","start_time")->orderBy("start_time","DESC")->get();

        if(count($_arrOfWebMatchRanking)==0){
            return array(
                "code"=>1,
                "msg"=>"success",
                "data"=>array(
                    "count"=>0,
                    "list"=>array()
                )
            );
        }

        $_web_match_ranking_id = $_arrOfWebMatchRanking[0]["web_match_ranking_id"];

        return WebMatchRankingController::WebMatchRankingDetail($_web_match_ranking_id,$_data["rank_type"]);
    }
}
