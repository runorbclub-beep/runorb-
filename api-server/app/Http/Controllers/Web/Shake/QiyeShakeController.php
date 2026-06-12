<?php

namespace App\Http\Controllers\Web\Shake;


use App\Exports\Shake\Export;
use App\Exports\Shake\QiyeShakeUserExport;
use App\Http\Controllers\Controller;
use App\Models\QiyeShakeUser;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Support\Facades\DB;

class QiyeShakeController extends Controller
{

    /**
     * 导出要加油用户
     *
     * @param Request $request
     * @return array|void
     */
    public function exportShakeUser(Request $request)
    {
        $_data = $request->all();
        $sysQiyeShakeId = $_data['qsid'] ?? '';
        if (!$sysQiyeShakeId) {
            return [
                'code' => 0,
                'msg' => '参数有误'
            ];
        }

        $startDate = $_data['start_date'] ?? date('Y-m-d');
        $endDate = $_data['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

        $sql = 'SELECT FROM_UNIXTIME(shake_group_user.datetime, "%Y-%m-%d") as date, qiye_shake_user.department, qiye_shake_user.name, qiye_shake_user.sex, qiye_shake_user.phone, concat(shake_group_user.index + 1, "号", shake_group_user.title) as title, shake_group_user.distance, shake_group_user.duration, shake_group_user.integral
                FROM qiye_shake_user
                LEFT JOIN shake_group_user on shake_group_user.user_id = qiye_shake_user.user_id
                WHERE shake_group_user.datetime >= "' . $startTime . '" AND shake_group_user.datetime <= "' . $endTime . '"
                AND qiye_shake_user.sys_qiye_shake_id = ' . $sysQiyeShakeId . '
                ORDER BY shake_group_user.datetime asc, qiye_shake_user.department asc';
        $res = DB::select($sql);
        $data = $res ? (array)$res : [];
        //按日期切分
        $userList = $this->array_group($data, 'date');

        //所有员工
        $allList = QiyeShakeUser::where('sys_qiye_shake_id', $sysQiyeShakeId)->get(['department', 'name', 'phone']);
        $allList = $allList ? $allList->toArray() : [];
        $allNameList = array_column($allList, 'name');
        $allList = array_column($allList, null, 'name');

        $newList = [];
        foreach ($userList as $date => $uList) {
            $unUserList = array_column($uList, 'name');
            $notInList = array_diff($allNameList, $unUserList);
            $notInList = $notInList ?? [];
            foreach ($notInList as $notIn) {
                $uList[] = [
                    'date' => $date,
                    'department' => $allList[$notIn]['department'] ?? '',
                    'name' => $allList[$notIn]['name'] ?? '',
                    'sex' => $allList[$notIn]['sex'] ?? '',
                    'phone' => $allList[$notIn]['phone'] ?? '',
                    'title' => '',
                    'distance' => 0,
                    'duration' => 0,
                    'integral' => 0,
                ];
            }
            $newList = array_merge($newList, $uList);
        }

        $headerList = [
            '赛事时间', '所属部门', '姓名', '性别', '手机号', '所选赛马', '运动距离', '运动时间', '所得积分'
        ];

        $export = new Export();
        return $export->exportToCsv($headerList, $newList, '员工摇加油');
    }


    public function array_group($data, $key)
    {
        $list = [];
        foreach ($data as $da) {
            $list[$da->$key][] = $da;
        }
        return $list;
    }


}
