<?php

namespace App\Exports\Shake;

use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ShakeIntegralRankingSheetsExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];
        //时间段按天组装数组
        $period = CarbonPeriod::create($this->data['s_time'],$this->data['e_time'])->toArray();
        foreach ($period as $k => $v) {
            $sheets[] = new ShakeIntegralRankingExport($v);
        }
        return $sheets;
    }
}
