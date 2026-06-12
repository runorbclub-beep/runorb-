<?php

namespace App\Exports\UserPlay;

use App\Services\ExportService;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserPlayExport implements FromView
{
    protected $param;//接收参数
    protected $returnData;

    public function __construct($param)
    {
        $this->param = $param;
    }

    public function view(): View
    {
        $this->returnData = self::userPlayExportData($this->param);

        return view('web.user_play.user_play', [
            'user_play' => $this->returnData,
        ]);
    }

    /**
     * 导出数据处理
     * @param $param
     * @return array
     * User: zxw
     * Date: 2021/11/16 15:13
     */
    public static function userPlayExportData($param): array
    {
        $service = new ExportService;
        return $service->getUserPlay($param);
    }
}
