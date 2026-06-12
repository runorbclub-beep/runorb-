<?php


namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\BrandUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * 登录Service
 * Class LoginService
 * @package App\Services
 * User: zxw
 * Date: 2021/9/22 15:14
 */
class LoginService
{
    /**
     * H5-积分兑换商户-获取品牌分店店员登录信息
     * @param $param
     * @return Builder|Model|object
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 16:54
     */
    public function getBrandUserInfo($param)
    {

        $brandUserInfo = BrandUser::with('brand','brand_shop')->where('phone',$param['phone'])->first();
        if (empty($brandUserInfo)) throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.create_branduser_error'));
        Redis::select(1);
        Redis::hdel('brand_user',$brandUserInfo->access_token);//删除原来token缓存
        //生成新token
        $brandUserInfo->access_token = md5($brandUserInfo->user_id . $brandUserInfo->user_name . time() . rand(100000, 999999));
        try {
            $brandUserInfo->save();
            //设置brandUserInfo缓存
            Redis::hset('brand_user',$brandUserInfo->access_token,json_encode($brandUserInfo,true));
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.create_branduser_error'));
        }
        return $brandUserInfo;
    }
}
