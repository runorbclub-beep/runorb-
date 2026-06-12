<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsUserIntegral
 * 
 * @property string $matchs_user_integral_id
 * @property string|null $user_id
 * @property string|null $matchs_user_id
 * @property string|null $matchs_stage_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $match_integral
 * 
 * @property MatchsUser|null $matchs_user
 * @property UsrUser|null $usr_user
 * @property MatchsStage|null $matchs_stage
 *
 * @package App\Models
 */
class MatchsUserIntegral extends Model
{
	protected $table = 'matchs_user_integral';
	protected $primaryKey = 'matchs_user_integral_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'match_integral' => 'int'
	];

	protected $fillable = [
		'matchs_user_integral_id',
		'user_id',
		'matchs_user_id',
		'matchs_stage_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'match_integral'
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
}
