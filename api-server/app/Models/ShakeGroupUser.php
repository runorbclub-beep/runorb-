<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * @method static where(string $string, mixed $sys_shake_id)
 */
class ShakeGroupUser extends Model
{
    protected $table = 'shake_group_user';
    protected $primaryKey = 'shake_group_user_id';
    public $incrementing = false;
//    public $timestamps = false;

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'shake_group_user_id',
        'shake_group_id',
        'sys_shake_id',
        'user_id',
        'distance',
        'integral',
        'play_data',
        'datetime',
        'index',
        'title',
        'integral_join'
    ];

    public function sysShake()
    {
        return $this->belongsTo(SysShake::class);
    }

    /**
     * 用户分组摇与用户 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/24 16:42
     */
    public function usr_user(): HasOne
    {
        return $this->hasOne(UsrUser::class,'user_id','user_id');
    }

}
