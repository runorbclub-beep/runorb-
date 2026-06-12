<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Admin\Activity\ActivityController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\SysActivity;


class WebsiteActivityController extends Controller
{


    /**
     * @author jkd
     * @time 2021/7/14 10:13
     * @abstract _活动列表
     */
    public function activityList()
    {
        $_data = request()->input();

        $_search = $_data["search"] ?? '';
        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 10;
        $_type = $_data["type"] ?? '';

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => ActivityController::getActivityList($_page, $_limit, $_type, $_search)
        );
    }


    /**
     * @author jkd
     * @time 2021/7/14 10:12
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
            'sys_activity_id', 'created_date', 'title_cn', 'title_en', 'type', 'content_cn', 'content_en', 'view_num', 'img'
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


}
