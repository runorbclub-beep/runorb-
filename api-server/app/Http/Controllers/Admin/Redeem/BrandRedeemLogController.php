<?php


namespace App\Http\Controllers\Admin\Redeem;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Redeem\BrandGetBrandRedeemLogRequest;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;

/**
 * 积分兑换-账单流水
 * Class BrandRedeemLogController
 * @package App\Http\Controllers\Admin\Redeem
 * User: zxw
 * Date: 2021/9/16 14:23
 */
class BrandRedeemLogController extends Controller
{
    /**
     * 根据条件查询品牌分店账单列表 type: 1进账单 2出账单
     * @param BrandGetBrandRedeemLogRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/15 18:59
     */
    public function getBrandRedeemLog(BrandGetBrandRedeemLogRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getBrandRedeemLog($data);
        return $this->success($list);
    }


}
