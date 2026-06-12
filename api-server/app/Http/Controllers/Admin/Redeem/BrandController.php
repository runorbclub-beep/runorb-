<?php


namespace App\Http\Controllers\Admin\Redeem;


use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Requests\Admin\Redeem\BrandAddRequest;
use App\Http\Requests\Admin\Redeem\BrandGetBrandUserRequest;
use App\Http\Requests\Admin\Redeem\BrandListRequest;
use App\Http\Requests\Admin\Redeem\RedeemEditRequest;
use App\Models\Brand;
use App\Services\RedeemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 后台管理-积分品牌管理
 * Class BrandController
 * @package App\Http\Controllers\Admin\Redeem
 * User: zxw
 * Date: 2021/9/16 14:44
 */
class BrandController extends Controller
{
    /**
     * 获取积分品牌列表
     * @param BrandListRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 12:55
     */
    public function list(BrandListRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getBrandList($data);
        return $this->success(data_list_format($list));
    }

    /**
     * 新增品牌
     * @param BrandAddRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/15 16:56
     */
    public function add(BrandAddRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        //图片处理
        /*if ($request->hasFile('logo')) {
            try {
                $data['logo'] = StaticDataController::$_server_url . '/' . $request->file('logo')->store('public/redeem/' . date('Ymd'));
            } catch (\Throwable $ex) {
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.image_upload_failed'));
            }
        }*/
        $list = $service->addBrand($data);
        return $this->success($list);
    }

    /**
     * 编辑品牌信息
     * @param RedeemEditRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 9:27
     */
    public function edit(RedeemEditRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        //图片处理
        /*if ($request->hasFile('logo')) {
            try {
                $data['logo'] = StaticDataController::$_server_url . '/' . $request->file('logo')->store('public/redeem/' . date('Ymd'));
            } catch (\Throwable $ex) {
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.image_upload_failed'));
            }
        }*/
        $list = $service->editBrand($data);
        return $this->success($list);
    }

    /**
     * 根据品牌ID获取品牌详情
     * @param Request $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/15 18:35
     */
    public function getBrandDetail(Request $request, RedeemService $service): JsonResponse
    {
        $list = [];
        $data = $request->all();
        if (empty($data['id'])) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));

        $list['brand_detail'] = $service->getBrandDetail($data);//品牌详情
        $list['brand_shop_list'] = $service->getBrandShopList($data);//品牌分店列表
        return $this->success($list);
    }

    /**
     * 删除品牌
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 9:59
     */
    public function del(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['id'])) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));
        try {
            $list = Brand::where('id', $data['id'])->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return $this->success($list);
    }

}
