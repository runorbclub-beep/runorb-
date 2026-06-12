<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysRankingType;
use App\Models\UserAchievement;
use App\Models\UserPlay;
use Illuminate\Support\Facades\Redis;

class RankingController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/6/16 14:48
     * @abstract _榜单类型排序
     */
    public function postRankingTypeList()
    {

        $_arrOfReturnList = self::rankingTypeList();
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfReturnList
        );

    }

    public static function rankingTypeList()
    {
        $_arrOfReturnList = SysRankingType::where([
            "status" => 1,
        ])->select(
            "ranking_title_zh as title_zh", "ranking_title_en as title_en", "ranking_type as type", "ranking_index as index", "ranking_rule_zh as rule_zh", "ranking_rule_en as rule_en"
        )->orderBy("ranking_index", "ASC")->get();

        return $_arrOfReturnList;


        Redis::select(1);

        $_ranking_type_list = json_decode(Redis::get("ranking_type_list"), true);

        if ($_ranking_type_list == "") {
            $_ranking_type_list = array_values(StaticDataController::$_ranking_title_list);
            Redis::set("ranking_type_list", json_encode($_ranking_type_list));
        }

        $_arrOfReturnList = array();
        foreach ($_ranking_type_list as $key => $value) {
            $value["id"] = time() + $key;
            $_arrOfReturnList[$value["index"]] = $value;
        }

        ksort($_arrOfReturnList);


        return array_values($_arrOfReturnList);

    }


    /**
     * @author pengjl
     * @time 2021/6/2 17:41
     * @abstract _app 榜单
     */
    public function postRunballRanking()
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

//        字段缺失验证
        if (!isset($_data["ranking_type"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserAchievementQuery = UserAchievement::where([
            "user_achievement.status" => 1
        ])->join("usr_user", function ($join) {
            $join->on("user_achievement.user_id", "=", "usr_user.user_id");
        });

        if ($_data["ranking_type"] == "exponent") {
            $_arrOfUserAchievementQuery = $_arrOfUserAchievementQuery->where("user_achievement.runball_exponent", ">", 0)->select(
                "user_achievement.runball_exponent as value", "usr_user.user_id", "usr_user.user_name", "usr_user.user_img"
            )
                ->orderBy("user_achievement.runball_exponent", "DESC");
        } else if ($_data["ranking_type"] == "molecular") {
            $_arrOfUserAchievementQuery = $_arrOfUserAchievementQuery->select(
                "user_achievement.exponent_molecular as value", "usr_user.user_id", "usr_user.user_name", "usr_user.user_img"
            )
                ->orderBy("user_achievement.exponent_molecular", "DESC");
        }

        $_arrOfUserAchievementCount = $_arrOfUserAchievementQuery->get();
        $_arrOfUserAchievement = $_arrOfUserAchievementQuery->skip($_offset)->take($_limit)->get();

        $_my_ranking = 0;
        foreach ($_arrOfUserAchievementCount as $key => $value) {
            if ($value["user_id"] == $_usr_user["user_id"]) {
                $_my_ranking = $key + 1;
            }
        }

        foreach ($_arrOfUserAchievement as $key => $value) {
            $value["user_img"] = StaticDataController::$_server_url . "/" . $value["user_img"];
            $value["index"] = $_offset + $key + 1;

            if ($_data["ranking_type"] == "molecular") {
                $value["value"] = round($value["value"] / 1000, 2) . "km";
            } else {
                $value["value"] = (string)$value["value"];
            }
        }
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => count($_arrOfUserAchievementCount),
                "list" => $_arrOfUserAchievement,
                "my_ranking" => $_my_ranking
            )
        );

    }

    /**
     * @author pengjl
     * @time 2021/6/2 17:41
     * @abstract _app 榜单 新版
     */
    public function postRunballRankingV2()
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;

//        用户年龄类型，0：成年榜，1：青年榜
//        $_user_age_type = isset($_data["user_age_type"]) ? $_data["user_age_type"] : 0;
        if ($_data["user_age_type"] == 2 || $_data["user_age_type"] == -1){
            $_data["user_age_type"] = '';
        }
        $_user_age_type = $_data["user_age_type"] ?? '';

        //个人与团队
        if ($_data['user_type'] == 2){
            $_data['user_type'] = '';
        }
        $_user_type = $_data['user_type'] ?? '';

        //城市
        $_address = $_data['address'] ?? '';

        //性别
        $_sys_sex_id = $_data['sys_sex_id'] ?? '';

        $_title = $_data['title'] ?? '';

        $_return_arr = RankController::UserAchivementV3($_user_age_type, $_data["ranking_type"], $_user_type, $_address, "app", $_page, $_limit, $_usr_user["user_id"],$_sys_sex_id,$_title);

        return $_return_arr;

    }

    /**
     * @author pengjl
     * * @time 2021/6/2 17:41
     * * @abstract _app 榜单 今日最高转速
     */
    public function postRankingTodayHighestSpeed()
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;

//        用户年龄类型，0：成年榜，1：青年榜
//        $_user_age_type = isset($_data["user_age_type"]) ? $_data["user_age_type"] : 0;
        if ($_data["user_age_type"] == 2 || $_data["user_age_type"] == -1){
            $_data["user_age_type"] = '';
        }
        $_user_age_type = $_data["user_age_type"] ?? '';

        //个人与团队
        if ($_data['user_type'] == 2){
            $_data['user_type'] = '';
        }
        $_user_type = $_data['user_type'] ?? '';

        //城市
        $_address = $_data['address'] ?? '';

        //性别
        $_sys_sex_id = $_data['sys_sex_id'] ?? '';

        $_title = $_data['title'] ?? '';

        $_day_time = $_data['day_time'] ?? date('Y-m-d');

        $_return_arr = RankController::RankingTodayHighestSpeed($_user_age_type, $_user_type, $_address, $_page, $_limit, $_usr_user["user_id"],$_sys_sex_id,$_day_time,$_title);
        
        if (!$_return_arr['data']['list']) {
            $_return_arr = RankController::UserAchivementV3($_user_age_type, 'max_speed', $_user_type, $_address, "app", $_page, $_limit, $_usr_user["user_id"],$_sys_sex_id,$_title);
        }

        return $_return_arr;
    }

    /**
     * @author pengjl
     * * @time 2021/6/2 17:41
     * * @abstract _app 榜单 今日累计距离
     */
    public function postRankingAccumulatedDistanceToday()
    {
        Redis::select(1);
        $_data = request()->input();

        $_language = request()->header("language") != null ? request()->header("language") : 'zh-CN';
        $_user_token = request()->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;

//        用户年龄类型，0：成年榜，1：青年榜
//        $_user_age_type = isset($_data["user_age_type"]) ? $_data["user_age_type"] : 0;
        if ($_data["user_age_type"] == 2 || $_data["user_age_type"] == -1){
            $_data["user_age_type"] = '';
        }
        $_user_age_type = $_data["user_age_type"] ?? '';

        //个人与团队
        if ($_data['user_type'] == 2){
            $_data['user_type'] = '';
        }
        $_user_type = $_data['user_type'] ?? '';

        //城市
        $_address = $_data['address'] ?? '';

        //性别
        $_sys_sex_id = $_data['sys_sex_id'] ?? '';

        $_title = $_data['title'] ?? '';

        $_day_time = $_data['day_time'] ?? date('Y-m-d');

        $_return_arr = RankController::RankingAccumulatedDistanceToday($_user_age_type, $_user_type, $_address, $_page, $_limit, $_usr_user["user_id"],$_sys_sex_id,$_day_time,$_title);

        return $_return_arr;
    }
}
