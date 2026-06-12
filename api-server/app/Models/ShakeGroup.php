<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ShakeGroup extends Model
{
    protected $table = 'shake_group';
    protected $primaryKey = 'shake_group_id';
    public $incrementing = false;
//    public $timestamps = false;

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'shake_group_id',
        'sys_shake_id',
        'title',
        'start_time',
        'stop_time',
        'num',
        'distance',
        'integral',
        'status',
        'datetime',
        'index',
        'ranking'
    ];

    public function sysShake()
    {
        return $this->belongsTo(SysShake::class);
    }

}
