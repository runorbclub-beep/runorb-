<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MatchsAward extends Model
{
    use HasFactory;
    protected $table = 'matchs_awards';

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'sys_match_id', 'title', 'title_en', 'award_img', 'back_img', 'created_at', 'updated_at'];

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
     * @return HasOne
     */
    public function sys_matchs(): HasOne
    {
        return $this->hasOne(SysMatch::class,'sys_match_id','sys_match_id');
    }

    public function usr_user()
    {
        return $this->hasOne(UsrUser::class,'user_id','user_id');
    }
}
