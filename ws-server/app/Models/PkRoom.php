<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PkRoom
 * 
 * @property string $pk_room_id
 * @property string|null $user_id
 * @property string|null $matchs_event_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $pk_room_title
 * @property string|null $pk_room_pwd
 * @property int|null $pk_type
 * @property string|null $pk_max_person
 * @property int|null $time_long
 * @property int|null $group_red_circle_count
 * @property int|null $group_blue_circle_count
 * @property string|null $group_win
 * @property float|null $distance_value
 * @property string|null $pk_result_type
 * @property int|null $pk_type_id
 * @property string|null $pk_room_code
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property string|null $pk_room_number
 * @property string|null $group_red_title
 * @property string|null $group_blue_title
 * 
 * @property MatchsEventType|null $matchs_event_type
 * @property UsrUser|null $usr_user
 * @property Collection|UserPkList[] $user_pk_lists
 *
 * @package App\Models
 */
class PkRoom extends Model
{
	protected $table = 'pk_room';
	protected $primaryKey = 'pk_room_id';
	public $incrementing = false;
	
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'pk_type' => 'int',
		'time_long' => 'int',
		'group_red_circle_count' => 'int',
		'group_blue_circle_count' => 'int',
		'distance_value' => 'float',
		'pk_type_id' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int'
	];

	protected $fillable = [
		'pk_room_id',
		'user_id',
		'matchs_event_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'pk_room_title',
		'pk_room_pwd',
		'pk_type',
		'pk_max_person',
		'time_long',
		'group_red_circle_count',
		'group_blue_circle_count',
		'group_win',
		'distance_value',
		'pk_result_type',
		'pk_type_id',
		'pk_room_code',
		'start_time',
		'stop_time',
		'pk_room_number',
		'group_red_title',
		'group_blue_title'
	];

	public function matchs_event_type()
	{
		return $this->belongsTo(MatchsEventType::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function user_pk_lists()
	{
		return $this->hasMany(UserPkList::class);
	}
}
