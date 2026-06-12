<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserGradetWeek
 * 
 * @property string $user_grade_week_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $duration
 * @property int|null $speed_max
 * @property int|null $circle_count
 * @property int|null $play_count
 * @property int|null $endurance_max
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property string|null $month
 * @property string|null $year
 * 
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserGradetWeek extends Model
{
	protected $table = 'user_gradet_week';
	protected $primaryKey = 'user_grade_week_id';
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
		'play_count' => 'int',
		'endurance_max' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int'
	];

	protected $fillable = [
		'user_grade_week_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'duration',
		'speed_max',
		'circle_count',
		'play_count',
		'endurance_max',
		'start_time',
		'stop_time',
		'month',
		'year'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
