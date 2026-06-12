<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserGroup
 * 
 * @property string $user_group_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $group_title
 * @property string|null $group_logo
 * @property int|null $is_official
 * @property string|null $official_description
 * @property string|null $audit_certificate
 * @property string|null $audit_description
 * @property string|null $group_num
 * @property int|null $group_user_num
 * 
 * @property Collection|MatchsUser[] $matchs_users
 * @property Collection|UserGroupAssociated[] $user_group_associateds
 *
 * @package App\Models
 */
class UserGroup extends Model
{
	protected $table = 'user_group';
	protected $primaryKey = 'user_group_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'is_official' => 'int',
		'group_user_num' => 'int'
	];

	protected $fillable = [
		'user_group_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'group_title',
		'group_logo',
		'is_official',
		'official_description',
		'audit_certificate',
		'audit_description',
		'group_num',
		'group_user_num'
	];

	public function matchs_users()
	{
		return $this->hasMany(MatchsUser::class);
	}

	public function user_group_associateds()
	{
		return $this->hasMany(UserGroupAssociated::class);
	}
}
