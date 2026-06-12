<?php

namespace App\Exports\UsrUser;

use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserAchievementExport implements FromView, WithTitle
{
    //多张的sheets名称
    private $sheetTitle;

    public function __construct($sheetTitle)
    {
        $this->sheetTitle = $sheetTitle;
    }

    /**
     * User: zxw
     * Date: 2022/1/4 16:22
     */
    public function view(): View
    {
        $exportService = new ExportService();
        return view('web.download_ranking.download_ranking', [
            'list' => $exportService->getUserAchievements($this->sheetTitle)
        ]);
    }

    /**
     * sheets名称
     * @return string
     * User: zxw
     * Date: 2022/1/4 17:16
     */
    public function title(): string
    {
        switch ($this->sheetTitle) {
            case 'max_speed':
                return '最高转速用户排名列表';
            case 'onemin':
                return '摇跑一分钟用户排名列表';
            case 'exponent':
                return '摇跑指数用户排名列表';
            case 'marathon':
                return '摇跑马拉松用户排名列表';
        }
        return $this->sheetTitle;
    }


}
