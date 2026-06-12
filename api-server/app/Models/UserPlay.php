<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property int|null $is_abnormal
 * @property string|null $exponent_molecular
 * @property string|null $exponent_denominator
 * @property string|null $exponent
 * @property string|null $marathon
 *
 * @property MatchsStage|null $matchs_stage
 * @property MatchsUser|null $matchs_user
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
		'weight' => 'float',
		'calories' => 'float',
		'duration' => 'int',
		'speed_max' => 'int',
		'circle_count' => 'int',
		'endurance_max' => 'int',
		'exponent_speed_max' => 'int',
		'compare_last' => 'int',
		'start_time' => 'int',
		'stop_time' => 'int',
		'distance' => 'float',
		'is_abnormal' => 'int'
	];

	protected $fillable = [
		'user_play_id',
        'source',
		'matchs_stage_id',
		'user_id',
		'user_pk_list_id',
		'sys_shake_id',
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
		'distance',
		'is_abnormal',
		'exponent_molecular',
		'exponent_denominator',
		'exponent_speed_max',
		'exponent',
		'marathon'
	];

	public function matchs_stage()
	{
		return $this->belongsTo(MatchsStage::class);
	}

	public function matchs_user()
	{
		return $this->belongsTo(MatchsUser::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id','user_id');
	}

	public function user_play_details()
	{
		return $this->hasMany(UserPlayDetail::class);
	}

	public function user_play_progresses()
	{
		return $this->hasMany(UserPlayProgress::class);
	}

    /**
     * 用户运动表与企业名单 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/11/15 17:01
     */
    public function qiye_shake_user_one(): HasOne
    {
        return $this->hasOne(QiyeShakeUser::class,'user_id','user_id');
    }
}
