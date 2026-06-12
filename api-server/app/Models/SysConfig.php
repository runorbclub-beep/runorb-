<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysConfig
 *
 * @property string $sys_config_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $web_site
 *
 * @package App\Models
 */
class SysConfig extends Model
{
	protected $table = 'sys_config';
	protected $primaryKey = 'sys_config_id';
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
		'sys_config_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'phone',
		'email',
		'web_site'
	];
}
