<?php

namespace App\Exports;

use App\Model\Salary;
use App\Model\SysUser;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class ChannelStorePerformanceExport implements FromArray,WithHeadings
{


    private $data;

    private $format;

    public function __construct (array $data,array $format)
    {
        $this->data = $data;
        $this->format = $format;
    }

    public function array() : array
    {
        return $this->data;
    }
    // 列名
    public function headings() : array
    {
        return $this->format;
    }

}
