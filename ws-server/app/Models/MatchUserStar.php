<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchUserStar
 * 
 * @property string $match_user_star_id
 * @property string|null $user_id
 * @property string|null $matchs_user_grade_id
 * @property string|null $matchs_user_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * 
 * @property MatchsUser|null $matchs_user
 * @property UsrUser|null $usr_user
 * @property MatchsUserGrade|null $matchs_user_grade
 *
 * @package App\Models
 */
class MatchUserStar extends Model
{
	protected $table = 'match_user_star';
	protected $primaryKey = 'match_user_star_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'match_user_star_id',
		'user_id',
		'matchs_user_grade_id',
		'matchs_user_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status'
	];

	public function matchs_user()
	{
		return $this->belongsTo(MatchsUser::class);
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function matchs_user_grade()
	{
		return $this->belongsTo(MatchsUserGrade::class);
	}
}
