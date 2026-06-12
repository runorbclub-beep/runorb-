<?php

namespace App\Exports;

use App\Model\Salary;
use App\Model\SysUser;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class AdminTaskExport implements FromArray,WithHeadings,WithEvents
{


    private $data;

    public function __construct (array $data)
    {
        $this->data = $data;
    }

    public function array() : array
    {
        return $this->data;
    }
    // 列名
    public function headings() : array
    {
        return [
            '门店编号',
            '门店名',
            '区域经理',
            '完成进度',
            '是否完成'
        ];
    }

    public function registerEvents() : array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(10);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
            }
        ];
    }
}
