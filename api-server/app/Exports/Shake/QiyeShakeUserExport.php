<?php

namespace App\Exports\Shake;


use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QiyeShakeUserExport implements FromCollection, WithHeadings
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }


    public function collection()
    {
        $sysQiyeShakeId = $this->data['qsid'] ?? '';

        $startDate = $this->data['start_date'] ?? date('Y-m-d');
        $endDate = $this->data['end_date'] ?? date('Y-m-d');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);

//        $startTime = '1629129600';
//        $endTime = '1629648000';

        $sql = 'SELECT FROM_UNIXTIME(shake_group_user.datetime, "%Y-%m-%d") as date, qiye_shake_user.department, qiye_shake_user.name, qiye_shake_user.phone, concat(shake_group_user.index + 1, "号", shake_group_user.title) as title, shake_group_user.distance, shake_group_user.duration, shake_group_user.integral
                FROM qiye_shake_user
                LEFT JOIN shake_group_user on shake_group_user.user_id = qiye_shake_user.user_id
                WHERE shake_group_user.datetime >= "' . $startTime . '" AND shake_group_user.datetime <= "' . $endTime . '"
                AND qiye_shake_user.sys_qiye_shake_id = ' . $sysQiyeShakeId . '
                ORDER BY shake_group_user.datetime asc, qiye_shake_user.department asc';
        $res = DB::select($sql);
        $list = $res ?? [];

        return collect($list);
    }


    //设置导出表头,如果不需要可以不设置.
    public function headings(): array
    {
        return [
            '塞事时间',
            '所属部门',
            '姓名',
            '手机号',
            '所选赛马',
            '运动距离',
            '运动时间',
            '所得积分'
        ];
    }
}
