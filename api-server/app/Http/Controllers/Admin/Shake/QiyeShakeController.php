<?php

namespace App\Http\Controllers\Admin\Shake;


use App\Exports\Shake\QiyeShakeUserImport;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\QiyeShake;
use App\Models\QiyeShakeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Excel;

class QiyeShakeController extends Controller
{

    /***
     * 获取企业摇加油
     *
     * @param Request $request
     * @return array
     */
    public function postQiyeShakeList(Request $request)
    {
        $_data = $request->all();
        $_search = $_data["search"] ?? '';
        $_page = $_data["page"] ?? 1;
        $_limit = $_data["limit"] ?? 10;

        $_offset = ($_page - 1) * $_limit;
        $_arrOfQuery = QiyeShake::where('status', 1)
            ->where(function ($query) use ($_search) {
                if ($_search != '') {
                    $query->where("title", "like", '%' . $_search . "%");
                }
            });

        $_arrOfCount = $_arrOfQuery->count();
        $_arrOfData = $_arrOfQuery->orderBy('created_time', 'desc')
            ->take($_offset)->limit($_limit)->get();

        $list = [];
        foreach ($_arrOfData as $key => $value) {
            $_arrOfNewsNode = [
                "created_date" => date("Y-m-d H:i", $value["created_time"]),
                "sys_qiye_shake_id" => (string)$value["sys_qiye_shake_id"],
                "title" => $value["title"],
                "phone" => $value["phone"],
                "contacts" => $value["contacts"],
                "status" => $value["status"]
            ];
            $list[$key] = $_arrOfNewsNode;
        }

        return [
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'count' => $_arrOfCount ?? 0,
                'list' => $list
            ]
        ];
    }


    /**
     * 新增与编辑
     *
     * @param Request $request
     * @return array
     */
    public function postQiyeShakeAdd(Request $request)
    {
        $_data = $request->all();
        $allFiles = $request->allFiles();

        $_token_key = "admin_user_token:" . $request->header("token");
        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
        if (!isset($_data["title"]) || !isset($_data["phone"]) || !isset($_data["contacts"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfSysData = [
            'title' => $_data["title"],
            'phone' => $_data["phone"],
            'contacts' => $_data["contacts"],
        ];

//        已存在ID，编辑
        if (isset($_data["sys_qiye_shake_id"])) {
            $_arrOfSysData["updated_uid"] = $_admin_user["admin_user_id"];

            QiyeShake::where([
                "sys_qiye_shake_id" => $_data["sys_qiye_shake_id"]
            ])->update($_arrOfSysData);
            $msg = '编辑成功';
            $sysQiyeShakeId = $_data["sys_qiye_shake_id"];
        } else {
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfSysData["sys_qiye_shake_id"] = $_sno->nextId();
            $_arrOfSysData["created_uid"] = $_admin_user["admin_user_id"];
            $_arrOfSysData["status"] = 1;

            QiyeShake::create($_arrOfSysData);
            $msg = '创建成功';
            $sysQiyeShakeId = $_arrOfSysData["sys_qiye_shake_id"];
        }

        if ($allFiles && $sysQiyeShakeId) {
            $files = $allFiles['files'] ?? [];
            QiyeShakeUser::where('sys_qiye_shake_id', $sysQiyeShakeId)->delete();
            foreach ($files as $file) {
                Excel::import(new QiyeShakeUserImport($sysQiyeShakeId, $_admin_user["admin_user_id"]), $file);
            }
        }

        return [
            'code' => 1,
            'msg' => $msg ?? ''
        ];
    }


    /**
     * 获取详情
     *
     * @param Request $request
     * @return array
     */
    public function postQiyeShakeInfo(Request $request)
    {
        $_data = $request->all();
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';

        if (!isset($_data["sys_qiye_shake_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        $_arrOfInfo = QiyeShake::where([
            "sys_qiye_shake_id" => $_data["sys_qiye_shake_id"],
            "status" => 1,
        ])->first();

        if ($_arrOfInfo) {
            $list = [
                "created_date" => date("Y-m-d H:i", $_arrOfInfo["created_time"]),
                "sys_qiye_shake_id" => (string)$_arrOfInfo["sys_qiye_shake_id"],
                "title" => $_arrOfInfo["title"],
                "phone" => $_arrOfInfo["phone"],
                "contacts" => $_arrOfInfo["contacts"],
                "status" => $_arrOfInfo["status"]
            ];
            return [
                'code' => 1,
                'msg' => 'success',
                'data' => $list
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '未查询到内容'
            ];
        }
    }


    /**
     * 删除
     *
     * @param Request $request
     * @return array
     */
    public function postQiyeShakeDelete(Request $request)
    {
        $_data = $request->all();

        $_token_key = "admin_user_token:" . $request->header("token");
        Redis::select(1);
        $_admin_user = json_decode(Redis::get($_token_key), true);
//        语言
        $_language = isset($_data['language']) ? $_data['language'] : 'zh-CN';
        if (!isset($_data["sys_qiye_shake_id"])) {
            return SystemErrorController::paramtersError($_language);
        }

        QiyeShake::where(["sys_qiye_shake_id" => $_data["sys_qiye_shake_id"]])->update(["status" => 0, "updated_uid" => $_admin_user["admin_user_id"]]);

        return [
            'code' => 1,
            'msg' => '删除成功'
        ];
    }


}
