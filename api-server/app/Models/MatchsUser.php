<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsUser
 *
 * @property string $matchs_user_id
 * @property string|null $sys_match_id
 * @property string|null $user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $is_join
 * @property int|null $is_like
 * @property int|null $integral_total
 * @property int|null $start_total
 * @property string|null $sys_sys_match_id
 * @property string|null $user_group_id
 * @property int|null $is_group
 * @property int|null $user_group_finish_time
 * @property string|null $user_name
 * @property string|null $user_group_name
 * @property int|null $stage_pass
 *
 * @property SysMatch|null $sys_match
 * @property UsrUser|null $usr_user
 * @property UserGroup|null $user_group
 * @property Collection|MatchUserStar[] $match_user_stars
 * @property Collection|MatchsUserGrade[] $matchs_user_grades
 * @property Collection|MatchsUserIntegral[] $matchs_user_integrals
 * @property Collection|UserPlay[] $user_plays
 *
 * @package App\Models
 */
class MatchsUser extends Model
{
	protected $table = 'matchs_user';
	protected $primaryKey = 'matchs_user_id';
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
		'is_join' => 'int',
		'is_like' => 'int',
		'integral_total' => 'int',
		'start_total' => 'int',
		'is_group' => 'int',
		'user_group_finish_time' => 'int',
		'stage_pass' => 'int'
	];

	protected $fillable = [
		'matchs_user_id',
		'sys_match_id',
		'user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'is_join',
		'is_like',
		'integral_total',
		'start_total',
		'sys_sys_match_id',
		'user_group_id',
		'is_group',
		'user_group_finish_time',
		'user_name',
		'user_group_name',
		'stage_pass',
        'team_tag',
        'is_quartets',
        's_match_point',
	];

	public function sys_match()
	{
		return $this->belongsTo(SysMatch::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

    public function usr_user_one()
    {
        return $this->hasOne(UsrUser::class, 'user_id','user_id');
    }

	public function user_group()
	{
		return $this->belongsTo(UserGroup::class);
	}

	public function match_user_stars()
	{
		return $this->hasMany(MatchUserStar::class);
	}

	public function matchs_user_grades()
	{
		return $this->hasMany(MatchsUserGrade::class);
	}

	public function matchs_user_integrals()
	{
		return $this->hasMany(MatchsUserIntegral::class);
	}

	public function user_plays()
	{
		return $this->hasMany(UserPlay::class);
	}
}
