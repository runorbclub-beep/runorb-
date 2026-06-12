<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static create($param)
 * @method static where(string $string, mixed $id)
 * @method static select(array|string[] $select)
 */
class UserClan extends Model
{
    use HasFactory;

    protected $table = 'user_clans';

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'clan_avatar', 'address', 'introduction', 'avg_speed_max', 'avg_exponent_molecular', 'avg_runball_exponent', 'avg_marathon', 'status', 'telephone', 'photo_text', 'title_time', 'updated_at'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'avg_speed_max',
        'avg_exponent_molecular',
        'avg_runball_exponent',
        'avg_marathon',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'title_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 战队与战队成员 一对多
     * @return HasMany
     * User: zxw
     * Date: 2021/12/06 17:17
     */
    public function user_clan_members(): HasMany
    {
        return $this->hasMany(UserClanMember::class,'user_clan_id','id');
    }


}
