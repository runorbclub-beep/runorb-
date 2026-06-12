<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserGroupAssociated
 *
 * @property string $user_group_associated_id
 * @property string|null $user_id
 * @property string|null $user_group_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $join_description
 * @property string|null $audit_description
 * @property int|null $is_creater
 *
 * @property UserGroup|null $user_group
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserGroupAssociated extends Model
{
	protected $table = 'user_group_associated';
	protected $primaryKey = 'user_group_associated_id';
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
		'status' => 'int',
		'is_creater' => 'int'
	];

	protected $fillable = [
		'user_group_associated_id',
		'user_id',
		'user_group_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'join_description',
		'audit_description',
		'is_creater'
	];

	public function user_group()
	{
		return $this->belongsTo(UserGroup::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
