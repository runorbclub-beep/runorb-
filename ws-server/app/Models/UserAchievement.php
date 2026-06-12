<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAchievement
 * 
 * @property string $user_achievement_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $duration
 * @property int|null $speed_max
 * @property int|null $circle_count
 * @property int|null $endurance_max
 * @property int|null $play_count
 * @property float|null $distance_max
 * @property int|null $thrmin
 * @property int|null $half_marathon
 * @property int|null $marathon
 * @property float|null $exponent_denominator
 * @property float|null $exponent_molecular
 * 
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserAchievement extends Model
{
	protected $table = 'user_achievement';
	protected $primaryKey = 'user_achievement_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'duration' => 'int',
		'speed_max' => 'int',
		'circle_count' => 'int',
		'endurance_max' => 'int',
		'play_count' => 'int',
		'distance_max' => 'float',
		'thrmin' => 'int',
		'half_marathon' => 'int',
		'marathon' => 'int',
		'exponent_denominator' => 'float',
		'exponent_molecular' => 'float'
	];

	protected $fillable = [
		'user_achievement_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'duration',
		'speed_max',
		'circle_count',
		'endurance_max',
		'play_count',
		'distance_max',
		'thrmin',
		'half_marathon',
		'marathon',
		'exponent_denominator',
		'exponent_molecular'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
