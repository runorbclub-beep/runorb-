<?php
/**
 * 更多活动
 */

namespace App\Http\Controllers\Admin\Activity;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ActivityController extends Controller
{

    /**
     * @author jkd
     * @time 2021/7/13 18:03
     * @abstract _新增，编辑活动
     */
    public function postActivityAdd()
    {
        $_data = request()->input();

        $_token_key = "admin_user_token:" . request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = $_data['language'] ?? 'zh-CN';

        if (!isset($_data["title_cn"]) || !isset($_data["content_cn"]) || !isset($_data["img"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_base_url = StaticDataController::$_server_url . "/";
        $_img = $_data["img"];
        $_img = str_replace($_base_url, "", $_img);

        $_arrOfSysActivityData = [
            "title_cn" => $_data["title_cn"] ?? '',
            "title_en" => $_data["title_en"] ?? '',
            "content_cn" => $_data["content_cn"] ?? '',
            "content_en" => $_data["content_en"] ?? '',
            "type" => $_data["type"] ?? 1,
            "img" => $_img ?? '',
            "created_date" => $_data['created_date'] ?? date('Y-m-d H:i:s')
        ];

//        已存在ID，编辑
        if (isset($_data["sys_activity_id"])) {
            $_arrOfSysActivityData["updated_uid"] = $_admin_user["admin_user_id"];

            SysActivity::where([
                "sys_activity_id" => $_data["sys_activity_id"]
            ])->update($_arrOfSysActivityData);

            return [
                "code" => 1,
                "msg" => "编辑成功"
            ];
        } else {
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfSysActivityData["sys_activity_id"] = $_sno->nextId();
            $_arrOfSysActivityData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfSysActivityData["status"] = 1;

            SysActivity::create($_arrOfSysActivityData);

            return [
                "code" => 1,
                "msg" => "创建成功"
            ];
        }
    }


    /**
     * @author jkd
     * @time 2021/7/14 09:41
     * @abstract _查看活动详情
     */
    public function postActivityInfo()
    {
        $_data = request()->input();
//        语言
        $_language = $_data['language'] ?? 'zh-CN';

        if (!isset($_data["sys_activity_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfActivityInfo = SysActivity::where([
            "sys_activity_id" => $_data["sys_activity_id"],
            "status" => 1,
        ])->select(
            'sys_activity_id', 'created_date', 'title_cn', 'title_en', 'type', 'content', 'content_en', 'view_num', 'img'
        )->first();

        if ($_arrOfActivityInfo) {
            return [
                "code" => 1,
                "msg" => "success",
                "data" => $_arrOfActivityInfo
            ];
        } else {
            return [
                "code" => 0,
                "msg" => "未查询到内容"
            ];
        }
    }


    /**
     * @author jkd
     * @time 2021/7/14 09:50
     * @abstract _查询活动列表
     */
    public function postActivityList(Request $request)
    {

        $_data = request()->input();

        $_search = $_data["search"] ?? '';
        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 10;
        $_type = $_data["type"] ?? '';

        return [
            "code" => 1,
            "msg" => "success",
            "data" => self::getActivityList($_page, $_limit, $_type, $_search)
        ];
    }

    /**
     * @author jkd
     * @time 2021/7/14 09:54
     * @abstract _删除活动
     */
    public function postActivityDelete(Request $request)
    {
        $_data = request()->input();

        $_token_key = "admin_user_token:" . request()->header("token");

        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = $_data['language'] ?? 'zh-CN';

        if (!isset($_data["sys_activity_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        SysActivity::where(["sys_activity_id" => $_data["sys_activity_id"]])->update(["status" => 0, "updated_uid" => $_admin_user["admin_user_id"]]);

        return [
            "code" => 1,
            "msg" => "删除成功"
        ];
    }


    /**
     * @author jkd
     * @time 2021/7/14 09:55
     * @abstract _查询活动列表公共方法
     */
    public static function getActivityList($_page, $_limit, $_type, $_search)
    {

        $_offset = ($_page - 1) * $_limit;

        $_arrOfActivityQuery = SysActivity::where([
            "status" => 1,
        ]);

        if ($_search != '') {
            $_arrOfActivityQuery = $_arrOfActivityQuery->where(function ($query) use ($_search) {
                $query->where("title_cn", "like", '%' . $_search . "%")
                    ->orWhere("title_en", "like", '%' . $_search . "%");
            });
        }

        if ($_type != '') {
            $_arrOfActivityQuery = $_arrOfActivityQuery->where(["type" => $_type]);
        }

        $_arrOfActivityQuery = $_arrOfActivityQuery->select('sys_activity_id', 'created_date', 'title_cn', 'title_en', 'type', 'content_cn', 'content_en', 'view_num', 'img');

        $_arrOfActivityCount = $_arrOfActivityQuery->count();
        $_arrOfActivity = $_arrOfActivityQuery->orderBy('created_time', 'desc')->take($_offset)->limit($_limit)->get();

        foreach ($_arrOfActivity as $key => $value) {
            $_arrOfActivityNode = [
                "created_date" => $value["created_date"],
                "sys_activity_id" => (string)$value["sys_activity_id"],
                "title_cn" => $value["title_cn"],
                "title_en" => $value["title_en"],
                "type" => $value["type"],
                "content_cn" => $value["content_cn"],
                "content_en" => $value["content_en"],
                "view_num" => $value["view_num"],
                "img" => StaticDataController::$_server_url . "/" . $value["img"],
            ];
            $_arrOfActivity[$key] = $_arrOfActivityNode;
        }

        return [
            "count" => $_arrOfActivityCount,
            "list" => $_arrOfActivity
        ];
    }

}
