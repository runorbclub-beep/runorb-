<?php


namespace App\Services;

use App\Models\UsrUser;


/**
 * 用户管理Service
 * Class UsrUserService
 * @package App\Services
 * User: zxw
 * Date: 2021/9/18 16:22
 */
class UsrUserService
{
    /**
     * 根据用户手机号查询用户信息
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/9/18 17:41
     */
    public function getUserPhone($param)
    {
        return UsrUser::where('phone', $param['phone'])->first();
    }
}
