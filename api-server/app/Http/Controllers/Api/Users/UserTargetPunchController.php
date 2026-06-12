<?php

namespace App\Http\Controllers\Api\Users;

use App\Constants\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Requests\Api\Users\TargetPunch\EditTargetPunchRequest;
use App\Services\CrontabService;
use App\Services\UserTargetPunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 用户打卡目标
 * Class UserTargetPunchController
 * @package App\Http\Controllers\Api\Users
 * User: zxw
 * Date: 2021/11/24 09:08
 */
class UserTargetPunchController extends Controller
{
    /**
     * 新增/编辑用户打卡目标
     * @param EditTargetPunchRequest $request
     * @param UserTargetPunchService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/11/24 09:57
     */
    public function writeTargetPunch(EditTargetPunchRequest $request, UserTargetPunchService $service): JsonResponse
    {
        $map = [];
        $data = $request->all();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header('token');
        if ($_user_token == "") {
            return $this->error(ErrorCode::SEVER_ERROR, LanguageController::getLanguage($_language, "lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];
        $data['stop_time'] = num_month_end_day($data['start_time'], $data['num_month']);

        //计算两个时间之间有多少个月份
        $data['month_time'] = $diffMonths = diff_months($data['start_time'], $data['stop_time'], '-', false);
        //不能大于12个月
        if (count($diffMonths) > 12) {
            return $this->error(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));
        }

        //进数据库直接先查询已存在的，再与已存在的进行差集计算，然后In更新已存在的，再插入不存在的  使用事务
        $editData = $service->getMonthList($data);
        $editMonthTime = $editData->pluck('month_time');
        $diffMonthTime = collect($diffMonths)->diff($editMonthTime)->all();

        try {
            DB::transaction(function () use ($editMonthTime, $service, $_usr_user, $diffMonthTime, $data, $map) {
                if (count($diffMonthTime) > 0) {
                    //根据月份组装批量写入数据
                    foreach ($diffMonthTime as $k => $v) {
                        $status = strtotime($v) > strtotime(date('Y-m')) ? 3 : (strtotime($v) < strtotime(date('Y-m')) ? 0 : 1);
                        $map[] = [
                            'month_time' => $v,
                            'user_id' => $_usr_user['user_id'],
                            'source' => $data['source'] ?? 0,
                            'target_distance' => $data['target_distance'],
                            'min_days' => $data['min_days'],
                            'status' => $status,
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ];
                    }
                    $service->add($map);
                }
                //组装需要修改的数据
                if (count($editMonthTime) > 0) {
                    //修改已存在的数据
                    $service->editMonthTime($editMonthTime, $data);
                }
            }, 5);
        } catch (\Throwable $ex) {
            return $this->error(ErrorCode::SEVER_ERROR, $ex);
        }

        return $this->success();
    }

    /**
     * 获取用户打卡目标
     * @param Request $request
     * @param UserTargetPunchService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/11/24 10:19
     */
    public function getTargetPunch(Request $request, UserTargetPunchService $service): JsonResponse
    {
        $data = $request->all();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        $_user_token = $request->header('token');
        if ($_user_token == "") {
            return $this->error(ErrorCode::SEVER_ERROR, LanguageController::getLanguage($_language, "lack_token"));
        }
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);
        $data['user_id'] = $_usr_user['user_id'];
        $data['stop_time'] = num_month_end_day($data['start_time'], $data['num_month']);

        //计算两个时间之间有多少个月份
        $data['month_time'] = $diffMonths = diff_months($data['start_time'], $data['stop_time'], '-', false);
        //不能大于12个月
        if (count($diffMonths) > 12) {
            return $this->error(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));
        }

        $list = $service->getMonthList($data);

        return $this->success($list);
    }

    /**
     * 手动执行用户目标打卡统计
     * @param UserTargetPunchService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/11/26 11:22
     */
    public function getUserTargetPunchsConsoleList(UserTargetPunchService $service): JsonResponse
    {
        $list = $service->getUserTargetPunchsConsoleList();
        if (!$list){
            return $this->error(ErrorCode::SEVER_ERROR, false);
        }
        return $this->success(true);
    }

    /**
     * 批量处理PK结果数据错误问题
     * @return JsonResponse
     * User: zxw
     * Date: 2021/12/03 14:33
     */
    public function getPkList(): JsonResponse
    {
        $list = CrontabService::getPkList();
        return $this->success($list);
    }
}
