<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminUserRole
 * 
 * @property string $admin_user_role_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $role_name
 * @property string|null $role_code
 * 
 * @property Collection|AdminUser[] $admin_users
 *
 * @package App\Models
 */
class AdminUserRole extends Model
{
	protected $table = 'admin_user_role';
	protected $primaryKey = 'admin_user_role_id';
	public $incrementing = false;
	
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'admin_user_role_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'role_name',
		'role_code'
	];

	public function admin_users()
	{
		return $this->hasMany(AdminUser::class);
	}
}
