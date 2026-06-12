<?php

namespace App\Exports\Shake;

use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ShakeIntegralRankingExport implements FromView, WithTitle
{
    protected $timeTitle;

    public function __construct($timeTitle)
    {
        $this->timeTitle = $timeTitle;
    }

    public function view(): View
    {
        $exportService = new ExportService();
        return view('web.shake_integral_ranking.shake_integral_ranking',[
            'list' => $exportService->getShakeIntegralRankingExport($this->timeTitle)
        ]);
    }

    /**
     * sheets名称
     * @return string
     * User: zxw
     * Date: 2022/1/18 9:40
     */
    public function title(): string
    {
        return date('Y-m-d',strtotime($this->timeTitle))." 摇加油数据详情表";
    }
}
