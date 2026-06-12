<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\WxPayController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysMatch;
use App\Models\SysMember;
use App\Models\UserMember;
use App\Models\UsrUser;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserMembersController extends Controller
{

    /**
     * @author pengjl
     * @time 2021/6/30 17:33
     * @abstract _官网通过手机号查询用户信息
     */
    public function getUserInfo()
    {
        $_data = request()->input();
        //        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';


        if (!isset($_data["phone"]) || !isset($_data["phone_prefix"])) {
            return SystemErrorController::paramtersError($_language);
        }
        $_arrOfUser = UsrUser::where([
            "phone" => $_data["phone"],
            "phone_prefix" => $_data["phone_prefix"]
        ])->select("user_id", "user_name", "real_name", "birthday", "address_detail", "address", "address_json", "wechart_id", "sys_sex_id")->get();

        if (count($_arrOfUser) == 0) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "website_user_not_fount")
            );
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfUser[0]
        );
    }

    /**
     * @author pengjl
     * @time 2021/6/23 11:41
     * @abstract _官网提交用户会员信息
     */
    public function postUserMembers()
    {
        $_data = request()->input();
        //语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        $_arrOfUserDetail = array();

        if (!isset($_data["orderid"])) {
            return array(
                "code" => 0,
                "msg" => "未查询到订单号"
            );
        }

        //是否购买球， 1：是 0：否
        $_isBuyRunball = $_data["is_buy_runball"] ?? 0;

//        手机号
        if (isset($_data["phone"])) {
            $_arrOfUserDetail["phone"] = $_data["phone"];
        }
        if (isset($_data["address"])) {
            $_arrOfUserDetail["address"] = $_data["address"];
        }
        if (isset($_data["address_json"])) {
            $_arrOfUserDetail["address_json"] = $_data["address_json"];
        }
        if (isset($_data["phone_prefix"])) {
            $_arrOfUserDetail["phone_prefix"] = $_data["phone_prefix"];
        }
        if (isset($_data["live_platform"])) {
            $_arrOfUserDetail["live_platform"] = $_data["live_platform"];
        }
        if (isset($_data["live_id"])) {
            $_arrOfUserDetail["live_id"] = $_data["live_id"];
        }
        if (isset($_data["wechart_id"])) {
            $_arrOfUserDetail["wechart_id"] = $_data["wechart_id"];
        }
        if (isset($_data["address_detail"])) {
            $_arrOfUserDetail["address_detail"] = $_data["address_detail"];
        }
        if (isset($_data["real_name"])) {
            $_arrOfUserDetail["real_name"] = $_data["real_name"];
        }

        if (isset($_data["sys_sex_id"])) {
            $_arrOfUserDetail["sys_sex_id"] = $_data["sys_sex_id"];
        }

        if (isset($_data["email"])) {
            $_arrOfUserDetail["email"] = $_data["email"];
        }

        if (isset($_data["birthday"])) {
            $_arrOfUserDetail["birthday"] = $_data["birthday"];

            $_arrOfUserDetail["is_yang"] = 0;
            $_this_date = strtotime(date("Y-m-d", time()));
            $_age = floor(($_this_date - strtotime($_arrOfUserDetail["birthday"])) / (60 * 60 * 24 * 365));
//                13-18周岁判定为青少年
            if ($_age >= StaticDataController::$_yang_start_age && $_age <= StaticDataController::$_yang_stop_age) {
                $_arrOfUserDetail["is_yang"] = 1;
            }
        }

        $_user_id = isset($_data["user_id"]) ? $_data["user_id"] : "";

        if ($_user_id === "") {
            $_arrOfUser = UsrUser::where([
                "phone" => $_arrOfUserDetail["phone"],
                "phone_prefix" => $_arrOfUserDetail["phone_prefix"]
            ])->select("user_id")->get();
            if (count($_arrOfUser) == 0) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, "website_user_not_fount")
                );
            }
            $_user_id = $_arrOfUser[0]["user_id"];
        }

        UsrUser::where([
            "user_id" => $_user_id
        ])->update($_arrOfUserDetail);


        $_sno = new Snowflake(StaticDataController::$_workId);
//        创建用户会员支付订单
        $_arrOfUserMomentData = array(
            "user_members_id" => $_sno->nextId(),
            "order_id" => $_data["orderid"],
            "invite_code" => isset($_data["invite_code"]) ? $_data["invite_code"] : "",
            "usr_user_id" => $_arrOfUser[0]["user_id"],
            "status" => 1,
            "is_buy_runball" => $_isBuyRunball ?? 0
        );

        UserMember::create($_arrOfUserMomentData);

        return array(
            "code" => 1,
            "msg" => "success"
        );

    }

    /**
     * @author pengjl
     * @time 2021/6/23 14:00
     * @abstract _用户支付校验
     */
    public function postUserMembersCheck()
    {
        $_data = request()->input();

        if (!isset($_data["orderid"])) {
            return array(
                "code" => 0,
                "msg" => "未查询到订单号"
            );
        }

//        查询订单是否支付
        $_arrOfUserMembers = UserMember::where([
            "order_id" => $_data["orderid"]
        ])->select("usr_user_id", "pay_status", "pay_time", "pay_amount")->get();

//        查询微信支付状态
        $_arrOfPayResult = self::orderStatus($_data["orderid"]);

        if (count($_arrOfUserMembers) > 0 && $_arrOfPayResult["code"] === 1) {
            Redis::select(1);

            $_int_members_index = Redis::get("members_index");
            if ($_int_members_index == null) {
                $_int_members_index = 100000 + UsrUser::whereNotNull("share_code")->select("user_id")->count();
            }

            $_members_exptime = strtotime("+365 day");

//            查询用户会员状态，如果已是会员，且未过期，会员过期时间延长
            $_arrOfUser = UsrUser::where(["user_id" => $_arrOfUserMembers[0]["usr_user_id"]])->select("user_id", "members_exptime")->orderBy("created_time", "DESC")->get();
            if (count($_arrOfUser) > 0 && $_arrOfUser[0]["members_status"] == 1) {
//                过期时间延长
                $_members_exptime = strtotime(date("Y-m-d H:i:s", $_arrOfUser[0]["members_status"]) . " +365 day");
            }

//            用户成为会员
            UsrUser::where(["user_id" => $_arrOfUserMembers[0]["usr_user_id"]])->update([
                "is_members" => 1,
                "members_exptime" => $_members_exptime,
                "members_status" => 1,
                "members_join_time" => time(),
                "share_code" => $_int_members_index
            ]);
        }

        return $_arrOfPayResult;
    }

    /**
     * @author pengjl
     * @time 2021/6/23 10:44
     * @abstract _查询订单数据
     */
    public function orderInfo()
    {

        $_data = request()->input();

        if (!isset($_data["orderid"])) {
            return array(
                "code" => 0,
                "msg" => "未查询到订单号"
            );
        }

        $_order = $_data["orderid"];

        return self::orderStatus($_order);
    }

    public static function orderStatus($_order_id)
    {

        $_mchid = WxPayController::$_mchid;

        $_url = "https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/$_order_id?mchid=$_mchid";

        $_base_url = "/v3/pay/transactions/out-trade-no/$_order_id?mchid=$_mchid";

        $_time = time();
        $_out_trade_no = date("Ymd", $_time) . rand(10000, 99999);
        $body = "";
        $_http_method = "GET";

        $_str_sign = WxPayController::getSign($_http_method, $_base_url, $body, $_out_trade_no, $_time);

        $_http_header_authorization = WxPayController::getAuthorization($_str_sign, $_out_trade_no, $_time);

        $_headers = array(
            "Content-type:application/json",
            "Accept:application/json",
            "User-Agent:*/*",
            "Authorization:" . $_http_header_authorization,
        );

        $_result = WxPayController::get($_url, array(), $_headers);
        Log::info('支付检查', [$_result]);
        if (isset($_result["trade_state"]) && $_result["trade_state"] === "SUCCESS") {

            return array(
                "code" => 1,
                "msg" => "支付成功，请前往APP查看会员状态"
            );
        } else {
            return array(
                "code" => 0,
                "msg" => isset($_result["trade_state"]) ? $_result["trade_state_desc"] : "支付失败"
            );
        }
    }


    /**
     * @author pengjl
     * @time 2021/6/23 10:44
     * @abstract _微信支付 H5下单
     */
    public function wxPay()
    {

        $_request_url = "https://api.mch.weixin.qq.com/v3/pay/transactions/h5";
        $_time = time();
        $_out_trade_no = date("Ymd", $_time) . $_time . rand(100000, 999999);
        $_mchid = WxPayController::$_mchid;
        $_appid = WxPayController::$_appid;

        $_description = "RunOrb会员年费";
        $_notify_url = "https://api.runorb.us/api/test";


        $_arrOfSysMember = SysMember::where(["status" => 1])->select(
            "title_cn", "title_en", "members_amount", "members_description_cn", "members_description_en", "currency"
        )->get();


        Log::info($_arrOfSysMember);


//        $_amount = [
//            "total" => 1,
//            "currency" => "CNY"
//        ];
        if (count($_arrOfSysMember) > 0) {
            $_amount = array(
                "total" => $_arrOfSysMember[0]["members_amount"] * 100,
                "currency" => $_arrOfSysMember[0]["currency"]
            );
        } else {
            $_amount = array(
//                "total"=>30000,
                "total" => 20000,
                "currency" => "CNY"
            );
        }


        $_scene_info = array(
            "payer_client_ip" => request()->ip(),
            "h5_info" => array(
                "type" => "Wap"
            )
        );


        $_http_data = array(
            "mchid" => $_mchid,
            "out_trade_no" => $_out_trade_no,
            "appid" => $_appid,
            "description" => $_description,
            "notify_url" => $_notify_url,
            "amount" => $_amount,
            "scene_info" => $_scene_info,
        );

        $_str_sign = WxPayController::getSign("POST", "/v3/pay/transactions/h5", json_encode($_http_data), $_out_trade_no, $_time);

        $_http_header_authorization = WxPayController::getAuthorization($_str_sign, $_out_trade_no, $_time);

        $_headers = array(
            "Content-type:application/json",
            "Accept:application/json",
            "User-Agent:*/*",
            "Authorization:" . $_http_header_authorization,
        );
        Log::info('签名', [$_str_sign, $_http_header_authorization]);
        $_result = WxPayController::post($_request_url, $_http_data, $_headers);

        Log::info("支付返回：", [$_result]);
        Log::info("支付订单号：" . $_out_trade_no);

        $_result["h5_url"] = $_result["h5_url"] . "&redirect_url=https://hisport.cloud/applyForm?orderid=" . $_out_trade_no;
        $_result["order_id"] = $_out_trade_no;

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_result
        );
    }


    /**
     * 追加会员参数
     *
     * @param $updateData
     */
    public static function getMemberData(&$updateData)
    {
        $_members_exptime = strtotime(date("Y-m-d H:i:s") . " +365 day");

        Redis::select(1);
        $_int_members_index = Redis::get("members_index");
        if ($_int_members_index == null) {
            $_int_members_index = 100000 + UsrUser::whereNotNull("share_code")->select("user_id")->count();
        }

        $updateData['is_members'] = 1;
        $updateData['members_exptime'] = $_members_exptime;
        $updateData['members_status'] = 1;
        $updateData['members_join_time'] = time();
        $updateData['share_code'] = $_int_members_index;
    }


}
