<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserLevelAssociated
 *
 * @property string $user_level_associated
 * @property string|null $sys_level_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $year
 *
 * @property UsrUser|null $usr_user
 * @property SysLevel|null $sys_level
 *
 * @package App\Models
 */
class UserLevelAssociated extends Model
{
	protected $table = 'user_level_associated';
	protected $primaryKey = 'user_level_associated';
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
		'user_level_associated',
		'sys_level_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'year'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function sys_level()
	{
		return $this->belongsTo(SysLevel::class);
	}
}
