<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @method static create($param)
 * @method static where(array $array)
 * @method static select(array|string[] $select)
 */
class UserClanMember extends Model
{
    use HasFactory;

    protected $table = 'user_clan_members';

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_clan_id', 'user_id', 'is_captain', 'remark', 'reply_remark', 'status', 'avg_speed_max', 'avg_exponent_molecular', 'avg_runball_exponent', 'avg_marathon', 'avg_exponent_speed_max', 'updated_at'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'deleted_at'
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
     * 战队成员表与用户表  一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/12/07 18:13
     */
    public function usr_user(): HasOne
    {
        return $this->hasOne(UsrUser::class, 'user_id', 'user_id');
    }

    /**
     * 战队成员表与用户成就表  一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/12/08 15:17
     */
    public function user_achievement(): HasOne
    {
        return $this->hasOne(UserAchievement::class, 'user_id', 'user_id');
    }

    /**
     * 战队成员与战队表 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/12/08 18:20
     */
    public function user_clan(): HasOne
    {
        return $this->hasOne(UserClan::class, 'id', 'user_clan_id');
    }

}
