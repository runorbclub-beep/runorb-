<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\PayChannel;
use App\Models\UserPayAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * 平台支付渠道与用户支付方式账户Service
 */
class UserPayAccountService
{
    /**
     * 获取平台支付渠道列表
     * @return Builder[]|Collection
     * User: zxw
     * Date: 2022/1/24 11:13
     */
    public function getPayChannel($param)
    {
        return PayChannel::with(['user_pay_account' => function ($query) use ($param) {
            $query->select('id', 'pay_channel_id', 'account', 'actual_name');
            $query->where('user_id', $param['user_id']);
        }])
            ->where('status', 1)
            ->get();
    }

    /**
     * 根据条件获取用户支付渠道账户信息
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/24 11:17
     */
    public function getUserPayAccount($param)
    {
        $map = ['pay_channel_id' => $param['pay_channel_id']];
        isset($param['id']) ? $map['id'] = $param['id'] : '';
        isset($param['pay_channel_id']) ? $map['pay_channel_id'] = $param['pay_channel_id'] : '';
        isset($param['user_id']) ? $map['user_id'] = $param['user_id'] : '';
        isset($param['account']) ? $map['account'] = $param['account'] : '';
        isset($param['actual_name']) ? $map['actual_name'] = $param['actual_name'] : '';

        return UserPayAccount::where($map)->first();
    }

    /**
     * 新增用户支付渠道
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/24 11:44
     * @throws BusinessException
     */
    public function addUserPayAccount($param)
    {
        $account_password = isset($param['account_password']) ? Crypt::encryptString($param['account_password']) : '';
        $map = [
            'user_id' => $param['user_id'],
            'pay_channel_id' => $param['pay_channel_id'],
            'account' => $param['account'],
            'account_password' => $account_password,//TODO 需要密码操作，在这里修改 。。。
            'actual_name' => $param['actual_name'],
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString()
        ];

        try {
            $userPayAccount = UserPayAccount::create($map);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }
        return $userPayAccount;
    }

    /**
     * 编辑用户支付渠道
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/24 13:41
     * @throws BusinessException
     */
    public function editUserPayAccount($param)
    {
        $map = [];
        try {
            isset($param['account']) ? $map['account'] = $param['account'] : '';
            isset($param['account_password']) ? $map['account_password'] = Crypt::encryptString($param['account_password']) : '';
            isset($param['actual_name']) ? $map['actual_name'] = $param['actual_name'] : '';
            $map['updated_at'] = Carbon::now()->toDateTimeString();

            $userPayAccount = UserPayAccount::where([
                'id' => $param['id'],
                'user_id' => $param['user_id'],
                'pay_channel_id' => $param['pay_channel_id']
            ])->update($map);
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }
        return $userPayAccount;
    }

    /**
     * 删除用户支付渠道
     * @param $param
     * @return void
     * User: zxw
     * Date: 2022/1/24 13:44
     * @throws BusinessException
     */
    public function delUserPayAccount($param)
    {
        try {
            $userPayAccount = UserPayAccount::where([
                'id' => $param['id'],
                'user_id' => $param['user_id'],
                'pay_channel_id' => $param['pay_channel_id']
            ])->delete();
        } catch (\Throwable $ex) {
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex->getMessage());
        }
        return $userPayAccount;
    }

    /**
     * 用户获取用户支付渠道列表
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2022/1/24 13:55
     */
    public function getUserPayAccountList($param)
    {
        return UserPayAccount::where('user_id', $param['user_id'])->get();
    }
}
