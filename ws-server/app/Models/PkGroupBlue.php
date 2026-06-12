<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PkGroupBlue
 * 
 * @property string $pk_group_blue_id
 * @property string|null $pk_room_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $circle_count
 * 
 * @property PkRoom|null $pk_room
 * @property UsrUser|null $usr_user
 * @property Collection|UserPlay[] $user_plays
 *
 * @package App\Models
 */
class PkGroupBlue extends Model
{
	const CREATED_AT = 'created_time';
	const UPDATED_AT = 'updated_time';
	protected $table = 'pk_group_blue';
	protected $primaryKey = 'pk_group_blue_id';
	public $incrementing = false;
	protected $dateFormat = 'U';
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'circle_count' => 'int'
	];

	protected $fillable = [
		'pk_group_blue_id',
		'pk_room_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'circle_count'
	];

	public function pk_room()
	{
		return $this->belongsTo(PkRoom::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function user_plays()
	{
		return $this->hasMany(UserPlay::class);
	}
}
