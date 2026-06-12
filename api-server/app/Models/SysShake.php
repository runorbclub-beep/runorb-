<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SysShake extends Model
{
	const CREATED_AT = 'created_time';
	const UPDATED_AT = 'updated_time';
	protected $table = 'sys_shake';
	protected $primaryKey = 'sys_shake_id';
	public $incrementing = false;
//	public $timestamps = false;

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

	protected $fillable = [
		'sys_shake_id',
        'title',
        'datetime',
        'start_time',
        'stop_time',
		'each_integral',
		'limit_num',
		'status',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid'
	];


    public function shakeGroup()
    {
        return $this->hasMany(ShakeGroup::class, 'sys_shake_id', 'sys_shake_id');
    }

}
