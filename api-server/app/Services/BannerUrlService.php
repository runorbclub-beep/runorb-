<?php

namespace App\Services;

use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\BannerUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BannerUrlService
{
    /**
     * 获取三张banner
     * @param $param
     * @return mixed
     */
    public function getBannerList(&$param)
    {
       /* if (Redis::exists('getBannerList')){
            $bannerUrl = Redis::get('getBannerList');
            $bannerUrl = unserialize($bannerUrl);
        }else{
            $param['limit'] = $param['limit'] ?? 3;
            $bannerUrl = BannerUrl::select(DB::raw("CONCAT('".StaticDataController::$_server_url . "/',img_url) as img_url"),'jump_link','banner_type')->where('status',1)->orderBy('sort','desc')->limit($param['limit'])->get();
            if (!$bannerUrl){
                Redis::setex('getBannerList',300,serialize($bannerUrl));
            }
        }*/
        $param['limit'] = $param['limit'] ?? 5;
        return BannerUrl::select(DB::raw("CONCAT('".StaticDataController::$_server_url . "/',img_url) as img_url"),'jump_link','banner_type')->where('status',1)->orderBy('sort','desc')->limit($param['limit'])->get();
    }
}
