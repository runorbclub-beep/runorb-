<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QiyeShakeUser extends Model
{
    protected $table = 'qiye_shake_user';
    protected $primaryKey = 'qiye_shake_user_id';
    public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'qiye_shake_user_id',
        'sys_qiye_shake_id',
        'created_time',
        'updated_time',
        'created_uid',
        'updated_uid',
        'status',
        'name',
        'phone',
        'sex'
    ];

    /**
     * 企业名称表与用户表 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/11/15 16:30
     */
    public function sys_qiye_shake(): HasOne
    {
        return $this->hasOne(SysQiyeShake::class, 'sys_qiye_shake_id', 'sys_qiye_shake_id');
    }

    /**
     * 企业报名名单与用户表 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/11/15 16:30
     */
    public function usr_user(): HasOne
    {
        return $this->hasOne(UsrUser::class, 'user_id', 'user_id');
    }

    /**
     * 企业报名名单与用户运动表 一对多
     * @return HasMany
     * User: zxw
     * Date: 2021/11/16 14:02
     */
    public function user_play(): HasMany
    {
        return $this->hasMany(UserPlay::class,'user_id', 'user_id');
    }

}
