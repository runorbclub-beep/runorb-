<?php


namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\Brand;
use App\Models\BrandRedeemLog;
use App\Models\BrandShop;
use App\Models\BrandUser;
use App\Models\PayChannel;
use App\Models\UserIntegralLog;
use App\Models\UsrUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * 积分兑换Service
 * Class RedeemService
 * @package App\Services
 * User: zxw
 * Date: 2021/9/15 14:03
 */
class RedeemService
{
    /**
     * 获取品牌列表
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/15 16:51
     */
    public function getBrandList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;

        return Brand::withCount('BrandShop as brand_shop_count')
            ->withCount(['BrandShop as shop_integral_sum' => function ($query) {
                $query->select(DB::raw("IFNULL(SUM(shop_integral),0)"));
            }])
            ->groupBy('brands.id')
            ->orderBy('brands.created_at', 'desc')
            ->paginate($param['limit']);
    }

    /**
     * 新增品牌
     * @param $param
     * @return mixed
     * @throws BusinessException User: zxw
     * Date: 2021/9/15 16:55
     */
    public function addBrand($param)
    {
        try {
            $param['created_time'] = Carbon::now()->toDateTimeString();
            $data = Brand::create($param);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $data;
    }

    /**
     * 编辑品牌信息
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 9:25
     */
    public function editBrand($param)
    {
        //根据ID获取品牌详情
        $brand = self::getBrandDetail($param);
        try {
            isset($param['logo']) ? $brand->logo = $param['logo'] : '';
            isset($param['brand_name']) ? $brand->brand_name = $param['brand_name'] : '';
            isset($param['contact_person']) ? $brand->contact_person = $param['contact_person'] : '';
            isset($param['phone']) ? $brand->phone = $param['phone'] : '';
            $brand->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $brand;
    }

    /**
     * 根据ID获取品牌详情
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/15 18:25
     */
    public function getBrandDetail($param)
    {
        return Brand::withCount('BrandShop as brand_shop_count')
            ->withCount(['BrandShop as shop_integral_sum' => function ($query) {
                $query->select(DB::raw("IFNULL(SUM(shop_integral),0)"));
            }])
            ->where('id', $param['id'])
            ->first();
    }

    /**
     * 根据品牌ID获取品牌分店列表
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/15 18:41
     */
    public function getBrandShopList($param)
    {
        return BrandShop::where('brand_id', $param['id'])->get();
    }

    /**
     * 根据分店ID获取分店详情
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/18 15:13
     */
    public function getBrandShop($param)
    {
        $map = [];
        $map[] = ['id', '=', $param['id']];
        isset($param['brand_id']) ? $map[] = ['brand_id', '=', $param['brand_id']] : '';
        return BrandShop::where($map)->first();
    }

    /**
     * 根据用户ID获取品牌分店信息
     * @param $param
     * @return Builder|Model|object
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/22 16:54
     */
    public function getBrandUserInfo($param)
    {
        $brandUserInfo = BrandUser::with('brand','brand_shop')->where('id',$param['id'])->first();
        if (empty($brandUserInfo)) throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.create_branduser_error'));
        Redis::select(1);
        Redis::hdel('brand_user',$brandUserInfo->access_token);//删除原来token缓存
        try {
            $brandUserInfo->save();
            //设置brandUserInfo缓存
            Redis::hset('brand_user',$brandUserInfo->access_token,json_encode($brandUserInfo,true));
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.create_branduser_error'));
        }
        return $brandUserInfo;
    }

    /**
     * 新增品牌分店
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 13:42
     */
    public function addBrandShop($param)
    {
        try {
            $param['created_time'] = Carbon::now()->toDateTimeString();
            $brandShop = BrandShop::create($param);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $brandShop;
    }

    /**
     * 编辑品牌分店信息
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 15:16
     */
    public function editBrandShop($param)
    {
        $brandShop = self::getBrandShop($param);
        try {
            isset($param['shop_name']) ? $brandShop->shop_name = $param['shop_name'] : '';
            isset($param['shop_address']) ? $brandShop->shop_address = $param['shop_address'] : '';
            $brandShop->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $brandShop;
    }

    /**
     * 获取用户积分获得列表
     * @param $param
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/9/23 16:07
     */
    public function getUserIntegralLogList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;

        $userIntegralLog = UserIntegralLog::with([
                'usr_user' => function($query){
                    $query->select('user_id','sys_sex_id','user_name','user_img');
                }
            ])
            ->where('user_id',$param['user_id'])
            ->orderBy('id', 'desc')
            ->paginate($param['limit'])
            ->toArray();
        foreach ($userIntegralLog['data'] as $k => $v) {
            $userIntegralLog['data'][$k] = [
                'id' => $v['id'],
                'source_type' => $v['source_type'],
                'user_id' => $v['user_id'],
                'integral' => $v['integral'],
                'remark' => $v['remark'],
                'created_at' => $v['created_at'],
                'sys_sex_id' => $v['usr_user']['sys_sex_id'] ?? null,
                'user_name' => $v['usr_user']['user_name'] ?? null,
                'user_img' => $v['usr_user']['user_img'] ?? null,
            ];
        }
        return $userIntegralLog;
    }

    /**
     * 获取用户积分获得详情
     * @param $param
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/9/23 16:08
     */
    public function getUserIntegralLogDetail($param)
    {
        $userIntegralLog = UserIntegralLog::with([
                'usr_user' => function($query){
                    $query->select('user_id','sys_sex_id','user_name','user_img');
                }
            ])
            ->where('id',$param['id'])
            ->first();
        $userIntegralLog['sys_sex_id'] = $userIntegralLog->usr_user->sys_sex_id ?? null;
        $userIntegralLog['user_name'] = $userIntegralLog->usr_user->user_name ?? null;
        $userIntegralLog['user_img'] = $userIntegralLog->usr_user->user_img ?? null;
        unset($userIntegralLog['usr_user']);
        return $userIntegralLog;
    }

    /**
     * 根据条件查询品牌分店账单列表-不分页 type: 1进账单 2出账单
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/15 18:55
     */
    public function getBrandRedeemLog($param)
    {
        switch ($param['type']) {
            case 1://进账单
                $data = BrandRedeemLog::where([
                    ['type', '=', 1],
                    ['brand_id', '=', $param['brand_id']],
                    ['brand_shop_id', '=', $param['id']]
                ])->get();
                break;
            case 2://出账单
                $data = BrandRedeemLog::where([
                    ['type', '=', 2],
                    ['brand_id', '=', $param['brand_id']],
                    ['brand_shop_id', '=', $param['id']]
                ])->get();
                break;
            default://非法请求
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }
        return $data;
    }

    /**
     * 根据条件查询品牌分店账单列表-分页 type: 1进账单 2出账单
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/15 18:55
     */
    public function getBrandRedeemLogList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;
        switch ($param['type']) {
            case 1://进账单
                $type = 1;
                break;
            case 2://出账单
                $type = 2;
                break;
            default://非法请求
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }
        $map = [];
        $map[] = ['type', '=', $type];
        isset($param['user_id']) ? $map[] = ['user_id', '=', $param['user_id']] : '';
        isset($param['brand_id']) ? $map[] = ['brand_id', '=', $param['brand_id']] : '';
        isset($param['brand_shop_id']) ? $map[] = ['brand_shop_id', '=', $param['brand_shop_id']] : '';

        $brandRedeemLog = BrandRedeemLog::with(['usr_user'=>function($query){
                $query->select('user_id','sys_sex_id','user_name','user_img');
            }])
            ->where($map)
            ->where('integral','>',0)
            ->orderBy('id', 'desc')
            ->paginate($param['limit'])
            ->toArray();
        foreach ($brandRedeemLog['data'] as $k => $v) {
            $brandRedeemLog['data'][$k] = [
                'id' => $v['id'],
                'order_no' => $v['order_no'],
                'type' => $v['type'],
                'integral_bill_type' => $v['integral_bill_type'],
                'integral' => $v['integral'],
                'integral_balance' => $v['integral_balance'],
                'project_name' => $v['project_name'],
                'remark' => $v['remark'],
                'created_at' => $v['created_at'],
                'user_id' => $v['usr_user']['user_id'] ?? null,
                'sys_sex_id' => $v['usr_user']['sys_sex_id'] ?? null,
                'user_name' => $v['usr_user']['user_name'] ?? null,
                'user_img' => $v['usr_user']['user_img'] ?? null,
            ];
            if (!is_null($brandRedeemLog['data'][$k]['user_img'])) $brandRedeemLog['data'][$k]['user_img'] = StaticDataController::$_server_url . "/" . $brandRedeemLog['data'][$k]['user_img'];
        }
        return $brandRedeemLog;
    }

    /**
     * 根据ID获取账单详情
     * @param $param
     * @return Builder|Model|object|null
     * User: zxw
     * Date: 2021/9/22 14:23
     */
    public function getBrandRedeemLogDetail($param)
    {
        $brandRedeemLog = BrandRedeemLog::with([
                'usr_user' => function($query){
                    $query->select('user_id','sys_sex_id','user_name','user_img');
                },
                'brand_shop' => function($query){
                    $query->select('id','shop_name','shop_address');
                },
                'brand_users' => function($query){
                    $query->select('id','real_name','nickname','user_img');
                }
            ])
            ->where('id',$param['id'])
            ->first();
        $brandRedeemLog['sys_sex_id'] = $brandRedeemLog->usr_user->sys_sex_id ?? null;
        $brandRedeemLog['user_name'] = $brandRedeemLog->usr_user->user_name ?? null;
        $brandRedeemLog['user_img'] = $brandRedeemLog->usr_user->user_img ? StaticDataController::$_server_url . "/" . $brandRedeemLog->usr_user->user_img : null;
        $brandRedeemLog['shop_name'] = $brandRedeemLog->brand_shop->shop_name ?? null;
        $brandRedeemLog['shop_address'] = $brandRedeemLog->brand_shop->shop_address ?? null;
        $brandRedeemLog['shop_real_name'] = $brandRedeemLog->brand_users->real_name ?? null;
        $brandRedeemLog['shop_nickname'] = $brandRedeemLog->brand_users->nickname ?? null;
        $brandRedeemLog['shop_user_img'] = isset($brandRedeemLog->brand_users->user_img)&&!empty($brandRedeemLog->brand_users->user_img) ? StaticDataController::$_server_url . "/" . $brandRedeemLog->brand_users->user_img : null;
        unset($brandRedeemLog['usr_user'],$brandRedeemLog['brand_shop'],$brandRedeemLog['brand_users']);
        return $brandRedeemLog;
    }

    /**
     * 获取用户头像和昵称
     * @param $userId
     * @return mixed
     * User: zxw
     * Date: 2021/9/24 10:37
     */
    public function getUsrUserInfo($userId)
    {
        return UsrUser::select('user_img','user_name')->where('user_id',$userId)->first();
    }

    /**
     * 写入积分兑换记录
     * @param $param
     * @return Builder|Model|object|null
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/23 19:00
     */
    public function postBrandRedeemLog($param)
    {
        $map = [
            'order_no' => $param['order_no'],
            'type' => $param['type'],
            'brand_id' => $param['brand_id'],
            'brand_shop_id' => $param['brand_shop_id'],
            'brand_user_id' => $param['brand_user_id'],
            'operate_user_id' => $param['operate_user_id'],
            'user_id' => $param['user_id'],
            'integral' => $param['integral'],
            'project_name' => $param['project_name'],
            'remark' => $param['remark'] ?? null,
            'created_at' => Carbon::now()->toDateTimeString()
        ];

        try {
            DB::transaction(function () use ($map,$param){
                //写入积分兑换记录
                BrandRedeemLog::create($map);
                $usrUser = UsrUser::where('user_id',$param['user_id'])->where('integral','>=',$param['integral'])->first();
                if (empty($usrUser)) throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.insufficient_user_points_error'));
                //更新用户积分（扣除）
                UsrUser::where('user_id',$param['user_id'])->where('integral','>=',$param['integral'])->decrement('integral',$param['integral']);
                //更新品牌分店积分（增加）
                BrandShop::where('id',$param['brand_shop_id'])->increment('shop_integral',$param['integral']);
            },5);

        }catch (\Throwable $ex){
            if ($ex->getMessage() == trans('messages.insufficient_user_points_error')){
                throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
            }
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.transaction_failed_error'));
        }
        $brandUserInfo = BrandUser::with('brand','brand_shop')->where('id',$param['brand_user_id'])->first();
        Redis::select(1);
        //更新设置brandUserInfo缓存
        Redis::hset('brand_user',$brandUserInfo->access_token,json_encode($brandUserInfo,true));
        $usrUser = self::getUsrUserInfo($param['user_id']);
        $usrUser['integral'] = $param['integral'];
        return $usrUser;
    }

    /**
     * 根据条件查询品牌分店店员列表
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/15 19:10
     */
    public function getBrandUserList($param)
    {
        return BrandUser::where([
            ['brand_id', '=', $param['brand_id']],
            ['brand_shop_id', '=', $param['id']]
        ])->get();
    }

    /**
     * 根据条件查询品牌分店店员信息
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/18 17:26
     */
    public function getBrandUser($param)
    {
        return BrandUser::where([
            ['id', '=', $param['id']],
            ['brand_id', '=', $param['brand_id']],
            ['brand_shop_id', '=', $param['brand_shop_id']]
        ])
            ->first();
    }

    /**
     * 根据手机号查询店员手机号是否注册全云动账号，进行关联注册
     * @param $param
     * @param $usrUser
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 16:50
     */
    public function postBrandUserPhone($param, $usrUser)
    {
        try {
            $param['user_id'] = $usrUser->user_id;
            $param['real_name'] = $usrUser->real_name;
            $param['nickname'] = $usrUser->user_name;
            $param['phone'] = $usrUser->phone;
            $param['email'] = $usrUser->email;
            $param['user_img'] = $usrUser->user_img;
            $brandUser = BrandUser::create($param);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $brandUser;
    }

    /**
     * 编辑品牌分店店员信息
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 17:30
     */
    public function editBrandUser($param)
    {
        $brandUser = self::getBrandUser($param);
        try {
            isset($param['real_name']) ? $brandUser->real_name = $param['real_name'] : '';
            isset($param['nickname']) ? $brandUser->nickname = $param['nickname'] : '';
            isset($param['phone']) ? $brandUser->phone = $param['phone'] : '';
            isset($param['email']) ? $brandUser->email = $param['email'] : '';
            isset($param['user_img']) ? $brandUser->user_img = $param['user_img'] : '';
            $brandUser->save();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.edit_error'));
        }
        return $brandUser;
    }

    /**
     * 支付提现写入提现日志
     * @param $param
     * @param $integral_balance
     * @param int $type 账单类型 1入账单 2出账单
     * @return mixed
     * User: zxw
     * Date: 2022/1/21 13:57
     */
    public function pointsWithdrawalLog($param, $integral_balance, int $type)
    {
        if (!empty($param['pay_channel_id'])){
            $payChannel = PayChannel::where('id',$param['pay_channel_id'])->first();
        }

        if (empty($payChannel['pay_name'])){
            if (!empty($param['pay_name'])){
                $payChannel['pay_name'] = $param['pay_name'];
            }
        }

        //写入积分兑换记录
        return BrandRedeemLog::create([
            'order_no' => $param['order_no'],
            'type' => $type,
            'integral_bill_type' => $param['integral_bill_type'] ?? 4,
            'user_id' => $param['user_id'],
            'integral' => $param['integral'],
            'integral_balance' => $integral_balance,
            'project_name' => $payChannel['pay_name'] ?? '未知',
            'remark' => $param['remark'],
            'created_at' => $param['create_time'] ?? Carbon::now()->toDateTimeString(),
            'updated_at' => $param['create_time'] ?? Carbon::now()->toDateTimeString()
        ]);
    }

}
