<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminUser
 *
 * @property string $admin_user_id
 * @property string|null $admin_user_role_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $user_name
 * @property string|null $email
 * @property string|null $pk_room_pwd
 * @property string|null $user_img
 * @property string|null $access_token
 * @property int|null $exp_time
 * @property string|null $nick_name
 * @property string|null $password
 *
 * @property AdminUserRole|null $admin_user_role
 *
 * @package App\Models
 */
class AdminUser extends Model
{
	protected $table = 'admin_user';
	protected $primaryKey = 'admin_user_id';
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
		'exp_time' => 'int'
	];

	protected $hidden = [
		'access_token',
		'password'
	];

	protected $fillable = [
		'admin_user_id',
		'admin_user_role_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'user_name',
		'email',
		'pk_room_pwd',
		'user_img',
		'access_token',
		'exp_time',
		'nick_name',
		'password'
	];

	public function admin_user_role()
	{
		return $this->belongsTo(AdminUserRole::class);
	}
}
