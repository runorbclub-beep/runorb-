<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserPlayProgress
 * 
 * @property string $user_play_progress_id
 * @property string|null $user_id
 * @property string|null $user_play_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * 
 * @property UserPlay|null $user_play
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserPlayProgress extends Model
{
	protected $table = 'user_play_progress';
	protected $primaryKey = 'user_play_progress_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'user_play_progress_id',
		'user_id',
		'user_play_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status'
	];

	public function user_play()
	{
		return $this->belongsTo(UserPlay::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
