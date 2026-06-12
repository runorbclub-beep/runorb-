<?php


namespace App\Http\Controllers\Api;


use App\Constants\ErrorCode;
use App\Http\CommonClass\RandName;
use App\Http\CommonClass\SMSController;
use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\WeixinHttp;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\fileMoveController;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Controllers\PublicFunction\UsrUserController;
use App\Http\Requests\Api\User\UserPostTripartiteRequest;
use App\Model\SysUser;
use App\Models\MatchsStage;
use App\Models\MatchsUserGrade;
use App\Models\SysCountry;
use App\Models\SysSex;
use App\Models\SysUserType;
use App\Models\UserAchievement;
use App\Models\UserMedalAssociated;
use App\Models\UsrUser;
use App\Services\YouzanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{

    // 免费会员时间
    const MEMBERS_START_DATE = '2021-07-26';
    const MEMBERS_STOP_DATE = '2022-12-31';


    public $AppID = "wxa22bc8af865c116f";
    public $AppSecret = env("WECHAT_APP_SECRET", "");
    public $_http;

    public function __construct()
    {
        $this->_http = new WeixinHttp();
    }

    public function randUser($_sys_country, $_language, $_device_uid, $_version = '', $_channel = '', $_device_model = '')
    {

//        Log::info("创建新用户---------------------");

//        唯一主键
        $_objOfSnowflake = new Snowflake(StaticDataController::$_workId);
        $_rand_id = $_objOfSnowflake->nextId();

//        随机姓名
        $_objOfRandName = new RandName();
        $_nick_name = $_objOfRandName->getName($_sys_country);

//        国家
        $_arrOfSysCountry = json_decode(Redis::hget("sys_country", $_sys_country));

//        如果没找到国家
        if ($_arrOfSysCountry == null) {
            $_ObjOfSysCountry = SysCountry::where([
                "status" => 1,
                "name_en" => $_sys_country
            ])->select("sys_country_id", "status", "name_cn", "name_en", "country_code", "default_language", "order_index")->get();


            if (count($_ObjOfSysCountry) == 1) {
                $_arrOfSysCountry = $_ObjOfSysCountry[0];
                Redis::hset("sys_country", $_arrOfSysCountry["name_en"], json_encode($_arrOfSysCountry));
            } else {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, 'system_error'),
                );
            }
        }


//        性别
        $_arrOfSysSex = json_decode(Redis::hget("sys_sex", "man"));
        if ($_arrOfSysSex == null) {
            $_ObjOfSysSex = SysSex::where([
                "status" => 1,
            ])->select("sys_sex_id", "status", "sex_name", "sex_code")->get();

            if (count($_ObjOfSysSex) == 0) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, 'system_error'),
                );

            } else {
                $_arrOfSysSex = $_ObjOfSysSex[0];
                Redis::hset("sys_sex", $_arrOfSysSex["sex_code"], json_encode($_arrOfSysSex));
            }
        }

//        用户类型，默认游客
        $_arrOfSysUserType = json_decode(Redis::hget("sys_user_type", "tourists"));
        if ($_arrOfSysUserType == null) {
            $_ObjOfSysUserType = SysUserType::where([
                "status" => 1,
                "user_type_code" => "tourists",
            ])->select("sys_user_type_id", "status", "user_type_name", "user_type_code")->get();

            if (count($_ObjOfSysUserType) == 0) {
                return array(
                    "code" => 0,
                    "msg" => LanguageController::getLanguage($_language, 'system_error'),
                );
            } else {
                $_arrOfSysUserType = $_ObjOfSysUserType[0];
                Redis::hset("sys_user_type", $_arrOfSysUserType["sys_user_type_id"], json_encode($_arrOfSysUserType));
            }
        }

        $_access_token = md5($_rand_id . $_nick_name);

        $_arrOfUserDetailChange = StaticDataController::$_user_detail_change_num;

        $_arrOfUser = array(
            "user_id" => $_rand_id,
            "user_name" => $_nick_name,
            "status" => 1,
            "self_description" => $_language == "zh-CN" ? '多运动更健康，大家快来一起运动吧！' : "More exercise is healthier, everyone come to exercise together!",
            "user_name_change" => 0,
            "user_img_change" => 0,
            "phone" => "",
            "user_img" => 'wx_sources/default_user.png',
            "access_token" => $_access_token,
            "sys_country_id" => $_arrOfSysCountry->sys_country_id,
            "user_country" => $_arrOfSysCountry->name_cn,
            "sys_sex_id" => $_arrOfSysSex->sys_sex_id,
            "sex_name" => $_arrOfSysSex->sex_name,
            "sys_user_type_id" => $_arrOfSysUserType->sys_user_type_id,
            "user_type_name" => $_arrOfSysUserType->user_type_name,
            "device_uid" => $_device_uid,
            "is_group" => 0,
            "user_birthday_change" => $_arrOfUserDetailChange['user_birthday_change'],
            "user_city_change" => $_arrOfUserDetailChange['user_city_change'],
            "sys_sex_id_change" => $_arrOfUserDetailChange['sys_sex_id_change'],
            "integral" => 0,
            "version" => $_version ?? '',
            "channel" => $_channel ?? '',
            "device_model" => $_device_model ?? '',
        );

//        写入数据库
        UsrUser::create($_arrOfUser);

        $_user_img = $_arrOfUser["user_img"];
        if (strpos($_user_img, 'http') === false) {
            $_arrOfUser["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
        }

        $_usr_user["achievement"] = array(
            "duration" => 0,
            "speed_max" => 0,
            "circle_count" => 0,
            "endurance_max" => 0,
            "play_count" => 0,
            "thrmin" => 0,
            "half_marathon" => 0,
        );

//        写入缓存
        Redis::select(1);
        Redis::hset("usr_user", $_access_token, json_encode($_arrOfUser));

        //            获取用户勋章列表
        $_arrOfUser["my_medal"] = UsrUserController::getUserMedal($_arrOfUser["access_token"], 4, $_language);
        $_arrOfUser["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_arrOfUser["access_token"]);

        //        写入缓存
        Redis::select(1);
        Redis::hset("usr_user", $_access_token, json_encode($_arrOfUser));

        if (!isset($_arrOfUser["token"]) && isset($_arrOfUser["access_token"])) {
            $_arrOfUser["token"] = $_arrOfUser["access_token"];
        }
        //购买渠道链接
        $_arrOfUser["shop_url"] = env("shop_url");
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_info" => $_arrOfUser
            )
        );

    }

    /**
     * @abstract 随机生成游客账号
     * @return array
     */
    public function postRandUser(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_log_data = array(
            "url" => $request->path(),
            "data" => $request->input(),
            "token" => $request->header("token"),
        );
//        存储日志
//        Log::info("请求数据：".json_encode($_log_data));

        $_version = $_data['version'] ?? '';    //版本号
        $_channel = $_data['channel'] ?? '';    //渠道
        $_device_model = $_data['device_model'] ?? '';    //手机型号

        $_sys_country = isset($_data["sys_country"]) && $_data["sys_country"] != null ? $_data["sys_country"] : "chinese";//国家
        $_device_uid = isset($_data["device_uid"]) && $_data["device_uid"] != null ? $_data["device_uid"] : "";//设备号
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if ($_device_uid == "") {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter"),
            );
        }

        $_arrOfUsrUser = UsrUser::where([
            "usr_user.device_uid" => $_device_uid
        ])->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->join("sys_sex", function ($join) {
            $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
        })->join("sys_country", function ($join) {
            $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
        })->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change", "usr_user.photo_text", "usr_user.user_img_change", "usr_user.user_img", "sys_sex.sex_name"
            , "usr_user.device_uid", "sys_country.name_cn", "usr_user.access_token as token"
            , "sys_user_type.user_type_name", "usr_user.integral", "usr_user.version", "usr_user.device_model", "usr_user.channel"
        )->get();


//        Log::info("数据库查询结果---------------------");
//        Log::info(json_encode($_arrOfUsrUser));


        if (count($_arrOfUsrUser) == 1) {
            $_user_info = $_arrOfUsrUser[0];

            $_user_img = $_user_info["user_img"];
            if (strpos($_user_img, 'http') === false) {
                $_user_info["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
            }

            Redis::select(1);
//          写入缓存
            Redis::hset("usr_user", $_user_info["token"], json_encode($_user_info));

//            获取用户勋章列表
            $_user_info["my_medal"] = UsrUserController::getUserMedal($_user_info["token"], 4, $_language);
            $_user_info["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_user_info["token"]);

            //购买渠道链接
            $_user_info["shop_url"] = env("shop_url");

            $updateList = [];
            if ($_version != $_user_info['version']) {
                $updateList['version'] = $_version ?? '';
            }
            if ($_device_model != $_user_info['device_model']) {
                $updateList['device_model'] = $_device_model ?? '';
            }
            if ($_channel != $_user_info['channel']) {
                $updateList['channel'] = $_channel ?? '';
            }
            if ($updateList) {  //更新
                UsrUser::where('user_id', $_user_info['user_id'])->update($updateList);
            }

            $_user_info['version'] = $_version ?: ($_user_info['version'] ?? '');
            $_user_info['device_model'] = $_device_model ?: ($_user_info['device_model'] ?? '');
            $_user_info['channel'] = $_channel ?: ($_user_info['channel'] ?? '');

            Redis::select(1);
//          写入缓存
            Redis::hset("usr_user", $_user_info["token"], json_encode($_user_info));

//            Log::info("直接返回---------------------");
//            Log::info(json_encode($_user_info));
            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "user_info" => $_user_info
                )
            );
        }


//        创建随机用户返回
        return $this->randUser($_sys_country, $_language, $_device_uid, $_version, $_channel, $_device_model);
    }

    /**
     * @author pengjl
     * @time 2021/6/8 10:33
     * @abstract _海外手机区号
     */
    public function postOverseasCode()
    {
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => StaticDataController::$_overseas_code
        );
    }

    /**
     * @param Request $request
     * @return array
     * @author pengjl
     * @abstract 绑定手机号
     */
    public function postBindPhone(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["phone"]) || !isset($_data["number"])) {
            return array(
                "code" => 0,
                "msg" => SystemErrorController::paramtersError($_language)
            );
        }

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        校验验证码
        $_is_check = SMSController::checkNumber($_data["phone"], $_data["number"], "bind_phone");

        if (trim($_data["phone"]) == "17328767742") {   //app审核专用账户，请勿改动或删除
            $_is_check = true;
        }


//        验证手机号
        if ($_is_check) {
            $_phone_sms_check = json_decode(Redis::get("phone_sms:" . $_data["phone"]), true);

            $_arrOfUsrUser = UsrUser::where([
                "phone" => $_data["phone"],
                "phone_prefix" => $_phone_sms_check["phone_prefix"] ?? 0,
            ])->select("access_token")->get();

            if (count($_arrOfUsrUser) == 1) {
                return self::getUserInfo($_arrOfUsrUser[0]["access_token"], $_language);
            } else {
                $updateData = [
                    "phone" => $_data["phone"],
                    "phone_prefix" => $_phone_sms_check["phone_prefix"] ?? 0,
                    "is_group" => -1
                ];

                // 判断是否免费会员
                if ($this->checkFreeMember()) {
                    UserMembersController::getMemberData($updateData);  //追加会员参数
                }

                UsrUser::where([
                    "user_id" => $_usr_user["user_id"]
                ])->update($updateData);

                return self::getUserInfo($_user_token, $_language);
            }

        } else {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "sms_not_found"),
            );
        }
    }


    /**
     * @abstract 手机号登陆
     * @param Request $request
     * @return array
     */
    public function postLoginPhone(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_version = $_data['version'] ?? '';    //版本号
        $_channel = $_data['channel'] ?? '';    //渠道
        $_device_model = $_data['device_model'] ?? '';    //手机型号

        if (trim($_data["phone"]) == "17328767742") {   //app审核专用账户，请勿改动或删除
            //不写代码 if为true安全些
        }else{
            if (!isset($_data["phone"]) || !isset($_data["number"])) {
                return array(
                    "code" => 0,
                    "msg" => SystemErrorController::paramtersError($_language)
                );
            }
        }

        if (trim($_data["phone"]) == "17328767742") {   //app审核专用账户，请勿改动或删除
            $_is_check = true;
        }else{
            //        校验码验证
            $_is_check = SMSController::checkNumber($_data["phone"], $_data["number"], "login");
        }

        if (!$_is_check) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "sms_not_found"),
            );
        }

        Redis::select(1);
        $_user_token = $request->header("token");
        $_phone_sms_check = json_decode(Redis::get("phone_sms:" . $_data["phone"]), true);

        if (trim($_data["phone"]) == "17328767742") {   //app审核专用账户，请勿改动或删除
            $_phone_sms_check = [
                "msg_type" => "login",
                "number" => 123456,
                "phone_prefix" => "86",
            ];
        }


//        查询当前手机号是否存在绑定用户
        $_arrOfUsrUser = UsrUser::where([
            "usr_user.phone" => $_data["phone"],
            "usr_user.status" => 1,
//            "usr_user.phone_prefix" => $_phone_sms_check["phone_prefix"],
        ])->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->join("sys_sex", function ($join) {
            $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
        })->join("sys_country", function ($join) {
            $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
        })->select(
            "usr_user.user_id", "usr_user.is_group", "usr_user.status", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change"
            , "usr_user.user_img_change", "usr_user.user_img", "usr_user.access_token as token", "sys_sex.sys_sex_id", "sys_sex.sex_name"
            , "usr_user.device_uid", "sys_country.sys_country_id", "sys_country.name_cn", "sys_user_type.sys_user_type_id"
            , "sys_user_type.user_type_name", "usr_user.phone", "usr_user.phone_prefix", "usr_user.access_token"
            , "usr_user.is_members", "usr_user.members_status", "usr_user.members_exptime", "usr_user.share_code", "usr_user.integral", "usr_user.birthday"
            , "usr_user.address", "usr_user.version", "usr_user.device_model", "usr_user.channel", "usr_user.sys_sex_id_change", "usr_user.photo_text"
        )->get();

        if (count($_arrOfUsrUser) == 1) {
//            写入缓存
            Redis::select(1);
//            删除原数据
            $_a = Redis::hdel("usr_user", $_arrOfUsrUser[0]["access_token"]);

            Log::info("删除原token：" . $_arrOfUsrUser[0]["access_token"] . "，状态：" . $_a);


            Log::info("手机号已绑定用户---------------");
//            已找到用户，刷新token返回
            $_user_info = $_arrOfUsrUser[0];
            $_user_token = md5($_user_info["user_id"] . $_user_info["user_name"] . time() . rand(100000, 999999));
            $_user_info["token"] = $_user_token;
            $_user_info["access_token"] = $_user_token;

            $_b = Redis::hset("usr_user", $_user_token, json_encode($_user_info));
            Log::info("设置token：" . $_user_token . "，状态：" . $_b);


            $_user_info["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_user_token);
//            获取用户勋章列表
            $_user_info["my_medal"] = UsrUserController::getUserMedal($_user_token, 4, $_language);

            UsrUser::where(["user_id" => $_user_info["user_id"]])->update(["access_token" => $_user_token]);

            $_user_img = $_user_info["user_img"];
            if (strpos($_user_img, 'http') === false) {
                $_user_info["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
            }

            if ($_user_info["share_code"] == null) {
                $_user_info["share_code"] = "";
            }
            if ($_user_info["members_exptime"] == null) {
                $_user_info["members_exptime"] = "";
            }

            $updateList = [];
            if ($_version != $_user_info['version']) {
                $updateList['version'] = $_version ?? '';
            }
            if ($_device_model != $_user_info['device_model']) {
                $updateList['device_model'] = $_device_model ?? '';
            }
            if ($_channel != $_user_info['channel']) {
                $updateList['channel'] = $_channel ?? '';
            }
            if ($updateList) {  //更新
                UsrUser::where('user_id', $_user_info['user_id'])->update($updateList);
            }

            $_user_info['version'] = $_version ?: ($_user_info['version'] ?? '');
            $_user_info['device_model'] = $_device_model ?: ($_user_info['device_model'] ?? '');
            $_user_info['channel'] = $_channel ?: ($_user_info['channel'] ?? '');

//                写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_user_info));

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "user_info" => $_user_info
                )
            );

        } else {
            Log::info("手机号未绑定用户---------------");
            //        创建随机用户
            $_arrOfUserResult = $this->randUser("chinese", $_language, "", $_version, $_channel, $_device_model);

//            if($_arrOfUserResult["code"]==1){
            $_arrOfUser = $_arrOfUserResult["data"]["user_info"];

            $_user_token = $_arrOfUser["token"];

            $_arrofUserCount = UsrUser::where(["sys_user_type_id" => "1809649560981504"])->select("user_id")->count();

            $_arrOfUser['is_group'] = -1;
            $_arrOfUser['birthday'] = null;
            $_arrOfUser['address'] = '';
            $updateData = [
                "phone" => $_data["phone"],
                "usr_user.phone_prefix" => $_phone_sms_check["phone_prefix"],
                "sys_user_type_id" => "1809649560981504",
                "access_token" => $_user_token,
                'is_group' => -1
            ];
            // 判断是否免费会员
            if ($this->checkFreeMember()) {
                UserMembersController::getMemberData($updateData);  //追加会员参数
            }

            UsrUser::where([
                "user_id" => $_arrOfUser["user_id"]
            ])->update($updateData);

            $_arrOfUserResult["data"]["user_info"]['phone'] = $_arrOfUser["phone"] = $_data["phone"];
            $_arrOfUserResult["data"]["user_info"]['phone_prefix'] = $_arrOfUser["phone_prefix"] = $_phone_sms_check["phone_prefix"];
            $_arrOfUserResult["data"]["user_info"]['sys_user_type_id'] = $_arrOfUser["sys_user_type_id"] = "1809649560981504";
            $_arrOfUserResult["data"]["user_info"]['user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['sys_user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['is_group'] = -1;
            $_arrOfUserResult["data"]["user_info"]['birthday'] = null;
            $_arrOfUserResult["data"]["user_info"]['address'] = '';
            $_arrOfUserResult["data"]["user_info"]['photo_text'] = '';

//            写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_arrOfUser));

            //创建用户到有赞商铺
            try {
                $youzanService = new YouzanService();
                $createCustomer = $youzanService->createCustomer([
                    'phone' => (string)$_arrOfUser['phone'],
                    'birthday' => date('Y-m-d 00:00:00'),
                    'sys_sex_id' => 0,
                    'user_name' => $_arrOfUser["user_name"],
                    'remark' => $_arrOfUser["sys_user_type_name"]
                ]);
                if ($createCustomer['code'] !== 200){//记录创建失败记录
                    Log::info('注册用户 '.$_arrOfUser['phone'].' ==创建到有赞商铺失败1：'.json_encode($createCustomer,true));
                }
            }catch (\Throwable $ex){
                Log::info('注册用户 '.$_arrOfUser['phone'].' ==创建到有赞商铺失败2：'.json_encode($ex,true));
            }

            return $_arrOfUserResult;
        }

        return array(
            "code" => 0,
            "msg" => "error"
        );
    }

    /**
     * @abstract 邮箱登陆
     * @param Request $request
     * @return array
     */
    public function postLoginEmail(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_version = $_data['version'] ?? '';    //版本号
        $_channel = $_data['channel'] ?? '';    //渠道
        $_device_model = $_data['device_model'] ?? '';    //手机型号

        if (!isset($_data["email"]) || !isset($_data["number"])) {
            return array(
                "code" => 0,
                "msg" => SystemErrorController::paramtersError($_language)
            );
        }

//        校验码验证
        $_is_check = EmailController::checkNumber($_data["email"], $_data["number"], 'login');

        if (trim($_data["email"]) == "runorb@runorb.com") {   //app审核专用账户，请勿改动或删除
            $_is_check = true;
        }

        if (!$_is_check) {
            return array(
                "code" => 0,
                "msg" => $_language == 'zh-CN' ? '验证码错误' : 'Verification code error'
            );
        }

        Redis::select(1);
        $_user_token = $request->header("token");
//        查询当前手机号是否存在绑定用户
        $_arrOfUsrUser = UsrUser::where([
            "usr_user.email" => $_data["email"]
        ])->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->join("sys_sex", function ($join) {
            $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
        })->join("sys_country", function ($join) {
            $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
        })->select(
            "usr_user.user_id", "usr_user.is_group", "usr_user.status", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change"
            , "usr_user.user_img_change", "usr_user.user_img", "usr_user.access_token as token", "sys_sex.sys_sex_id", "sys_sex.sex_name"
            , "usr_user.device_uid", "sys_country.sys_country_id", "sys_country.name_cn", "sys_user_type.sys_user_type_id"
            , "sys_user_type.user_type_name", "usr_user.phone", "usr_user.phone_prefix", "usr_user.access_token"
            , "usr_user.is_members", "usr_user.members_status", "usr_user.members_exptime", "usr_user.share_code", "usr_user.integral", "usr_user.birthday"
            , "usr_user.address", "usr_user.version", "usr_user.device_model", "usr_user.channel", "usr_user.email", "usr_user.user_city_change", "usr_user.user_birthday_change", "usr_user.sys_sex_id_change", "usr_user.photo_text"
        )->get();

        if (count($_arrOfUsrUser) == 1) {
//            写入缓存
            Redis::select(1);
//            删除原数据
            $_a = Redis::hdel("usr_user", $_arrOfUsrUser[0]["access_token"]);

            Log::info("删除原token：" . $_arrOfUsrUser[0]["access_token"] . "，状态：" . $_a);

//            已找到用户，刷新token返回
            $_user_info = $_arrOfUsrUser[0];
            $_user_token = md5($_user_info["user_id"] . $_user_info["user_name"] . time() . rand(100000, 999999));
            $_user_info["token"] = $_user_token;
            $_user_info["access_token"] = $_user_token;

            $_b = Redis::hset("usr_user", $_user_token, json_encode($_user_info));
            Log::info("设置token：" . $_user_token . "，状态：" . $_b);


            $_user_info["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_user_token);
//            获取用户勋章列表
            $_user_info["my_medal"] = UsrUserController::getUserMedal($_user_token, 4, $_language);

            UsrUser::where(["user_id" => $_user_info["user_id"]])->update(["access_token" => $_user_token]);

            $_user_img = $_user_info["user_img"];
            if (strpos($_user_img, 'http') === false) {
                $_user_info["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
            }

            if ($_user_info["share_code"] == null) {
                $_user_info["share_code"] = "";
            }
            if ($_user_info["members_exptime"] == null) {
                $_user_info["members_exptime"] = "";
            }

            $updateList = [];
            if ($_version != $_user_info['version']) {
                $updateList['version'] = $_version ?? '';
            }
            if ($_device_model != $_user_info['device_model']) {
                $updateList['device_model'] = $_device_model ?? '';
            }
            if ($_channel != $_user_info['channel']) {
                $updateList['channel'] = $_channel ?? '';
            }
            if ($updateList) {  //更新
                UsrUser::where('user_id', $_user_info['user_id'])->update($updateList);
            }

            $_user_info['version'] = $_version ?: ($_user_info['version'] ?? '');
            $_user_info['device_model'] = $_device_model ?: ($_user_info['device_model'] ?? '');
            $_user_info['channel'] = $_channel ?: ($_user_info['channel'] ?? '');

//                写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_user_info));

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "user_info" => $_user_info
                )
            );

        } else {
            Log::info("邮箱未绑定用户---------------");
            //        创建随机用户
            $_arrOfUserResult = $this->randUser("chinese", $_language, "", $_version, $_channel, $_device_model);

//            if($_arrOfUserResult["code"]==1){
            $_arrOfUser = $_arrOfUserResult["data"]["user_info"];

            $_user_token = $_arrOfUser["token"];

            $_arrofUserCount = UsrUser::where(["sys_user_type_id" => "1809649560981504"])->select("user_id")->count();

            $_arrOfUser['is_group'] = -1;
            $_arrOfUser['birthday'] = null;
            $_arrOfUser['address'] = '';
            $updateData = [
                "email" => $_data["email"],
                "sys_user_type_id" => "1809649560981504",
                "access_token" => $_user_token,
                'is_group' => -1
            ];
            // 判断是否免费会员
            if ($this->checkFreeMember()) {
                UserMembersController::getMemberData($updateData);  //追加会员参数
            }

            UsrUser::where([
                "user_id" => $_arrOfUser["user_id"]
            ])->update($updateData);

            $_arrOfUserResult["data"]["user_info"]['email'] = $_arrOfUser["email"] = $_data["email"];
            $_arrOfUserResult["data"]["user_info"]['sys_user_type_id'] = $_arrOfUser["sys_user_type_id"] = "1809649560981504";
            $_arrOfUserResult["data"]["user_info"]['user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['sys_user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['is_group'] = -1;
            $_arrOfUserResult["data"]["user_info"]['birthday'] = null;
            $_arrOfUserResult["data"]["user_info"]['address'] = '';
            $_arrOfUserResult["data"]["user_info"]['photo_text'] = '';

//            写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_arrOfUser));

            return $_arrOfUserResult;
        }

        return array(
            "code" => 0,
            "msg" => "error"
        );
    }


    /**
     * @author pengjl
     * @time 2021/5/17 19:42
     * @abstract _用户通过微信登录
     */
    public function postLoginOpenWx(Request $request)
    {
        Redis::select(1);

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");

        $_data = $request->input();

        if (!isset($_data["code"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_js_code = $_data["code"];

        $_data = array(
            "appid" => $this->AppID,
            "secret" => $this->AppSecret,
            "js_code" => $_js_code,
            "grant_type" => "authorization_code"
        );

        $_arrOfSession = json_decode($this->_http->get($_data, "https://api.weixin.qq.com/sns/jscode2session"), true);


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfSession
        );

        Log::info("微信Session：" . json_encode($_arrOfSession));

        $_arrOfUsrUser = UsrUser::where([
            "open_weixin_id" => $_arrOfSession["openid"]
        ])->select("access_token")->get();

//        已找到
        if (count($_arrOfUsrUser) == 1) {
            return self::getUserInfo($_arrOfUsrUser[0]["access_token"], $_language);
        } else {
            UsrUser::where([
                "access_token" => $_user_token
            ])->update([
                "open_weixin_id" => $_arrOfSession["openid"]
            ]);

            return self::getUserInfo($_user_token, $_language);
        }

    }


    /**
     * @abstract 获取用户信息
     * @param Request $request
     * @return array
     */
    public function postUserInfo(Request $request)
    {
        Redis::select(1);

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");

        return self::getUserInfo($_user_token, $_language);
    }

    public static function getUserInfo($_user_token, $_language)
    {
        $_arrOfUsrUser = UsrUser::where([
            "usr_user.access_token" => $_user_token
        ])->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->join("sys_sex", function ($join) {
            $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
        })->join("sys_country", function ($join) {
            $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
        })->select(
            "usr_user.user_id", "usr_user.status", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change"
            , "usr_user.user_img_change", "usr_user.user_img", "usr_user.access_token as token", "sys_sex.sys_sex_id", "sys_sex.sex_name"
            , "usr_user.device_uid", "sys_country.sys_country_id", "sys_country.name_cn", "sys_user_type.sys_user_type_id"
            , "sys_user_type.user_type_name", "usr_user.birthday", "usr_user.address", "usr_user.phone", "usr_user.phone_prefix", "usr_user.open_weixin_id"
            , "usr_user.is_members", "usr_user.members_status", "usr_user.members_exptime", "usr_user.share_code", "usr_user.live_platform"
            , "usr_user.live_id", "usr_user.is_yang", "usr_user.wechart_id", "usr_user.real_name", "usr_user.address_detail", "usr_user.address_json"
            , "usr_user.user_birthday_change", "usr_user.user_city_change", "usr_user.user_name_change"
            , "usr_user.wechart_id", "usr_user.email", "usr_user.is_group", "usr_user.integral", "usr_user.sys_sex_id_change", "usr_user.photo_text"
        )->get();


        if (count($_arrOfUsrUser) == 0) {
            return array(
                "code" => 2,
                "msg" => LanguageController::getLanguage($_language, 'none_user'),
            );
        } else {
            $_user_info = $_arrOfUsrUser[0];

            $_user_info["share_code"] = $_user_info["share_code"] == null ? "" : $_user_info["share_code"];
            $_user_info["live_platform"] = $_user_info["live_platform"] == null ? "" : $_user_info["live_platform"];
            $_user_info["live_id"] = $_user_info["live_id"] == null ? "" : $_user_info["live_id"];
            $_user_info["is_yang"] = $_user_info["is_yang"] == null ? "" : $_user_info["is_yang"];
            $_user_info["members_exptime"] = $_user_info["members_exptime"] == null ? "" : $_user_info["members_exptime"];
            $_user_info["address"] = $_user_info["address"] == null ? "" : $_user_info["address"];
            $_user_info["is_group"] = $_user_info["is_group"] == null ? "" : $_user_info["is_group"];
            $_user_info["photo_text"] = $_user_info["photo_text"] == null ? "" : $_user_info["photo_text"];

            Redis::select(1);
//          写入缓存
            Redis::hset("usr_user", $_user_info["token"], json_encode($_user_info));
            $_user_info["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_user_token);
//            获取用户勋章列表
            $_user_info["my_medal"] = UsrUserController::getUserMedal($_user_token, 4, $_language);


            $_user_img = $_user_info["user_img"];
            if (strpos($_user_img, 'http') === false) {
                $_user_info["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
            }

            if ($_user_info["members_exptime"] != null && $_user_info["members_exptime"] != "") {
                $_user_info["members_exptitle"] = date("Y-m-d", $_user_info["members_exptime"]);
            } else {
                $_user_info["members_exptitle"] = LanguageController::getLanguage($_language, "member_exp_time_title");
            }

            if ($_user_info["is_members"] == 0) {
                $_user_info["show_members_entrance"] = 1;
                $_user_info["members_entrance_url"] = env("members_entrance_url");
            } else {
                $_user_info["show_members_entrance"] = 0;
                $_user_info["members_entrance_url"] = "";
            }


            $_sys_medal_count = 0;
            foreach ($_user_info["my_medal"] as $value) {
                if ($value["is_get"]) {
                    $_sys_medal_count += 1;
                }
            }

            $_user_info["sys_medal_count"] = $_sys_medal_count;

//                写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_user_info));

            //购买渠道链接
            $_user_info["shop_url"] = env("shop_url");

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => array(
                    "user_info" => $_user_info
                )
            );
        }
    }

    /**
     * @abstract 修改用户信息
     * @return array
     */
    public function postUserChange(Request $request)
    {
        $_data = $request->input();

        Redis::select(1);
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        Log::info('=================用户初始数据：'.json_encode($_usr_user));

        //修复缓存字段缺失问题 TODO。。。
        $_usr_user = UsrUser::where('user_id',$_usr_user['user_id'])->where('status',1)->first();

        if ($_usr_user != null && isset($_usr_user['sys_user_type_id']) && $_usr_user['sys_user_type_id'] == '1809649560981504') { //必须注册用户才能修改

            $_update_user_data = array();

            if (isset($_data["is_group"])) { //团队OR个人
                $_usr_user["is_group"] = $_data["is_group"] ?? 0;
                $_update_user_data["is_group"] = $_data["is_group"] ?? 0;
            }

            if (isset($_data['user_age_type'])) {   //团队时-成年OR青年
                $_usr_user["is_yang"] = $_data["user_age_type"] ?? 0;
                $_update_user_data["is_yang"] = $_data['user_age_type'] ?? 0;
            }

            if (isset($_data["user_name"])) {
                $_usr_user["user_name"] = $_data["user_name"];
                $_update_user_data["user_name"] = $_data["user_name"];
            }

            if (isset($_data["self_description"])) {
                $_usr_user["self_description"] = $_data["self_description"];
                $_update_user_data["self_description"] = $_data["self_description"];
            }

            if (isset($_data["photo_text"])) {
                $_usr_user["photo_text"] = $_data["photo_text"];
                $_update_user_data["photo_text"] = $_data["photo_text"];
            }

            $isDel = false;
            $_user_birthday_change = $_usr_user["user_birthday_change"] ?? 0;
//            已定义生日，且与原生日不一致，变更生日，并扣减修改次数
            Log::info('user_city_change_data 次数:'.$_user_birthday_change);
            Log::info('user_data1111111111111:'.json_encode($_data));
            if (isset($_data["birthday"]) && $_data["birthday"] != ($_usr_user["birthday"] ?? '') && $_user_birthday_change > 0) {
                $_usr_user["birthday"] = $_data["birthday"];
                $_usr_user["user_birthday_change"] = $_user_birthday_change - 1;


                $_update_user_data["is_yang"] = 0;
                $_this_date = strtotime(date("Y-m-d", time()));
                $_age = floor(($_this_date - strtotime($_data["birthday"])) / (60 * 60 * 24 * 365));
                Log::info('===============age==========:_age'.$_age);

//                13-18周岁判定为青少年
                if ($_age >= StaticDataController::$_yang_start_age && $_age <= StaticDataController::$_yang_stop_age) {
                    $_update_user_data["is_yang"] = 1;
                }

                $_update_user_data["birthday"] = $_data["birthday"];
                $_update_user_data["user_birthday_change"] = $_usr_user["user_birthday_change"];

                if ($isDel == false) {
                    Redis::select(1);
                    Redis::hdel('user_ranking_list', $_usr_user['user_id']);
                    $isDel = true;
                }
            }

//            用户修改地址，扣减剩余修改次数
            Log::info('user_data1111111111111111:'.json_encode($_usr_user));
            $_user_city_change = $_usr_user["user_city_change"] ?? 0;
            Log::info('user_city_change_data 次数:'.$_user_city_change);
            Log::info('user_data22222222222222222:'.json_encode($_data));
            if (isset($_data["address"]) && $_data["address"] != ($_usr_user["address"] ?? '') && $_user_city_change > 0) {
                $_usr_user["address"] = $_data["address"];
                $_usr_user["user_city_change"] = $_user_city_change - 1;
                $_update_user_data["address"] = $_data["address"];
                $_update_user_data["user_city_change"] = $_usr_user["user_city_change"];
                if ($isDel == false) {
                    Redis::select(1);
                    Redis::hdel('user_ranking_list', $_usr_user['user_id']);
                }
            }

            //用户修改性别，扣减剩余修改次数
            $_sys_sex_id_change = $_usr_user["sys_sex_id_change"] ?? 0;
            if (isset($_data["sys_sex_id"]) && $_data["sys_sex_id"] != ($_usr_user["sys_sex_id"] ?? '') && $_sys_sex_id_change > 0) {
                $_usr_user["sys_sex_id"] = $_data["sys_sex_id"];
                $_usr_user["sys_sex_id_change"] = $_sys_sex_id_change - 1;
                $_update_user_data["sys_sex_id"] = $_data["sys_sex_id"];
                $_update_user_data["sys_sex_id_change"] = $_usr_user["sys_sex_id_change"];
                if ($isDel == false) {
                    Redis::select(1);
                    Redis::hdel('user_ranking_list', $_usr_user['user_id']);
                }
                Log::info('性别有被修改到');
            }

//            地址，json格式
            if (isset($_data["address_json"])) {
                $_update_user_data["address_json"] = $_data["address_json"];
            }
//            详细地址
            if (isset($_data["address_detail"])) {
                $_update_user_data["address_detail"] = $_data["address_detail"];
            }


            if (isset($_data["email"])) {
                $_update_user_data["email"] = $_data["email"];
            }
            if (isset($_data["wechart_id"])) {
                $_update_user_data["wechart_id"] = $_data["wechart_id"];
            }
            if (isset($_data["real_name"])) {
                $_update_user_data["real_name"] = $_data["real_name"];
            }
//            直播平台
            if (isset($_data["live_platform"])) {
                $_update_user_data["live_platform"] = $_data["live_platform"];
            }
//            直播房间号
            if (isset($_data["live_id"])) {
                $_update_user_data["live_id"] = $_data["live_id"];
            }


            Redis::hset("usr_user", $_user_token, json_encode($_usr_user));

            UsrUser::where([
                "user_id" => $_usr_user["user_id"]
            ])->update($_update_user_data);

        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_data
        );
    }


    /**
     * @param Request $request
     * @return array
     * @abstract 获取用户徽章列表
     * @author pengjl
     * @time 2021/5/7 21:50
     */
    public function postMyMedal(Request $request)
    {
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_token = $request->header("token");
        if ($_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        $_user_medal = UsrUserController::getUserMedal($_token, 0, $_language);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_medal" => $_user_medal
            )
        );

    }

    /**
     * @abstract 清除用户历史记录数据
     * @return array
     */
    public function postUserAchievementDelete(Request $request)
    {
        Redis::select(1);

        $_token = $request->header('token');
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_usr_user["achievement"] = array(
            "duration" => 0,
            "speed_max" => 0,
            "circle_count" => 0,
            "endurance_max" => 0,
            "play_count" => 0,
            "thrmin" => 0,
            "half_marathon" => 0,
            "distance_max" => 0,
        );

        Redis::hset("usr_user", $_token, json_encode($_usr_user));

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

    /**
     * @abstract 清除用户已获得徽章数据
     * @return array
     */
    public function postUserMedalDelete(Request $request)
    {
        Redis::select(1);
        $_token = $request->header('token');
        $_usr_user = json_decode(Redis::hget("usr_user", $_token), true);

        $_usr_user["my_medal"] = array();

        Redis::hset("usr_user", $_token, json_encode($_usr_user));

        UserMedalAssociated::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"]
        ])->delete();

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

    /**
     * @abstract 用户头像变更
     * @param Request $request
     * @return array
     */
    public function postUserHeaderImgUpload(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (isset($_usr_user['sys_user_type_id']) && $_usr_user['sys_user_type_id'] != '1809649560981504') { //必须注册用户才能修改
            return array(
                "code" => 0,
                "msg" => "游客暂不能修改"
            );
        }

        $_file = $_FILES["file"];

        //        文件路径处理、移动文件
        $_file_path = fileMoveController::getFilePath("user_image", $_file["name"]);
        move_uploaded_file($_file["tmp_name"], $_file_path["file_path"]);

//        更新redis数据
        $_usr_user["user_img"] = $_file_path["file_path"];
        Redis::hset("usr_user", $_user_token, json_encode($_usr_user));


        UsrUser::where([
            "user_id" => $_usr_user["user_id"]
        ])->update(["user_img" => $_usr_user["user_img"]]);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_img_path" => StaticDataController::$_server_url . '/' . $_file_path["file_path"],
                "file_path" => $_file_path,
                "file" => $_file
            )
        );
    }


    /**
     * @abstract 获取用户摇跑指数
     * @param Request $request
     * @return array
     */
    public function postRunballExponent(Request $request)
    {

        Redis::select(1);

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_runball_exponent = 0;
        if (isset($_usr_user["achievement"]) && isset($_usr_user["achievement"]["exponent_molecular"]) && isset($_usr_user["achievement"]["exponent_denominator"])) {
            $_thrmin = $_usr_user["achievement"]["exponent_molecular"];
            $_half_marathon = $_usr_user["achievement"]["exponent_denominator"];
            $_half_marathon_min = intval($_half_marathon / 60);
            $_half_marathon_sec = $_half_marathon - $_half_marathon_min * 60;
            $_half_marathon_fmt = $_half_marathon_min + round($_half_marathon_sec / 60, 2);
            $_runball_exponent = $_half_marathon > 0 ? round($_thrmin / $_half_marathon_fmt, 2) : 0;

        }

        $_exponent_data = array(
            "runball_exponent" => $_runball_exponent,
            "exponent_title" => $_language == "zh-CN" ? Redis::hget("sys_setting", "exponent_title_description_zh") : Redis::hget("sys_setting", "exponent_title_description_en"),
            "exponent_molecular" => Redis::hget("sys_setting", "exponent_molecular"),
            "exponent_denominator" => Redis::hget("sys_setting", "exponent_denominator"),
        );

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => round($_runball_exponent, 2)
        );
    }


    /**
     * @abstract 获取用户摇跑指数，更新
     * @param Request $request
     * @return array
     */
    public function postRunballExponentV2(Request $request)
    {

        Redis::select(1);

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_arrOfUserAchivement = UserAchievement::where([
            "user_id" => $_usr_user["user_id"]
        ])->select("exponent_molecular", "exponent_denominator", "runball_exponent")->get();

        $_runball_exponent = 0;

        if ($_arrOfUserAchivement[0]["exponent_molecular"] > 0 && $_arrOfUserAchivement[0]["exponent_denominator"] > 0) {
            $_runball_exponent = round($_arrOfUserAchivement[0]["exponent_molecular"] / ($_arrOfUserAchivement[0]["exponent_denominator"] / 60), 2);


            UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update(["runball_exponent" => $_runball_exponent]);
        }

        $_exponent_data = array(
            "runball_exponent" => (string)$_runball_exponent,
            "exponent_title" => $_language == "zh-CN" ? Redis::hget("sys_setting", "exponent_title_description_zh") : Redis::hget("sys_setting", "exponent_title_description_en"),
            "exponent_molecular" => Redis::hget("sys_setting", "exponent_molecular"),
            "exponent_denominator" => Redis::hget("sys_setting", "exponent_denominator"),
            "exponent_molecular_tips_en" => Redis::hget("sys_setting", "exponent_molecular_tips_en"),
            "exponent_molecular_tips_zh" => Redis::hget("sys_setting", "exponent_molecular_tips_zh"),
            "exponent_denominator_tips_en" => Redis::hget("sys_setting", "exponent_denominator_tips_en"),
            "exponent_denominator_tips_zh" => Redis::hget("sys_setting", "exponent_denominator_tips_zh"),
        );

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_exponent_data
        );
    }

    /**
     * @abstract 获取用户摇跑指数，更新
     * @param Request $request
     * @return array
     */
    public function postRunballExponentV3(Request $request)
    {

        Redis::select(1);

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);


        $_exponent_data = array(
            "exponent_title" => $_language == "zh-CN" ? Redis::hget("sys_setting", "exponent_title_description_zh") : Redis::hget("sys_setting", "exponent_title_description_en"),
            "exponent_molecular" => Redis::hget("sys_setting", "exponent_molecular"),
            "exponent_denominator" => Redis::hget("sys_setting", "exponent_denominator"),
            "exponent_molecular_tips_en" => Redis::hget("sys_setting", "exponent_molecular_tips_en"),
            "exponent_molecular_tips_zh" => Redis::hget("sys_setting", "exponent_molecular_tips_zh"),
            "exponent_denominator_tips_en" => Redis::hget("sys_setting", "exponent_denominator_tips_en"),
            "exponent_denominator_tips_zh" => Redis::hget("sys_setting", "exponent_denominator_tips_zh"),
        );
        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_exponent_data
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/17 15:09
     * @abstract _APP上传用户摇跑指数
     */
    public function postRunballExponentAdd(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!isset($_data["exponent_molecular"]) || !isset($_data["exponent_denominator"])) {
            return SystemErrorController::paramtersError($_language);
        }
        
        if (!isset($_data["exponent_speed_max"])) {
            $_data["exponent_speed_max"] = 0;
        }

        $_runball_exponent = $_data["exponent_denominator"] > 0 ? round($_data["exponent_molecular"] / ($_data["exponent_denominator"] / 60), 2) : 0;

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);


        $_abnormal_index = StaticDataController::$_abnormal_index;
        $_data["exponent_molecular"] = $_data["exponent_molecular"] < $_abnormal_index['exponent_molecular'] ? $_data["exponent_molecular"] : 0;
        $_data["exponent_denominator"] = $_data["exponent_denominator"] > $_abnormal_index['exponent_denominator'] ? $_data["exponent_denominator"] : 0;
        $_runball_exponent = $_runball_exponent < $_abnormal_index['runball_exponent'] ? $_runball_exponent : 0;

//        如果定义了运动数据，验证运动是否为异常数据
        if (isset($_data["user_play_id"])) {
            Redis::select(14);
            $_user_play = json_decode(Redis::get($_data["user_play_id"]), true);

//            如果是异常数据，直接返回
            if ($_user_play["is_abnormal"] == 1) {
                return array(
                    "code" => 1,
                    "msg" => "success"
                );
            }

            $_usr_user["achievement"]["exponent_molecular"] = $_user_play["exponent_molecular"] = $_data["exponent_molecular"];
            $_usr_user["achievement"]["exponent_denominator"] = $_user_play["exponent_denominator"] = $_data["exponent_denominator"];
            $_usr_user["achievement"]["exponent_speed_max"] = $_user_play["exponent_speed_max"] = $_data["exponent_speed_max"];
            $_usr_user["achievement"]["exponent"] = $_usr_user["achievement"]["runball_exponent"] = $_user_play["exponent"] = $_runball_exponent;
            $_user_play["user_id"] = $_usr_user["user_id"];

//            数据存储
            Redis::set($_data["user_play_id"], json_encode($_user_play));

            //临时储存运动数据
            UserPlayController::saveUserPlay($_user_play);

            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_usr_user));
        }

        $_arrOfUserAchivement = UserAchievement::where([
            "user_id" => $_usr_user["user_id"]
        ])->select("exponent_molecular", "exponent_denominator", "runball_exponent")->get();

        if (count($_arrOfUserAchivement) == 1) {
            $_arrOfUserAchivementData = array();

            if ($_arrOfUserAchivement[0]["runball_exponent"] <= $_runball_exponent) {
                $_arrOfUserAchivementData["runball_exponent"] = $_runball_exponent;
                $_arrOfUserAchivementData["runball_exponent_time"] = time();
            }

//            如果指数分子，分母存在更新，
            if ($_arrOfUserAchivement[0]["exponent_molecular"] <= $_data["exponent_molecular"]) {
                $_arrOfUserAchivementData["exponent_molecular"] = $_data["exponent_molecular"];
                $_arrOfUserAchivementData["exponent_molecular_time"] = time();
            }

            if ($_arrOfUserAchivement[0]["exponent_denominator"] == 0 || $_arrOfUserAchivement[0]["exponent_denominator"] >= $_data["exponent_denominator"]) {
                $_arrOfUserAchivementData["exponent_denominator"] = $_data["exponent_denominator"];
            }

            if ($_user_token == '9cbb2bdda6b4c7187143e4ce4b8d1cfa') {
                Log::info('******************', $_arrOfUserAchivementData);
            }

//            如果存在更新内容
            if (count($_arrOfUserAchivementData) > 0) {
                UserAchievement::where(["user_id" => $_usr_user["user_id"]])->update($_arrOfUserAchivementData);
            }
        }

        //
        if (isset($_data['matchs_stage_id'])) {
            $_match_data = MatchsStage::leftjoin('sys_match', 'sys_match.sys_match_id', 'matchs_stage.sys_match_id')
                ->where([
                    "matchs_stage.status" => 1,
                    "matchs_stage.matchs_stage_id" => $_data["matchs_stage_id"]
                ])->first(['matchs_stage.is_exponent', 'sys_match.is_group']);
            $_is_exponent = $_match_data->is_exponent ?? 0;
            $_is_group = $_match_data->is_group ?? 0;
            if ($_is_exponent == 1) {
                if ($_is_group == 1) {
//                    $_matchs_user_grade_id = MatchsUserGrade::where([
//                        "matchs_user_grade.is_group" => $_is_group,
//                        "user_group_associated.status" => 1,
//                        "user_group_associated.user_id" => $_usr_user["user_id"],
//                        "matchs_stage_id" => $_data["matchs_stage_id"]
//                    ])->where('matchs_user_grade.match_grade', '!=', 10000000000)
//                        ->join("user_group_associated", function ($join) {
//                            $join->on("matchs_user_grade.user_group_id", "=", "user_group_associated.user_group_id");
//                        })
//                        ->value('matchs_user_grade.matchs_user_grade_id');
//                    if ($_matchs_user_grade_id) {
//                        MatchsUserGrade::where([
//                            "matchs_user_grade_id" => $_matchs_user_grade_id
//                        ])->update(['match_grade' => $_runball_exponent]);
//                    }

                    if (isset($_data["user_group_id"]) && $_data["user_group_id"]) {
                        MatchsUserGrade::where([
                            "matchs_user_grade.is_group" => $_is_group,
                            "matchs_stage_id" => $_data["matchs_stage_id"],
                            "user_group_id" => $_data["user_group_id"],
                        ])->update(['match_grade' => $_runball_exponent]);
                    }

                } else {
                    MatchsUserGrade::where([
                        "matchs_user_grade.is_group" => $_is_group,
                        "matchs_stage_id" => $_data["matchs_stage_id"],
                        "user_id" => $_usr_user["user_id"],
                    ])->update(['match_grade' => $_runball_exponent]);
                }
            }
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "runball_exponent" => (string)$_runball_exponent
            )
        );

    }


    /**
     * 检查是否可获取免费会员
     *
     * @return bool
     */
    private function checkFreeMember()
    {
        $date = date('Y-m-d');
        if ($date >= self::MEMBERS_START_DATE && $date <= self::MEMBERS_STOP_DATE) {
            return true;
        }

        return false;
    }


    /**
     * 更新用户设备与系统信息
     *
     * @param Request $request
     * @return array
     */
    public function postUserDeviceChange(Request $request)
    {
        $_data = $request->all();
        $_version = $_data['version'] ?? '';    //版本号
        $_channel = $_data['channel'] ?? '';    //渠道
        $_device_model = $_data['device_model'] ?? '';    //手机型号

        Redis::select(1);
        $_user_token = $request->header("token");
        $_user_info = json_decode(Redis::hget("usr_user", $_user_token), true);

        $updateList = [];
        if ($_version != ($_user_info['version'] ?? '')) {
            $updateList['version'] = $_version ?? '';
        }
        if ($_device_model != ($_user_info['device_model'] ?? '')) {
            $updateList['device_model'] = $_device_model ?? '';
        }
        if ($_channel != ($_user_info['channel'] ?? '')) {
            $updateList['channel'] = $_channel ?? '';
        }
        if ($updateList) {  //更新
            UsrUser::where('user_id', $_user_info['user_id'])->update($updateList);
        }

        $_user_info['version'] = $_version ?: ($_user_info['version'] ?? '');
        $_user_info['device_model'] = $_device_model ?: ($_user_info['device_model'] ?? '');
        $_user_info['channel'] = $_channel ?: ($_user_info['channel'] ?? '');

//                写入缓存
        Redis::select(1);
        Redis::hset("usr_user", $_user_token, json_encode($_user_info));

        return [
            'code' => 1,
            'msg' => 'success'
        ];
    }

    /**
     * 账号注销
     * @param Request $request
     * @return array|JsonResponse
     * User: zxw
     * Date: 2021/12/03 11:42
     */
    public function accountCancel(Request $request)
    {
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header('token');
        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $list = UsrUser::where('user_id',$_usr_user['user_id'])->first();

        if (empty($list)) return $this->error(ErrorCode::SEVER_ERROR,trans('messages.account_does_not_exist'));

        $list->status = -1;
        $list->phone = $list->phone."_back";
        $list->email = $list->email."_back";
        $list->open_weixin_id = $list->open_weixin_id."_back";
        $list->open_qq_id = $list->open_qq_id."_back";
        $list->open_weibo_id = $list->open_weibo_id."_back";
        $list->open_alipay_id = $list->open_alipay_id."_back";
        $list->open_ios_id = $list->open_ios_id."_back";
        $list->open_twitter_id = $list->open_twitter_id."_back";
        $list->open_facebook_id = $list->open_facebook_id."_back";
        $list->updated_time = Carbon::now()->toDateTimeString();
        $list->save();

        Redis::select(1);
        Redis::hdel("usr_user", $_user_token);

        return $this->success(null,trans('messages.account_cancelled_successfully'));
    }

    /**
     * 三方登录
     * @return JsonResponse
     * User: zxw
     * Date: 2022/3/21 9:35
     */
    public function tripartite(UserPostTripartiteRequest $request): JsonResponse
    {
        $data = $request->all();
        $list = [];
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_version = $data['version'] ?? '';    //版本号
        $_channel = $data['channel'] ?? '';    //渠道
        $_device_model = $data['device_model'] ?? '';    //手机型号
        //TODO 请求次数限制...



        $map = [];
        $key = null;
        if (isset($data['open_weixin_id'])) {
            $key = "weixin";
            $map['usr_user.open_weixin_id'] = $data['open_weixin_id'];
        }
        if (isset($data['open_qq_id'])) {
            $key = "qq";
            $map['usr_user.open_qq_id'] = $data['open_qq_id'];
        }
        if (isset($data['open_weibo_id'])){
            $key = "weibo";
            $map['usr_user.open_weibo_id'] = $data['open_weibo_id'];
        }
        if (isset($data['open_alipay_id'])){
            $key = "alipay";
            $map['usr_user.open_alipay_id'] = $data['open_alipay_id'];
        }
        if (isset($data['open_ios_id'])){
            $key = "ios";
            $map['usr_user.open_ios_id'] = $data['open_ios_id'];
        }
        if (isset($data['open_twitter_id'])) {
            $key = "twitter";
            $map['usr_user.open_twitter_id'] = $data['open_twitter_id'];
        }
        if (isset($data['open_facebook_id'])) {
            $key = "facebook";
            $map['usr_user.open_facebook_id'] = $data['open_facebook_id'];
        }

        //查询当前$map是否存在绑定用户
        $_arrOfUsrUser = UsrUser::where($map)
            ->where('usr_user.status',1)
            ->join("sys_user_type", function ($join) {
                $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
            })->join("sys_sex", function ($join) {
                $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
            })->join("sys_country", function ($join) {
                $join->on("usr_user.sys_country_id", "=", "sys_country.sys_country_id");
            })->select(
                "usr_user.user_id", "usr_user.is_group", "usr_user.status", "usr_user.user_name", "usr_user.self_description", "usr_user.user_name_change"
                , "usr_user.user_img_change", "usr_user.user_img", "usr_user.access_token as token", "sys_sex.sys_sex_id", "sys_sex.sex_name"
                , "usr_user.device_uid", "sys_country.sys_country_id", "sys_country.name_cn", "sys_user_type.sys_user_type_id"
                , "sys_user_type.user_type_name", "usr_user.phone", "usr_user.phone_prefix", "usr_user.access_token"
                , "usr_user.is_members", "usr_user.members_status", "usr_user.members_exptime", "usr_user.share_code", "usr_user.integral", "usr_user.birthday"
                , "usr_user.address", "usr_user.version", "usr_user.device_model", "usr_user.channel", "usr_user.sys_sex_id_change", "usr_user.photo_text", "third_info"
            )->get();

        if (count($_arrOfUsrUser) == 1){//已注册
            //写入缓存
            Redis::select(1);
            //删除原数据
            $_a = Redis::hdel("usr_user", $_arrOfUsrUser[0]["access_token"]);
            Log::info("删除原token：" . $_arrOfUsrUser[0]["access_token"] . "，状态：" . $_a);

            //已找到用户，刷新token返回
            $_user_info = $_arrOfUsrUser[0];
            $_user_token = md5($_user_info["user_id"] . $_user_info["user_name"] . time() . rand(100000, 999999));
            $_user_info["token"] = $_user_token;
            $_user_info["access_token"] = $_user_token;

            $_b = Redis::hset("usr_user", $_user_token, json_encode($_user_info));
            Log::info("设置token：" . $_user_token . "，状态：" . $_b);

            $_user_info["achievement"] = $_user_achievement = UsrUserController::getUserAchievement($_user_token);
            //获取用户勋章列表
            $_user_info["my_medal"] = UsrUserController::getUserMedal($_user_token, 4, $_language);

            UsrUser::where(["user_id" => $_user_info["user_id"]])->update(["access_token" => $_user_token]);

            $_user_img = $_user_info["user_img"];
            if (strpos($_user_img, 'http') === false) {
                $_user_info["user_img"] = StaticDataController::$_server_url . "/" . $_user_img;
            }

            if ($_user_info["share_code"] == null) {
                $_user_info["share_code"] = "";
            }
            if ($_user_info["members_exptime"] == null) {
                $_user_info["members_exptime"] = "";
            }

            $updateList = [];
            if ($_version != $_user_info['version']) {
                $updateList['version'] = $_version ?? '';
            }
            if ($_device_model != $_user_info['device_model']) {
                $updateList['device_model'] = $_device_model ?? '';
            }
            if ($_channel != $_user_info['channel']) {
                $updateList['channel'] = $_channel ?? '';
            }
            if (isset($data['third_info']) && !empty($data['third_info'])){
                $_user_info = $_user_info->toArray();
                $_user_info['third_info'] = json_decode($_user_info['third_info'],true);
                $data['third_info'] = json_decode($data['third_info'],true);
                $_user_info['third_info'][$key] = $data['third_info'];
                $_user_info['third_info'] = json_encode($_user_info['third_info']);
            }
            if ($updateList) {  //更新
                UsrUser::where('user_id', $_user_info['user_id'])->update($updateList);
            }

            $_user_info['version'] = $_version ?: ($_user_info['version'] ?? '');
            $_user_info['device_model'] = $_device_model ?: ($_user_info['device_model'] ?? '');
            $_user_info['channel'] = $_channel ?: ($_user_info['channel'] ?? '');

            unset($_user_info['third_info']);
            //写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_user_info));

            $list['user_info'] = $_user_info;

        }else{//无注册
            //创建随机用户
            $_arrOfUserResult = $this->randUser("chinese", $_language, "", $_version, $_channel, $_device_model);

            $_arrOfUser = $_arrOfUserResult["data"]["user_info"];
            $_user_token = $_arrOfUser["token"];

            $_arrOfUser['is_group'] = -1;
            $_arrOfUser['birthday'] = null;
            $_arrOfUser['address'] = '';
            $updateData = [
                "sys_user_type_id" => "1809649560981504",
                "access_token" => $_user_token,
                'is_group' => -1
            ];
            isset($data['open_weixin_id']) ? $_arrOfUserResult["data"]["user_info"]['open_weixin_id'] = $updateData['open_weixin_id'] = $data['open_weixin_id'] : '';
            isset($data['open_qq_id']) ? $_arrOfUserResult["data"]["user_info"]['open_qq_id'] = $updateData['open_qq_id'] = $data['open_qq_id'] : '';
            isset($data['open_weibo_id']) ? $_arrOfUserResult["data"]["user_info"]['open_weibo_id'] = $updateData['open_weibo_id'] = $data['open_weibo_id'] : '';
            isset($data['open_alipay_id']) ? $_arrOfUserResult["data"]["user_info"]['open_alipay_id'] = $updateData['open_alipay_id'] = $data['open_alipay_id'] : '';
            isset($data['open_ios_id']) ? $_arrOfUserResult["data"]["user_info"]['open_ios_id'] = $updateData['open_ios_id'] = $data['open_ios_id'] : '';
            isset($data['open_twitter_id']) ? $_arrOfUserResult["data"]["user_info"]['open_twitter_id'] = $updateData['open_twitter_id'] = $data['open_twitter_id'] : '';
            isset($data['open_facebook_id']) ? $_arrOfUserResult["data"]["user_info"]['open_facebook_id'] = $updateData['open_facebook_id'] = $data['open_facebook_id'] : '';
            if (isset($data['third_info']) && !empty($data['third_info'])){
                $_arrOfUserResult["data"]["user_info"]['third_info'] = $updateData['third_info'] = $data['third_info'];
            }else{
                $_arrOfUserResult["data"]["user_info"]['third_info'] = null;
            }

            // 判断是否免费会员
            if ($this->checkFreeMember()) {
                UserMembersController::getMemberData($updateData);  //追加会员参数
            }

            UsrUser::where([
                "user_id" => $_arrOfUser["user_id"]
            ])->update($updateData);

            $_arrOfUserResult["data"]["user_info"]['sys_user_type_id'] = $_arrOfUser["sys_user_type_id"] = "1809649560981504";
            $_arrOfUserResult["data"]["user_info"]['user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['sys_user_type_name'] = $_arrOfUser["sys_user_type_name"] = "注册用户";
            $_arrOfUserResult["data"]["user_info"]['is_group'] = -1;
            $_arrOfUserResult["data"]["user_info"]['birthday'] = null;
            $_arrOfUserResult["data"]["user_info"]['address'] = '';
            $_arrOfUserResult["data"]["user_info"]['photo_text'] = '';

            unset($_arrOfUser['third_info'],$_arrOfUserResult["data"]["user_info"]['third_info']);
            //写入缓存
            Redis::select(1);
            Redis::hset("usr_user", $_user_token, json_encode($_arrOfUser));

            $list = $_arrOfUserResult["data"];
        }

        return $this->success($list);
    }

}
