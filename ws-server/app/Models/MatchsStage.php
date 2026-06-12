<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsStage
 * 
 * @property string $matchs_stage_id
 * @property string|null $sys_match_id
 * @property string|null $matchs_integral_rule_id
 * @property string|null $matchs_stage_rule_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $match_stage_title
 * @property string|null $match_stage_start_time
 * @property string|null $match_stage_stop_time
 * @property int|null $max_integral
 * @property int|null $sub_integral
 * @property int|null $get_integral_type
 * @property float|null $get_integral_value
 * @property int|null $match_promotion_type
 * @property float|null $match_promotion_value
 * @property string|null $sys_sys_match_id
 * @property float|null $match_stage_distance
 * @property int|null $view_type
 * @property int|null $matchs_stage_status
 * 
 * @property SysMatch|null $sys_match
 * @property MatchsStageRule|null $matchs_stage_rule
 * @property MatchsIntegralRule|null $matchs_integral_rule
 * @property Collection|MatchsUserGrade[] $matchs_user_grades
 * @property Collection|MatchsUserIntegral[] $matchs_user_integrals
 * @property Collection|UserPlay[] $user_plays
 *
 * @package App\Models
 */
class MatchsStage extends Model
{
	protected $table = 'matchs_stage';
	protected $primaryKey = 'matchs_stage_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'max_integral' => 'int',
		'sub_integral' => 'int',
		'get_integral_type' => 'int',
		'get_integral_value' => 'float',
		'match_promotion_type' => 'int',
		'match_promotion_value' => 'float',
		'match_stage_distance' => 'float',
		'view_type' => 'int',
		'matchs_stage_status' => 'int'
	];

	protected $fillable = [
		'matchs_stage_id',
		'sys_match_id',
		'matchs_integral_rule_id',
		'matchs_stage_rule_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'match_stage_title',
		'match_stage_start_time',
		'match_stage_stop_time',
		'max_integral',
		'sub_integral',
		'get_integral_type',
		'get_integral_value',
		'match_promotion_type',
		'match_promotion_value',
		'sys_sys_match_id',
		'match_stage_distance',
		'view_type',
		'matchs_stage_status'
	];

	public function sys_match()
	{
		return $this->belongsTo(SysMatch::class);
	}

	public function matchs_stage_rule()
	{
		return $this->belongsTo(MatchsStageRule::class);
	}

	public function matchs_integral_rule()
	{
		return $this->belongsTo(MatchsIntegralRule::class);
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
