<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create($param)
 * @method static where(string $string, int $int)
 */
class PostPlayLog extends Model
{
    use HasFactory;

    protected $table = 'post_play_logs';
    protected $primaryKey = 'post_play_id';

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_play_id', 'source', 'user_id', 'json_data', 'is_quartets', 'status', 'created_at', 'updated_at'];

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
}
