<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFocus
 * 
 * @property int $user_focus_id
 * @property string|null $user_id
 * @property string|null $usr_user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * 
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserFocus extends Model
{
	protected $table = 'user_focus';
	protected $primaryKey = 'user_focus_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'user_focus_id' => 'int',
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'user_focus_id',
		'user_id',
		'usr_user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class);
	}
}
