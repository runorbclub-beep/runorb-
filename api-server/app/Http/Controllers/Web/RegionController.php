<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;

class RegionController extends Controller
{

    /**
     * @author JKD
     * @time 2021/7/7 16:42
     * @abstract _获取地区
     */
    public function getRegion()
    {
        $_data = request()->input();

        $pid = $_data['pid'] ?? 1;
        $list = \App\Http\Controllers\PublicFunction\RegionController::getRegion($pid);

        return [
            "code" => 1,
            "msg" => "success",
            "data" => $list
        ];
    }
}
