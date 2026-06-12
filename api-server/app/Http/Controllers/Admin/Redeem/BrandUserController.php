<?php


namespace App\Http\Controllers\Admin\Redeem;


use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Requests\Admin\Redeem\BrandGetBrandUserListRequest;
use App\Http\Requests\Admin\Redeem\RedeemEditBrandUserRequest;
use App\Http\Requests\Admin\Redeem\RedeemPostBrandUserPhoneRequest;
use App\Models\BrandUser;
use App\Services\RedeemService;
use App\Services\UsrUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 品牌分店店员管理
 * Class BrandUserController
 * @package App\Http\Controllers\Admin\Redeem
 * User: zxw
 * Date: 2021/9/16 14:25
 */
class BrandUserController extends Controller
{
    /**
     * 根据条件查询品牌分店店员列表
     * @param BrandGetBrandUserListRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * User: zxw
     * Date: 2021/9/15 19:11
     */
    public function getBrandUser(BrandGetBrandUserListRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        $list = $service->getBrandUserList($data);
        return $this->success($list);
    }

    /**
     * 根据手机号查询店员手机号是否注册全云动账号，进行关联注册
     * @param RedeemPostBrandUserPhoneRequest $request
     * @param RedeemService $service
     * @param UsrUserService $usrUserService
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 16:51
     */
    public function postBrandUserPhone(RedeemPostBrandUserPhoneRequest $request, RedeemService $service, UsrUserService $usrUserService): JsonResponse
    {
        $data = $request->all();
        $usrUser = $usrUserService->getUserPhone($data);
        if (empty($usrUser)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans("messages.create_branduser_error"));
        $list = $service->postBrandUserPhone($data, $usrUser);
        return $this->success($list);
    }

    /**
     * 编辑品牌分店店员信息
     * @param RedeemEditBrandUserRequest $request
     * @param RedeemService $service
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 17:35
     */
    public function editBrandUser(RedeemEditBrandUserRequest $request, RedeemService $service): JsonResponse
    {
        $data = $request->all();
        //图片处理
        /*if ($request->hasFile('user_img')) {
            try {
                $data['user_img'] = StaticDataController::$_server_url . '/' . $request->file('user_img')->store('public/redeem/' . date('Ymd'));
            } catch (\Throwable $ex) {
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.image_upload_failed'));
            }
        }*/
        $list = $service->editBrandUser($data);
        return $this->success($list);
    }

    /**
     * 删除品牌分店店员
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 9:45
     */
    public function delBrandUser(Request $request): JsonResponse
    {
        $data = $request->all();
        if (empty($data['id'])) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.request_parameter_error'));
        try {
            $list = BrandUser::where('id', $data['id'])->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.delete_error'));
        }
        return $this->success($list);
    }

}
