<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 用户积分获得记录
 * Class UserIntegralLog
 * @package App\Models
 * User: zxw
 * Date: 2021/9/23 16:02
 */
class UserIntegralLog extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['source_type', 'user_id', 'integral', 'remark', 'created_at'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 用户积分获得记录用户ID与用户ID 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/22 14:34
     */
    public function usr_user(): HasOne
    {
        return $this->hasOne(UsrUser::class, 'user_id', 'user_id');
    }
}
