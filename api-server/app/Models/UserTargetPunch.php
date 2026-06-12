<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static where(string $string, mixed $id)
 * @method static select(mixed|string $select)
 * @method static create($param)
 * @method static insert($param)
 * @method static whereIn(string $string, mixed $month_time)
 * @method static whereNotIn(string $string, int[] $array)
 * @method static withCount()
 * @method static withSum(string $string, \Closure $param)
 */
class UserTargetPunch extends Model
{
    use HasFactory;

    protected $table = 'user_target_punchs';
    protected $primaryKey = 'id';

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'source', 'month_time', 'target_distance', 'min_days', 'fulfil_days', 'status', 'created_at', 'updated_at'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 用户打卡目标表与用户运动表 一对多
     * @return HasMany
     * User: zxw
     * Date: 2021/11/25 17:13
     */
    public function user_play(): HasMany
    {
        return $this->hasMany(UserPlay::class,'user_id','user_id');
    }
}
