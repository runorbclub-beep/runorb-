<?php

namespace App\Http\Controllers\Web;

use App\Exports\Shake\ShakeIntegralRankingSheetsExport;
use App\Exports\UserPlay\UserAchievementExport;
use App\Exports\UserPlay\UserPlayExport;
use App\Exports\UsrUser\UserAchievementSheetsExport;
use App\Exports\UsrUser\UsrUserExport;
use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserPlayExportController extends Controller
{
    /**
     * 下载
     * @param Request $request
     * @param ExportService $service
     * User: zxw
     * Date: 2021/11/15 17:58
     * @return BinaryFileResponse
     */
    public function userPlayExport(Request $request, ExportService $service): BinaryFileResponse
    {
        $data = $request->all();

//        $list = $service->getUserPlay($data);return $this->success($list);die();

        $string = strtoupper(base64_encode(time())) . strtoupper(base64_encode(mt_rand(100000000000, 999999999999)));
        return Excel::download(new UserPlayExport($data), $string . '.xlsx');
    }

    /**
     * 企业摇跑指数-获取员工
     * @return BinaryFileResponse
     * User: zxw
     * Date: 2021/11/24 17:02
     */
    public function userAchievementExport(): BinaryFileResponse
    {
        $string = strtoupper(base64_encode(time())) . strtoupper(base64_encode(mt_rand(100000000000, 999999999999)));
        return Excel::download(new UserAchievementExport(), $string . '.xlsx');
    }

    /**
     * 导出全国总部四项赛事排名表
     * @return BinaryFileResponse
     * User: zxw
     * Date: 2022/1/4 17:32
     */
    public function downloadRanking(): BinaryFileResponse
    {
        $string = strtoupper(base64_encode(time())) . strtoupper(base64_encode(mt_rand(100000000000, 999999999999)));
        return Excel::download(new UserAchievementSheetsExport(), $string . '.xlsx');
    }

    /**
     * 用户导出，用于有赞导入
     * @return BinaryFileResponse
     * User: zxw
     * Date: 2021/12/23 11:29
     */
    public function usrUserExport(): BinaryFileResponse
    {
        return Excel::download(new UsrUserExport(), microtime() . '.xlsx');
    }

    /**
     * 摇加油积分排名Excel表  s_time  e_time
     * @param Request $request
     * @return BinaryFileResponse
     * User: zxw
     * Date: 2022/1/18 9:25
     */
    public function downloadShakeIntegralRanking(Request $request): BinaryFileResponse
    {
        $data = $request->all();
        return Excel::download(new ShakeIntegralRankingSheetsExport($data),$data['s_time'].'--'.$data['e_time'].'.xlsx');
    }
}
