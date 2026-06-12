<?php

namespace App\Http\Controllers\Api\BannerUrl;

use App\Http\Controllers\Controller;
use App\Services\BannerUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerUrlController extends Controller
{
    /**
     * 获取banner图
     * @param Request $request
     * @param BannerUrlService $service
     * @return JsonResponse
     */
    public function getBannerList(Request $request, BannerUrlService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getBannerList($data);
        return $this->success($list);
    }
}
