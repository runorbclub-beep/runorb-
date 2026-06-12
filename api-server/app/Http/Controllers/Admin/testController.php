<?php

namespace App\Http\Controllers\Admin;

use App\Http\CommonClass\RandName;
use App\Http\CommonClass\Rsa2;
use App\Http\CommonClass\SMSController;
use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\SysHttp;
use App\Http\CommonClass\TimeFormatController;
use App\Http\CommonClass\WeixinHttp;
use App\Http\Controllers\Admin\News\NewsController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\Shake\ShakeController;
use App\Http\Controllers\Api\UserMembersController;
use App\Http\Controllers\Controller;


use App\Http\Controllers\PublicFunction\AliPlayController;
use App\Http\Controllers\PublicFunction\AppFontController;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\UserPlayFunction;
use App\Http\Controllers\PublicFunction\UsrUserController;
use App\Models\AdminUser;
use App\Models\AdminUserRole;
use App\Models\Answers;
use App\Models\CountryBaseLanguage;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\Question;
use App\Models\ShakeGroupUser;
use App\Models\SpiderHis;
use App\Models\SysCountry;
use App\Models\SysMatch;
use App\Models\SysMedal;
use App\Models\SysRankingType;
use App\Models\SysSetting;
use App\Models\SysSex;
use App\Models\SysUserType;
use App\Models\UserAchievement;
use App\Models\UserDevice;
use App\Models\UserGroupAssociated;
use App\Models\UserMedalAssociated;
use App\Models\UserMember;
use App\Models\UserPkList;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Models\UsrUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

use Image;
use SwooleTW\Http\Websocket\Facades\Room;
use SwooleTW\Http\Websocket\Facades\Websocket;


/**
 * Created by PhpStorm.
 * User: pengjl
 * Date: 2020/2/27
 * Time: 14:35
 */
class testController extends Controller
{

    public function home(Request $request)
    {
        return array($request);
    }


    public function test(Request $request)
    {
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", '90f2694d580a5ab4b0018e83018e046f'), true);

        dd($_usr_user);


        Redis::select(14);
//        $groupList = [];
//        $groupId = $request->get('groupId');
        $sysShakeId = $request->get('sysShakeId');
//        $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
//        $allUserRedisList =  Redis::hgetAll($redisUserInfoName);
//        foreach ($allUserRedisList as $key => $allUserRedis) {
//            $userInfo = $allUserRedis ? json_decode($allUserRedis, true) : [];
//            $groupList[$userInfo['shake_group_id']] = $groupList[$userInfo['shake_group_id']] ?? 0;
//            $groupList[$userInfo['shake_group_id']] += $userInfo['distance'] ?? 0;
//        }
//
//        return $groupList;


        $allUserList = [];
        $userList = [];
        $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
        $allUserRedisList = Redis::hgetAll($redisUserInfoName);
        if ($allUserRedisList && is_array($allUserRedisList)) {
            foreach ($allUserRedisList as $key => $allUserRedis) {
                $myInfo = $allUserRedis ? json_decode($allUserRedis, true) : [];
                $allUserList[$myInfo['shake_group_id']][] = $myInfo;
            }
        }
//
//
        return $allUserList;
//        Log::channel('debug')->info('test', ['ddd' => 11]);

//        $path = 'D:\runorb\region.json';
//        $json = file_get_contents($path);
//        $data = json_decode($json, true);
//        $data = $data['internationalCode'] ?? [];
//        $insertData[] = [
//            'id' => 1,
//            'pid' => 0,
//            'name' => '中国',
//            'code' => '100000',
//            'path' => '1'
//        ];
//
//        $total = 1;
//        foreach ( $data as $da) {
//            $total ++;
//            $k = $total;
//            $insertData[] = [
//                'id' => $total,
//                'pid' => 1,
//                'name' => $da['name'],
//                'code' => $da['code'],
//                'path' => '1-' . $total
//            ];
//
//            $childs = $da['child'] ?? [];
//            foreach ($childs as $child) {
//                $total ++;
//                $insertData[] = [
//                    'id' => $total,
//                    'pid' => $k,
//                    'name' => $child['name'] == '市辖区' ? $da['name'] : $child['name'],
//                    'code' => $child['code'],
//                    'path' => '1-' . $k . '-' . $total
//                ];
//            }
//        }
//
//        $a = DB::table('region')->insert($insertData);
//
//        dd($a);
    }


    public function getTest(Request $request)
    {

//        AdminUser::where([
//            "admin_user_id"=>"11571951805927424"
//        ])->update([
//            "password"=>md5("OMADXASDFqesda123")
//        ]);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => StaticDataController::$_server_url,
            "data1" => env("DB_PASSWORD"),
        );

        UserPlay::where([
            "status" => 1,
        ])->where("duration", "<=", 0)->update([
            "status" => 0
        ]);

        return array();

        $_sno = new Snowflake(StaticDataController::$_workId);

        foreach (StaticDataController::$_ranking_title_list as $value) {
            $_data = array(
                "sys_ranking_type_id" => $_sno->nextId(),
                "ranking_title_zh" => $value["title_zh"],
                "ranking_title_en" => $value["title_en"],
                "ranking_type" => $value["type"],
                "ranking_index" => $value["index"],
                "status" => 1
            );

            SysRankingType::create($_data);
        }

        return array();

        $_redis_key = "e2de5dd929006b4786594f6dfc453a29";
        Redis::select(1);
        $_data = json_decode(Redis::hget("usr_user", $_redis_key), true);
        return $_data;

        Redis::select(1);
        $_arrOfUserDetailChange = Redis::getAll("user_detail_change_num");

        return array($_arrOfUserDetailChange);


        $arr = array("a" => 4, "b" => 2, "c" => 8, d => "6");
        uasort($arr, "my_sort");


        $_order_id = "202106291624934462250301";


//        查询订单是否支付
        $_arrOfUserMembers = UserMember::where([
            "order_id" => $_order_id
        ])->select("usr_user_id", "pay_status", "pay_time", "pay_amount")->get();

//        查询微信支付状态
        $_arrOfPayResult = UserMembersController::orderStatus($_order_id);

        if (count($_arrOfUserMembers) > 0 && $_arrOfPayResult["code"] === 1) {
            Redis::select(1);

            $_int_members_index = Redis::get("members_index");
            if ($_int_members_index == null) {
                $_int_members_index = 100000 + UsrUser::where(["is_members" => 1])->select("user_id")->count();
            }

//            用户成为会员
            UsrUser::where(["user_id" => $_arrOfUserMembers[0]["usr_user_id"]])->update([
                "is_members" => 1,
                "members_exptime" => strtotime("+365 day"),
                "members_status" => 1,
                "members_join_time" => time(),
                "share_code" => $_int_members_index
            ]);
        }

        return $_arrOfPayResult;

//        $_arrs = [
//            "55136241091350528",
//            "55135998044016640",
//            "55135714710392832",
//            "55134166412431360",
//            "55134029774589952",
//            "55133874501455872"
//        ];
//
//        $_redis_key = "55115114709258240:55115301414506496:46395305377140736";
//        Redis::select(14);
//        $_arr = Redis::lrange($_redis_key,0,-1);
//
//        $_err_id = "55136241091350528";
//
//        $_x = Redis::lrem($_redis_key,-1,$_err_id);
//
//        $_new_arr = Redis::lrange($_redis_key,0,-1);
//
//
//
//        return array(
//            $_arr,$_new_arr,$_x
//        );


//        REMOTE_ADDR

        return request()->ip();

        return array($_SERVER);

        $_data["birthday"] = "阿斯蒂芬";

        $_this_date = strtotime(date("Y-m-d", time()));
        $_age = floor(($_this_date - strtotime($_data["birthday"])) / (60 * 60 * 24 * 365));

        return date("Y-m-d", strtotime($_data["birthday"]));


        Redis::select(1);

        Redis::hdel("usr_user", "0d3e7000716b4d048c5c9a490ddfb936");

        return array(
            "code" => 1,
        );


        return array();

        $_pk = env("WECHAT_PAY_PRIVATE_KEY", "");  // Private key moved to .env


        $_time = time();
//        $_time = 1623897893;
        $_request_url = "https://api.mch.weixin.qq.com/v3/certificates";
        $_appid = env("WECHAT_PAY_APPID", "");
        $_mchid = env("WECHAT_PAY_MCHID", "");
        $_description = "RunOrb会员年费";
        $_out_trade_no = date("Ymd", $_time) . $_time . rand(100000, 999999);
//        $_out_trade_no = "593BEC0C930BF1AFEB40B4A08C8FB242";
        $_notify_url = "https://api.runorb.us/api/test";
        $_amount = array(
            "total" => 100,
            "currency" => "CNY"
        );
        $_openid = "pengjl";

        $_http_data = array(
            "mchid" => $_mchid,
            "out_trade_no" => $_out_trade_no,
            "appid" => $_appid,
            "description" => $_description,
            "notify_url" => $_notify_url,
            "amount" => $_amount,
            "payer" => $_openid,
        );

        $_sign_str = "GET" . "\n"
            . "/v3/certificates" . "\n"
            . $_time . "\n"
            . $_out_trade_no . "\n"
            . "\n";

        openssl_sign($_sign_str, $raw_sign, $_pk, "sha256WithRSAEncryption");

        $sign = base64_encode($raw_sign);


        $serial_no = "469BC8FB67E68FD8B5D4BD072B74BB6C598740A9";
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf('match="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $_mchid, $_out_trade_no, $_time, $serial_no, $sign);


        $_headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "User-Agent: */*",
            "Authorization: " . $schema . " " . $token
        );

//        return var_dump($_headers);


        //初使化init方法
        $_dingtalkApiCurl = curl_init();
        //指定URL
        curl_setopt($_dingtalkApiCurl, CURLOPT_URL, $_request_url);

        curl_setopt($_dingtalkApiCurl, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($_dingtalkApiCurl, CURLOPT_HEADER, false);
        curl_setopt($_dingtalkApiCurl, CURLOPT_RETURNTRANSFER, true);


//        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
//        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($_dingtalkApiCurl); //执行并获取HTML文档内容
        curl_close($_dingtalkApiCurl); //释放curl句柄


        return json_decode($output, true);


        //初使化init方法
        $_dingtalkApiCurl = curl_init();
        //指定URL
        curl_setopt($_dingtalkApiCurl, CURLOPT_URL, $_request_url);
        //设定请求后返回结果
        curl_setopt($_dingtalkApiCurl, CURLOPT_RETURNTRANSFER, 1);
        //声明使用POST方式来进行发送
        curl_setopt($_dingtalkApiCurl, CURLOPT_POST, 1);
        //发送数据
        curl_setopt($_dingtalkApiCurl, CURLOPT_POSTFIELDS, json_encode($_http_data));
        //忽略证书
//        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYHOST, false);
        //设置header头信息
        curl_setopt($_dingtalkApiCurl, CURLOPT_HTTPHEADER, $_headers);
        //设置超时时间
        curl_setopt($_dingtalkApiCurl, CURLOPT_TIMEOUT, 10);
        //发送请求
        $output = curl_exec($_dingtalkApiCurl);

        //关闭curl
        curl_close($_dingtalkApiCurl);


//        $_result = json_decode(WeixinHttp::post($_http_data,$_request_url),true);

        return array(
            "code" => 1,
            "msg" => "内网接口地址",
            "data" => json_decode($output, true)
        );

    }

    /**
     * @author pengjl
     * @time 2021/5/25 13:43
     * @abstract _计划任务，更新赛事，赛段状态
     */
    public static function runballMatchsStatusChange()
    {
        Log::info("执行赛事定时任务--------------------------");

//        查询所有未结束的赛事，更新状态
        $_arrOfSysMatch = SysMatch::where([
            "status" => 1,
        ])->whereNull("sys_sys_match_id")->where("match_status", "<", 3)
            ->select("sys_match_id", "match_start_time", "match_stop_time", "match_status")->get();


        foreach ($_arrOfSysMatch as $value) {
//            赛事未开始，且状态值不是未开始状态
            if ($value["match_start_time"] >= time() && $value["match_status"] != 1) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 1]);
            }

//            赛事已开始，且状态不是已开始状态
            if ($value["match_start_time"] <= time() && $value["match_stop_time"] > time() && $value["match_status"] != 2) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 2]);
            }

//            赛事已开始，且状态不是已结束状态
            if ($value["match_stop_time"] <= time() && $value["match_status"] != 3) {
                SysMatch::where(["sys_match_id" => $value["sys_match_id"]])->update(["match_status" => 3]);
            }
        }

//        查询所有赛选
        $_arrOfMatchStage = MatchsStage::where([
            "status" => 1,
        ])->where("matchs_stage_status", "<", 3)->select(
            "matchs_stage_id", "matchs_stage_status", "match_stage_start_time", "match_stage_stop_time", "sys_match_id", "sys_sys_match_id"
            , "match_promotion_type", "match_promotion_value"
        )->get();

        $_arr = array();

        foreach ($_arrOfMatchStage as $value) {
//            赛段未开始，且状态值不是未开始状态
            if ($value["match_stage_start_time"] >= time() && $value["matchs_stage_status"] != 1) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 1]);
            }

//            赛段已开始，且状态不是已开始状态
            if ($value["match_stage_start_time"] <= time() && $value["match_stage_stop_time"] > time() && $value["matchs_stage_status"] != 2) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 2]);
            }

//            赛段已开始，且状态不是已结束状态
            if ($value["match_stage_stop_time"] <= time() && $value["matchs_stage_status"] != 3) {
                MatchsStage::where(["matchs_stage_id" => $value["matchs_stage_id"]])->update(["matchs_stage_status" => 3]);

//                赛段结束，计算排名，晋级
                $_status_data = self::MatchStageStop($value["sys_match_id"], $value["matchs_stage_id"], $value["sys_sys_match_id"], $value["match_promotion_type"], $value["match_promotion_value"]);
                array_push($_arr, $_status_data);
            }
        }

        return $_arr;
    }


    /**
     * @author pengjl
     * @time 2021/5/21 20:07
     * @abstract _
     */
    public static function MatchStageStop($_sys_match_id, $_matchs_stage_id, $_sys_sys_match_id, $_match_promotion_type, $_match_promotion_value)
    {
        Log::info("赛事状态执行变更");

//        查询赛段最终成绩
        $_arrOfMatchUser = MatchsStage::where([
            "matchs_stage.matchs_stage_id" => $_matchs_stage_id,
            "matchs_stage.sys_match_id" => $_sys_match_id,
            "matchs_stage.sys_sys_match_id" => $_sys_sys_match_id
        ])->join("matchs_user", function ($join) {
            $join->on("matchs_user.sys_match_id", "=", "matchs_stage.sys_match_id");
        })->select(
            "matchs_user.user_group_finish_time", "matchs_user.user_group_name", "matchs_user.user_group_id"
            , "matchs_user.user_id"
            , "matchs_stage.match_stage_distance", "matchs_user.matchs_user_id"
        )->get();

        $_arrOfMatchUserKey = array();
        foreach ($_arrOfMatchUser as $value) {
            $_arrOfMatchUserKey[$value["matchs_user_id"]] = $value;
        }

        $_arrOfMatchUserGradeValue = MatchsUserGrade::where([
            "matchs_stage_id" => $_matchs_stage_id,
        ])->whereIn("matchs_user_id", array_keys($_arrOfMatchUserKey))->select(
            "matchs_user_id", "matchs_user_grade_id", "is_group", "distinct_grade"
        )->get();

        foreach ($_arrOfMatchUserGradeValue as $value) {
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["matchs_user_grade_id"] = $value["matchs_user_grade_id"];
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["is_group"] = $value["is_group"];
            $_arrOfMatchUserKey[$value["matchs_user_id"]]["distinct_grade"] = $value["distinct_grade"];
        }

        $_arrOfMatchUserGrade = array_values($_arrOfMatchUserKey);

//        如果当前赛事没有用户报名，直接结束
        if (count($_arrOfMatchUserGrade) == 0) {
            return array();
        }

        Redis::select(14);
        foreach ($_arrOfMatchUserGrade as $value) {

            if ($value["is_group"] == 1) {
                $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $value["user_group_id"];
            } else {
                $_redis_key = $_sys_match_id . ":" . $_matchs_stage_id . ":" . $value["user_id"];
            }

            $_matchsPlayList = Redis::lrange($_redis_key, 0, -1);


            $_distance = 0;
            if (count($_matchsPlayList) > 0) {
                foreach ($_matchsPlayList as $node) {
                    $_playData = json_decode(Redis::get($node), true);

                    if ($value["user_group_finish_time"] == null) {
                        $_distance += isset($_playData["duration"]) ? $_playData["duration"] : 0;
                    } else {

//                    在结束前的运动数据
                        if (isset($_playData["stop_time"]) && $_playData["stop_time"] <= $value["user_group_finish_time"]) {
                            $_distance += isset($_playData["duration"]) ? $_playData["duration"] : 0;
                        }

//                    结束后运动的数据，取开始到结束间的时间
                        if (isset($_playData["stop_time"]) && $_playData["stop_time"] > $value["user_group_finish_time"]) {
                            $_dd = isset($_playData["start_time"]) ? $value["user_group_finish_time"] - $_playData["start_time"] : 0;
                            $_distance += $_dd;
                        }
                    }

                }
            }

//            更新用户成绩
            $_MatchsUserGradeQuery = MatchsUserGrade::where([
                "matchs_stage_id" => $_matchs_stage_id,
                "matchs_user_id" => $value["matchs_user_id"]
            ]);

            if ($value["is_group"] == 1) {
                $_MatchsUserGradeQuery = $_MatchsUserGradeQuery->where([
                    "user_group_id" => $value["user_group_id"],
                ]);

            } else {
                $_MatchsUserGradeQuery = $_MatchsUserGradeQuery->where([
                    "user_id" => $value["user_id"],
                ]);
            }

            $_MatchsUserGradeQuery->update(["match_grade" => $_distance]);

        }


//        区分已完成比赛和未完成比赛的用户
        $_finish_group = array();
        $_unfinish_group = array();
        foreach ($_arrOfMatchUserGrade as $value) {
            if ($value["user_group_finish_time"] != null) {
                array_push($_finish_group, $value["matchs_user_id"]);
            }

            if ($value["user_group_finish_time"] == null) {
                array_push($_unfinish_group, $value["matchs_user_id"]);
            }
        }


//        已完成比赛的用户排名
        $_arrOfMatchUserGradeFinish = MatchsUserGrade::where([
            "matchs_stage_id" => $_matchs_stage_id
        ])->whereIn("matchs_user_id", $_finish_group)->select(
            "matchs_user_grade_id", "match_grade", "user_id", "user_group_id", "matchs_user_id", "is_group"
        )->orderBy("match_grade", "ASC")->get();


        $_arrOfMatchUserGradeKey = array();
//        变更赛段最终排名
        foreach ($_arrOfMatchUserGradeFinish as $key => $value) {
            MatchsUserGrade::where([
                "matchs_user_grade_id" => $value["matchs_user_grade_id"]
            ])->update([
                "match_ranking" => $key + 1
            ]);

            $_arrOfMatchUserGradeKey[$value["matchs_user_id"]] = $value;
        }


//        未完成比赛用户排名
        if (count($_unfinish_group) > 0) {
            //        查询赛段最终成绩
            $_arrOfMatchUserGradeUnFinish = MatchsUserGrade::where([
                "matchs_stage_id" => $_matchs_stage_id
            ])->whereIn("matchs_user_id", $_unfinish_group)->select(
                "matchs_user_grade_id", "distinct_grade", "user_id", "user_group_id", "matchs_user_id", "is_group"
            )->orderBy("distinct_grade", "ASC")->get();

//        变更赛段最终排名
            foreach ($_arrOfMatchUserGradeUnFinish as $key => $value) {
                MatchsUserGrade::where([
                    "matchs_user_grade_id" => $value["matchs_user_grade_id"]
                ])->update([
                    "match_ranking" => $key + 1 + count($_finish_group)
                ]);
            }
        }


//        晋级的用户报名ID
        $_arrOfmatchUserId = array();
//        晋级的分界名次
        $_arrOfMatchUserIndex = 0;
//        赛段指定人数晋级
        if ($_match_promotion_type == 0) {
            $_arrOfMatchUserIndex = $_match_promotion_value;
        } else if ($_match_promotion_type == 1) {
//            赛段按比例晋级
            $_arrOfMatchUserIndex = round(count($_arrOfMatchUserGrade) * $_match_promotion_value / 100);
        }


        for ($_i = 0; $_i < $_arrOfMatchUserIndex; $_i++) {
            if ($_i < count($_arrOfMatchUserGradeFinish)) {
                array_push($_arrOfmatchUserId, $_arrOfMatchUserGradeFinish[$_i]["matchs_user_id"]);
            }
        }


//        禁止所有用户继续下一赛段
        MatchsUser::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->update(["stage_pass" => 0]);

//        允许晋级用户继续参赛，
        MatchsUser::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->whereIn("matchs_user_id", $_arrOfmatchUserId)->update(["stage_pass" => 1, "user_group_finish_time" => null]);


        $_next_stage_id = "";
        //        查询下一赛段
        $_arrOfNextMatchStage = MatchsStage::where([
            "status" => 1,
            "sys_match_id" => $_sys_match_id,
            "sys_sys_match_id" => $_sys_sys_match_id
        ])->select("matchs_stage_id", "match_stage_start_time")->orderBy("match_stage_start_time", "ASC")->get();

        $_arrOfNextMatchStageKey = array();
        $_arrOfNextMatchStageTime = array();
        $_this_index = 0;
        foreach ($_arrOfNextMatchStage as $key => $value) {
            array_push($_arrOfNextMatchStageTime, $value["match_stage_start_time"]);
            $_arrOfNextMatchStageKey[$value["match_stage_start_time"]] = $value;

            if ($_matchs_stage_id == $value["matchs_stage_id"]) {
                $_this_index = $key + 1;
            }
        }

        if ($_this_index >= count($_arrOfNextMatchStageKey)) {
            return array(
                "code" => 1,
                "msg" => "没有下一赛段"
            );
        }

        $_next_stage_id = $_arrOfNextMatchStageKey[$_arrOfNextMatchStageTime[$_this_index]]["matchs_stage_id"];

//        为用户创建下一赛段成绩
        $_sno = new Snowflake(StaticDataController::$_workId);
        foreach ($_arrOfmatchUserId as $value) {
            $_arrOfMatchsStageGradeData = array(
                "matchs_user_grade_id" => $_sno->nextId(),
                "matchs_stage_id" => $_next_stage_id,
                "matchs_user_id" => $value,
                "match_grade" => 9999999999,
                "match_ranking" => 0,
                "user_id" => $_arrOfMatchUserGradeKey[$value]["user_id"],
                "is_group" => $_arrOfMatchUserGradeKey[$value]["is_group"],
                "user_group_id" => $_arrOfMatchUserGradeKey[$value]["user_group_id"],
            );

            MatchsUserGrade::create($_arrOfMatchsStageGradeData);
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfmatchUserId
        );
    }


    /**
     * @abstract 新增徽章
     * @return array
     */
    public function newMedal()
    {
        $_arrOfData = array(1000000, 5000000, 10000000, 40075700, 384401000);

        $_file_path = "medal_image/medal/累计距离/distance_lv";
        $_sno = new Snowflake(StaticDataController::$_workId);
        $_sys_sys_medal_id = $_sno->nextId();
        $_arrOfmedalData = array(
            "sys_medal_id" => $_sys_sys_medal_id,
            "status" => 1,
            "user_medal_name_cn" => "累计距离",
            "user_medal_name_en" => "All Distance"
        );
        SysMedal::create($_arrOfmedalData);
        for ($_i = 0; $_i < count($_arrOfData); $_i++) {
            $_arrOfmedalDataNode = array(
                "sys_medal_id" => $_sno->nextId(),
                "sys_sys_medal_id" => $_sys_sys_medal_id,
                "status" => 1,
                "user_medal_name_cn" => "累计距离",
                "user_medal_name_en" => "All Distance",
                "description_cn" => "累计运动距离达到 " . ($_arrOfData[$_i] / 1000) . " km",
                "description_en" => "All Distance More Than " . ($_arrOfData[$_i] / 1000) . " km",
                "medal_conditions" => json_encode(array("distance", $_arrOfData[$_i])),
                "level_name" => "level." . ($_i + 1),
                "medal_image" => $_file_path . ($_i + 1) . ".png",
                "medal_image_active" => $_file_path . ($_i + 1) . "_active.png",
            );
            SysMedal::create($_arrOfmedalDataNode);
        }


        $_arrOfSysMedal = SysMedal::where(["status" => 1])->select(
            "sys_medal_id", "sys_sys_medal_id", "user_medal_name_cn", "user_medal_name_en", "description_cn"
            , "description_en", "medal_conditions", "level_name", "medal_image", "medal_image_active"
            , "description_en", "user_medal_name_en"
        )->orderBy("sys_medal_id", "ASC")->get();

        $_arrOfRedis_medal = array();
        foreach ($_arrOfSysMedal as $value) {
            $_node = array(
                "sys_medal_id" => $value["sys_medal_id"],
                "sys_sys_medal_id" => $value["sys_sys_medal_id"],
                "user_medal_name_cn" => $value["user_medal_name_cn"],
                "user_medal_name_en" => $value["user_medal_name_en"],
                "description_cn" => $value["description_cn"],
                "description_en" => $value["description_en"],
                "medal_conditions" => json_decode($value["medal_conditions"], true),
                "level_name" => $value["level_name"],
                "medal_image" => $value["medal_image"],
                "medal_image_active" => $value["medal_image_active"],
                "status" => 1,
            );

            if ($value["sys_sys_medal_id"] == null) {
                $_node["node_medal"] = array();
                $_arrOfRedis_medal[$value["sys_medal_id"]] = $_node;
            } else {
                array_push($_arrOfRedis_medal[$value["sys_sys_medal_id"]]["node_medal"], $_node);
            }
        }

        Redis::select(1);
        foreach ($_arrOfRedis_medal as $key => $value) {
            Redis::hset("sys_medal", $key, json_encode($value));
        }

        return array();
    }

    public function getTests()
    {

        $_result = Http::get("https://www.baidu.com");
        return $_result;
    }

    public function getDyOrder()
    {
        $_data = array(
            "startdate" => "2021-02-12 00:00:00",
            "enddate" => "2021-02-12 23:59:59",
            "sign_type" => "RSA2",
//            "shopcode"=>"mklghjqzyd",
            "platform" => "DY",
            "timestamp" => time() * 1000,
            "pageno" => "1",
            "pagesize" => "10",
        );
        $_url = "http://119.136.21.188:7777/api/orderhistory/getmodifiedtime";

        $_rsa2 = new Rsa2();
        ksort($_data);

        // 生成规范化请求字符串
        $_str_data = '';
        foreach ($_data as $key => $value) {
            $_str_data .= '&' . $key . '=' . $value;
        }
        $_str_data = substr($_str_data, 1);

        $strSign = $_rsa2->createSign($_str_data);

        $_data["sign"] = $strSign;

        $header = array(
            "Content-Type:application/json",
            "Authorization:Bearer Y2xpZW50OnNlY3JldA==",
        );

//        return json_encode($_data);

        $ch = curl_init();
        //请求地址
        curl_setopt($ch, CURLOPT_URL, $_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //开启post请求
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //post请求文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_data));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $data = curl_exec($ch);

        curl_close($ch);
        return $data;

    }

}


