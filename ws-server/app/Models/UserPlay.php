<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserPlay
 * 
 * @property string $user_play_id
 * @property string|null $matchs_stage_id
 * @property string|null $user_id
 * @property string|null $user_pk_list_id
 * @property string|null $matchs_user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property float|null $weight
 * @property float|null $calories
 * @property int|null $duration
 * @property int|null $speed_max
 * @property int|null $circle_count
 * @property int|null $endurance_max
 * @property int|null $compare_last
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property float|null $distance
 * 
 * @property MatchsStage|null $matchs_stage
 * @property MatchsUser|null $matchs_user
 * @property UserPkList|null $user_pk_list
 * @property UsrUser|null $usr_user
 * @property Collection|UserPlayDetail[] $user_play_details
 * @property Collection|UserPlayProgress[] $user_play_progresses
 *
 * @package App\Models
 */
class UserPlay extends Model
{
	protected $table = 'user_play';
	protected $primaryKey = 'user_play_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'weight' => 'float',
		'calories' => 'float',
		'duration' => 'int',
		'speed_max' => 'int',
		'circle_count' => 'int',
		'endurance_max' => 'int',
		'compare_last' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int',
		'distance' => 'float'
	];

	protected $fillable = [
		'user_play_id',
		'matchs_stage_id',
		'user_id',
		'user_pk_list_id',
		'matchs_user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'weight',
		'calories',
		'duration',
		'speed_max',
		'circle_count',
		'endurance_max',
		'compare_last',
		'start_time',
		'stop_time',
		'distance'
	];

	public function matchs_stage()
	{
		return $this->belongsTo(MatchsStage::class);
	}

	public function matchs_user()
	{
		return $this->belongsTo(MatchsUser::class);
	}

	public function user_pk_list()
	{
		return $this->belongsTo(UserPkList::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function user_play_details()
	{
		return $this->hasMany(UserPlayDetail::class);
	}

	public function user_play_progresses()
	{
		return $this->hasMany(UserPlayProgress::class);
	}
}
