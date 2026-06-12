<?php


namespace App\Http\Controllers\Admin\Redeem;


use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Redeem\RedeemAddBrandShopRequest;
use App\Http\Requests\Admin\Redeem\RedeemEditBrandShopRequest;
use App\Models\BrandShop;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 品牌分店管理
 * Class BrandShopController
 * @package App\Http\Controllers\Admin\Redeem
 * User: zxw
 * Date: 2021/9/18 13:31
 */
class BrandShopController extends Controller
{
    /**
     * 添加品牌分店
     * @param RedeemAddBrandShopRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 13:43
     */
    public function addBrandShop(RedeemAddBrandShopRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->addBrandShop($data);
        return $this->success($list);
    }

    /**
     * 编辑品牌分店
     * @param RedeemEditBrandShopRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 15:23
     */
    public function editBrandShop(RedeemEditBrandShopRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->editBrandShop($data);
        return $this->success($list);
    }

    /**
     * 删除品牌分店
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 15:27
     */
    public function delBrandShop(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['id'])) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));
        try {
            $list = BrandShop::where('id', $data['id'])->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return $this->success($list);
    }

}
