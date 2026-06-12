<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QiyeShake extends Model
{
	protected $table = 'sys_qiye_shake';
	protected $primaryKey = 'sys_qiye_shake_id';
	public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $casts = [
        'created_time' => 'int',
        'updated_time' => 'int',
        'status' => 'int'
    ];

	protected $fillable = [
		'sys_qiye_shake_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'title',
		'phone',
		'contacts'
	];
}
