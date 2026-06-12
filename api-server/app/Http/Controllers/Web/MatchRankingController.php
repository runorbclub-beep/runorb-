<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\WebMatchRanking;
use App\Models\WebMatchRankingDetail;

class MatchRankingController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/6/24 10:35
     * @abstract _查询榜单类型列表
     */
    public function getRankingTypeList(){
        $_arrOfReturnList = RankingController::rankingTypeList();
        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_arrOfReturnList
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/22 15:28
     * @abstract _官网查询赛事榜单
     */
    public function getMatchRanking(){
        $_data = request()->input();
        $_language = request()->header("language")!=null?request()->header("language"):'zh-CN';

        if(!isset($_data["rank_type"]) || !isset($_data["rank_time_type"])){
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfWebMatchRanking = WebMatchRanking::where([
            "status"=>1,
            "ranking_type"=>$_data["rank_type"],
            "ranking_time_type"=>$_data["rank_time_type"],
        ])->select("web_match_ranking_id","ranking_title", "ranking_title_en")->orderBy("start_time","DESC")->get();

        if(count($_arrOfWebMatchRanking)==0){
            return array(
                "code"=>1,
                "msg"=>"无数据",
                "data"=>array(
                    "rank_title"=>"",
                    "ranking_title_en"=>"",
                    "list"=>array()
                )
            );
        }

        $_web_match_ranking_id = $_arrOfWebMatchRanking[0]["web_match_ranking_id"];


        $_arrOfWebMatchRankingDetailQuery = WebMatchRankingDetail::where([
            "status"=>1,
            "web_match_ranking_id"=>$_web_match_ranking_id,
        ])->select("user_name","user_img","value_format as value","unit","join_time");


        if($_data["rank_type"] == "marathon"){
            $_arrOfWebMatchRankingDetailQuery = $_arrOfWebMatchRankingDetailQuery->orderBy("value","ASC");
        }else{
            $_arrOfWebMatchRankingDetailQuery = $_arrOfWebMatchRankingDetailQuery->orderBy("value","DESC");
        }

        $_arrOfWebMatchRankingDetail = $_arrOfWebMatchRankingDetailQuery->get();


//        array_push($_return_arr,array(
//            "index"=>$_offset+$key+1,
//            "user_id"=>$value["user_id"],
//            "user_img"=>$_api_type==StaticDataController::$_server_url."/".$value["user_img"],
//            "user_name"=>$value["user_name"],
//            "value"=>$_value,
//            "unit"=>$_unit,
//            "time"=>$_time
//        ));


        foreach ($_arrOfWebMatchRankingDetail as $key=>$value){
            $value["index"] = $key+1;
            $value["user_img"] = StaticDataController::$_server_url."/".$value["user_img"];
            $value["time"] = date("Y-m-d H:i:s");

            $_arrOfWebMatchRankingDetail[$key] = $value;
        }

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "rank_title"=>$_arrOfWebMatchRanking[0]["ranking_title"],
                "ranking_title_en"=>$_arrOfWebMatchRanking[0]["ranking_title_en"],
                "list"=>$_arrOfWebMatchRankingDetail
            )
        );


    }


}
