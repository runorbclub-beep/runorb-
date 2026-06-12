<?php

namespace App\Http\Controllers\Admin\User;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Api\UserPlayController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Http\Requests\Admin\User\UserGetAddMatchAwardRequest;
use App\Models\UserMedalAssociated;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Models\UsrUser;
use App\Services\MatchAwardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @author pengjl
 * @time 2021/5/11 18:33
 * Class UserController
 * @package App\Http\Controllers\Admin\User
 * @abstract _管理后台用户相关操作
 */
class UserController extends Controller
{


    /**
     * @author pengjl
     * @time 2021/5/11 18:34
     * @abstract _用户列表查询
     */
    public function postUserList()
    {

        $_data = request()->input();

        $_search = isset($_data["search"]) ? $_data["search"] : '';
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        $_sys_user_type_id = isset($_data["sys_user_type_id"]) ? $_data["sys_user_type_id"] : "";
        $_members_status = isset($_data["members_status"]) ? $_data["members_status"] : "";


        $_arrOfUserQuery = UsrUser::where([
            "usr_user.status" => 1,
        ])->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->join("sys_sex", function ($join) {
            $join->on("usr_user.sys_sex_id", "=", "sys_sex.sys_sex_id");
        });


        if ($_search !== '') {
            $_arrOfUserQuery = $_arrOfUserQuery->where(function ($query) use ($_search) {
                $query->where("usr_user.user_name", "like", '%' . $_search . "%")->orwhere("usr_user.phone", "like", '%' . $_search . "%");
            });
        }

//        存在用户类型筛选
        if ($_sys_user_type_id != "") {
            $_arrOfUserQuery = $_arrOfUserQuery->where(["sys_user_type.sys_user_type_id" => $_sys_user_type_id]);
        }

        if ($_members_status !== "") {
            $_arrOfUserQuery = $_arrOfUserQuery->where(["usr_user.members_status" => $_members_status]);

        }

        $_arrOfUserQuery = $_arrOfUserQuery->select(
            "usr_user.user_id", "usr_user.user_name", "usr_user.user_img", "usr_user.self_description", "usr_user.phone", "usr_user.address"
            , "sys_user_type.user_type_name", "sys_sex.sex_name", "usr_user.is_members", "usr_user.members_status", "usr_user.integral"
        )->orderBy("usr_user.user_id", "DESC");

        $_arrOfUserCount = $_arrOfUserQuery->count();
        $_arrOfUser = $_arrOfUserQuery->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfUser as $key => $value) {
            $value["user_img"] = StaticDataController::$_server_url . "/" . $value["user_img"];
            $_arrOfUser[$key] = $value;
        }


        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfUserCount,
                "list" => $_arrOfUser
            )
        );
    }


    /**
     * @author pengjl
     * @time 2021/6/1 11:15
     * @abstract _用户详情
     */
    public function postUserInfo()
    {
        $_data = request()->input();

        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["user_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

//        用户基本信息
        $_arrOfUser = UsrUser::where([
            "usr_user.status" => 1,
            "usr_user.user_id" => $_data["user_id"],
            "ua.status" => 1,
        ])->join("user_achievement as ua", function ($join) {
            $join->on("usr_user.user_id", "=", "ua.user_id");
        })->join("sys_user_type", function ($join) {
            $join->on("usr_user.sys_user_type_id", "=", "sys_user_type.sys_user_type_id");
        })->select(
            "usr_user.user_id", "usr_user.user_name", "sys_user_type.user_type_name", "usr_user.user_img", "usr_user.birthday", "usr_user.self_description"
            , "usr_user.phone", "usr_user.address", "ua.duration", "ua.speed_max", "ua.circle_count", "ua.play_count", "ua.exponent_denominator"
            , "ua.exponent_molecular", "usr_user.is_members", "usr_user.members_exptime", "usr_user.members_status", "usr_user.members_join_time"
            , "usr_user.address_detail", "usr_user.integral"
        )->get();


        if (count($_arrOfUser) == 0) {
            return array(
                "code" => 0,
                "msg" => "用户数据未找到"
            );
        }

        $_runball_exponent = 0;
        if ($_arrOfUser[0]["exponent_denominator"] > 0 && $_arrOfUser[0]["exponent_molecular"] > 0) {
            $_runball_exponent = round($_arrOfUser[0]["exponent_molecular"] / $_arrOfUser[0]["exponent_denominator"], 2);
        }

        $_arrOfUserInfo = array(
            "user_id" => $_arrOfUser[0]["user_id"],
            "user_name" => $_arrOfUser[0]["user_name"],
            "user_type_name" => $_arrOfUser[0]["user_type_name"],
            "user_img" => StaticDataController::$_server_url . "/" . $_arrOfUser[0]["user_img"],
            "self_description" => $_arrOfUser[0]["self_description"],
            "phone" => $_arrOfUser[0]["phone"],
            "birthday" => $_arrOfUser[0]["birthday"],
            "address" => $_arrOfUser[0]["address"],
            "address_detail" => $_arrOfUser[0]["address_detail"],
            "is_members" => $_arrOfUser[0]["is_members"],
            "integral" => $_arrOfUser[0]["integral"],
            "members_status" => $_arrOfUser[0]["members_status"],
            "members_exptime" => $_arrOfUser[0]["members_exptime"] != null ? date("Y-m-d", $_arrOfUser[0]["members_exptime"]) : "",
            "members_join_time" => $_arrOfUser[0]["members_join_time"] != null ? date("Y-m-d H:i:s", $_arrOfUser[0]["members_join_time"]) : "",
            "achievement" => array(
                "duration" => $_arrOfUser[0]["duration"],
                "speed_max" => $_arrOfUser[0]["speed_max"],
                "circle_count" => $_arrOfUser[0]["circle_count"],
                "play_count" => $_arrOfUser[0]["play_count"],
                "runball_exponent" => $_runball_exponent
            ),
            "medal" => array()
        );

        $_arrOfUserMedal = UserMedalAssociated::where([
            "user_medal_associated.status" => 1,
            "user_medal_associated.user_id" => $_data["user_id"],
        ])->join("sys_medal", function ($join) {
            $join->on("user_medal_associated.sys_medal_id", "=", "sys_medal.sys_medal_id");
        })->select(
            "sys_medal.sys_medal_id", "sys_medal.user_medal_name_cn", "sys_medal.description_cn", "sys_medal.level_name"
            , "sys_medal.medal_image_active"
        )->get();

        foreach ($_arrOfUserMedal as $value) {
            array_push($_arrOfUserInfo["medal"], array(
                "sys_medal_id" => $value["sys_medal_id"],
                "medal_name" => $value["user_medal_name_cn"],
                "description" => $value["description_cn"],
                "level_name" => $value["level_name"],
                "medal_image" => $value["medal_image_active"],
            ));
        }


//        近30天运动数据
        $_start_time = strtotime("-30 day");

        $_arrOfUserPlayKey = array();
        for ($_i = 30; $_i > 0; $_i--) {
            $_arrOfUserPlayKey[date("m-d", strtotime("-" . $_i . " day"))] = 0;
        }


        $_arrOfUserPlay = UserPlay::where(["status" => 1, "user_id" => $_data["user_id"]])
            ->where("start_time", ">=", strtotime(date("Y-m-d", $_start_time) . "00:00:00"))
            ->select("user_play_id", "start_time")->get();


        foreach ($_arrOfUserPlay as $value) {
            $_date = date("m-d", $value["start_time"]);

            if (array_key_exists($_date, $_arrOfUserPlayKey)) {
                $_arrOfUserPlayKey[$_date] += 1;
            }

        }

        $_arrOfSerise = array(
            array(
                "name" => "运动次数",
                "type" => "line",
                "data" => array()
            )
        );

        $_user_play_count_max = 0;
        foreach ($_arrOfUserPlayKey as $value) {
            $_user_play_count_max = $_user_play_count_max < $value ? $value : $_user_play_count_max;
            array_push($_arrOfSerise[0]["data"], $value);
        }

        $_arrOfYaxis = array(
            array(
                "type" => "value",
                "name" => "次数",
                "min" => 0,
                "max" => $_user_play_count_max,
                "interval" => 10,
            )
        );

        $_arrOfLegend = array(
            "data" => array(
                "name" => "运动次数"
            )
        );


        $_arrOfUserInfo["history_play_echart"] = array(
            "xAxis" => array(
                "type" => "category",
                "data" => array_keys($_arrOfUserPlayKey)
            ),
            "yAxis" => $_arrOfYaxis,
            "legend" => $_arrOfLegend,
            "series" => $_arrOfSerise,
        );

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfUserInfo
        );

    }


    /**
     * 修改用户积分
     *
     * @return array
     */
    public function postUserEdit()
    {
        $_data = request()->input();
        $_user_id = $_data['user_id'] ?? '';
        $_integral = $_data['integral'] ?? 0;

        UsrUser::where('user_id', $_user_id)->update(['integral' => $_integral]);

        return [
            "code" => 1,
            "msg" => "success"
        ];
    }


    /**
     * @author pengjl
     * @time 2021/6/1 11:42
     * @abstract _用户运动列表
     */
    public function postUserPlayList()
    {
        $_data = request()->input();

        $_search = isset($_data["search"]) ? $_data["search"] : '';
        $_page = isset($_data["page"]) ? $_data["page"] : 1;
        $_limit = isset($_data["limit"]) ? $_data["limit"] : 10;
        $_offset = ($_page - 1) * $_limit;

        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["user_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserPlayQuery = UserPlay::where([
            "status" => 1,
            "user_id" => $_data["user_id"],
        ])->select(
            "user_play_id", "duration", "speed_max", "circle_count", "compare_last", "start_time", "stop_time", "distance", "is_abnormal", "exponent_molecular", "exponent_denominator", "exponent", "marathon"
        )->orderBy("start_time", "DESC");

        $_arrOfUserPlayCount = $_arrOfUserPlayQuery->count();
        $_arrOfUserPlay = $_arrOfUserPlayQuery->skip($_offset)->take($_limit)->get();

        foreach ($_arrOfUserPlay as $value) {
            $value["start_time_format"] = date("Y-m-d H:i:s", $value["start_time"]);
            $value["stop_time_format"] = date("Y-m-d H:i:s", $value["stop_time"]);
            $value["distance_format"] = round($value["distance"] / 1000, 3);
            $value["distance_format_unit"] = "km";

            $value["duration_format"] = RankController::timeFormat($value["duration"]);
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "count" => $_arrOfUserPlayCount,
                "list" => $_arrOfUserPlay
            )
        );

    }


    /**
     * @author pengjl
     * @time 2021/6/1 12:09
     * @abstract _用户运动详情
     */
    public function postUserPlayInfo()
    {

        $_data = request()->input();
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["user_play_id"])) {
            return SystemErrorController::paramtersError($_language);
        }


        $_user_play = UserPlayController::getPlayDetail($_data["user_play_id"], true, $_language);

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => array(
                "user_play" => $_user_play
            )
        );

    }


    /**
     * @author pengjl
     * @time 2021/6/12 13:05
     * @abstract _管理员编辑用户会员状态
     */
    public function postUserJoinMembers()
    {
        $_data = request()->input();
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["user_id"]) || !isset($_data["member_status"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfUserMembersData = array(
            "members_status" => $_data["member_status"],
        );

//        审核通过
        if ($_data["member_status"] == 1) {
            $_arrOfUserMembersData["members_join_time"] = time();
            $_arrOfUserMembersData["is_members"] = 1;
        } else if ($_data["member_status"] == 2) {
//            审核驳回
            $_arrOfUserMembersData["members_join_time"] = null;
            $_arrOfUserMembersData["is_members"] = 0;
        } else if ($_data["member_status"] == 3) {
//            会员强制过期
            $_arrOfUserMembersData["members_exptime"] = time();
            $_arrOfUserMembersData["is_members"] = 0;

        } else {
            return SystemErrorController::paramtersError($_language);
        }

        UsrUser::where([
            "user_id" => $_data["user_id"]
        ])->update($_arrOfUserMembersData);

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * 根据赛事ID获取用户例赛奖章列表
     * @param Request $request
     * @param MatchAwardService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/2 15:21
     * @throws BusinessException
     */
    public function getMatchAward(Request $request, MatchAwardService $service): JsonResponse
    {
        $data = $request->all();
        if (empty($data['sys_match_id'])) return $this->error(0,trans('messages.request_parameter_error'));

        $list = $service->getMatchAward($data);
        return $this->success(data_list_format($list));
    }


    /**
     * 新增用户例赛奖章
     * @param UserGetAddMatchAwardRequest $request
     * @param MatchAwardService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/2 14:19
     * @throws BusinessException
     */
    public function addMatchAward(UserGetAddMatchAwardRequest $request, MatchAwardService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->addMatchAward($data);
        return $this->success($list);
    }


    /**
     * 删除用户例赛奖章
     * @param Request $request
     * @param MatchAwardService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/2 15:41
     * @throws BusinessException
     */
    public function delMatchAward(Request $request, MatchAwardService $service): JsonResponse
    {
        $data = $request->all();
        if (empty($data['id']) || empty($data['sys_match_id'])) return $this->error(0,trans('messages.request_parameter_error'));

        $list = $service->delMatchAward($data);
        return $this->success($list);
    }


    /**
     * 根据赛事ID获取用户例赛赛点列表
     * @param Request $request
     * @param MatchAwardService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/2 15:42
     * @throws BusinessException
     */
    public function getMatchPointList(Request $request, MatchAwardService $service): JsonResponse
    {
        $data = $request->all();
        if (empty($data['sys_match_id'])) return $this->error(0,trans('messages.request_parameter_error'));

        $list = $service->getMatchPointList($data);
        return $this->success(data_list_format($list));
    }


    /**
     * 新增用户例赛赛点/清零赛点/删除报名数据
     * @param Request $request
     * @param MatchAwardService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2022/4/2 15:44
     * @throws BusinessException
     */
    public function postMatchPoint(Request $request, MatchAwardService $service): JsonResponse
    {
        $data = $request->all();
        if (empty($data['sys_match_id'])) return $this->error(0,trans('messages.request_parameter_error'));

        $list = $service->postMatchPoint($data);
        return $this->success($list);
    }

}
