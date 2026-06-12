<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysSeasonList
 *
 * @property string $sys_season_list_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property string|null $year
 * @property boolean|null $season_index
 * @property int|null $speed_max
 * @property int|null $circle_count
 * @property int|null $endurance_max
 *
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class SysSeasonList extends Model
{
	protected $table = 'sys_season_list';
	protected $primaryKey = 'sys_season_list_id';
	public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int',
		'season_index' => 'boolean',
		'speed_max' => 'int',
		'circle_count' => 'int',
		'endurance_max' => 'int'
	];

	protected $fillable = [
		'sys_season_list_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'start_time',
		'stop_time',
		'year',
		'season_index',
		'speed_max',
		'circle_count',
		'endurance_max'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}
}
