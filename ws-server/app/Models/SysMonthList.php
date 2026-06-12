<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysMonthList
 * 
 * @property string $sys_month_list_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property string|null $year
 * @property string|null $month
 * @property int|null $speed_max
 * @property int|null $circle_count
 * @property int|null $endurance_max
 * 
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class SysMonthList extends Model
{
	protected $table = 'sys_month_list';
	protected $primaryKey = 'sys_month_list_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int',
		'speed_max' => 'int',
		'circle_count' => 'int',
		'endurance_max' => 'int'
	];

	protected $fillable = [
		'sys_month_list_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'start_time',
		'stop_time',
		'year',
		'month',
		'speed_max',
		'circle_count',
		'endurance_max'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
