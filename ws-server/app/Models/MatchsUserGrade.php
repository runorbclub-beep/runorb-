<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsUserGrade
 * 
 * @property string $matchs_user_grade_id
 * @property string|null $matchs_stage_id
 * @property string|null $user_id
 * @property string|null $matchs_user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property float|null $match_grade
 * @property int|null $match_ranking
 * @property int|null $is_group
 * @property float|null $distinct_grade
 * @property int|null $distinct_ranking
 * @property string|null $user_group_id
 * 
 * @property MatchsUser|null $matchs_user
 * @property UsrUser|null $usr_user
 * @property MatchsStage|null $matchs_stage
 * @property Collection|MatchUserStar[] $match_user_stars
 *
 * @package App\Models
 */
class MatchsUserGrade extends Model
{
	protected $table = 'matchs_user_grade';
	protected $primaryKey = 'matchs_user_grade_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'match_grade' => 'float',
		'match_ranking' => 'int',
		'is_group' => 'int',
		'distinct_grade' => 'float',
		'distinct_ranking' => 'int'
	];

	protected $fillable = [
		'matchs_user_grade_id',
		'matchs_stage_id',
		'user_id',
		'matchs_user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'match_grade',
		'match_ranking',
		'is_group',
		'distinct_grade',
		'distinct_ranking',
		'user_group_id'
	];

	public function matchs_user()
	{
		return $this->belongsTo(MatchsUser::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function matchs_stage()
	{
		return $this->belongsTo(MatchsStage::class);
	}

	public function match_user_stars()
	{
		return $this->hasMany(MatchUserStar::class);
	}
}
