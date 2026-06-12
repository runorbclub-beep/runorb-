<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property float|null $runball_exponent
 * @property int|null $speed_max_time
 * @property int|null $runball_exponent_time
 * @property int|null $exponent_molecular_time
 * @property int|null $marathon_time
 * @property int|null $win_num
 * @property int|null $join_match_count
 *
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 * @method static where(string $string, $userClanMemberId)
 * @method static whereIn(string $string, $userClanMemberId)
 */
class UserAchievement extends Model
{
	protected $table = 'user_achievement';
	protected $primaryKey = 'user_achievement_id';
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
		'exponent_molecular' => 'float',
		'exponent_speed_max' => 'int',
		'runball_exponent' => 'float',
		'speed_max_time' => 'int',
		'runball_exponent_time' => 'int',
		'exponent_molecular_time' => 'int',
		'exponent_speed_max_time' => 'int',
		'marathon_time' => 'int',
		'win_num' => 'int',
		'join_match_count' => 'int'
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
		'exponent_molecular',
		'exponent_speed_max',
		'runball_exponent',
		'speed_max_time',
		'runball_exponent_time',
		'exponent_molecular_time',
		'exponent_speed_max_time',
		'marathon_time',
		'win_num',
		'join_match_count'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

    /**
     * 成绩表对用户表 一对一
     * @return HasOne
     * User: zxw
     * Date: 2022/1/4 15:47
     */
    public function usr_user_one(): HasOne
    {
        return $this->hasOne(UsrUser::class, 'user_id', 'user_id');
    }
}
