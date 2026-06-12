<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Match
 *
 * @property string $matchs_id
 * @property string|null $matchs_type_id
 * @property string|null $mat_matchs_id
 * @property string|null $matchs_event_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $match_title
 * @property string|null $match_site
 * @property string|null $match_champion_prize
 * @property string|null $match_join_prize
 * @property int|null $match_group_type
 * @property int|null $match_user_max
 * @property int|null $match_user_sign_count
 * @property string|null $match_champion_prize_description
 * @property string|null $match_join_prize_description
 * @property string|null $match_description
 * @property string|null $match_phone
 * @property string|null $match_email
 * @property string|null $match_start_time
 * @property string|null $match_stopt_time
 * @property string|null $match_image
 * @property string|null $match_user_type
 * @property string|null $match_user_sex
 *
 * @property MatchsType|null $matchs_type
 * @property Match|null $match
 * @property MatchsEventType|null $matchs_event_type
 * @property Collection|Match[] $matches
 * @property Collection|MatchsStage[] $matchs_stages
 * @property Collection|MatchsUser[] $matchs_users
 *
 * @package App\Models
 */
class Match extends Model
{
	const CREATED_AT = 'created_time';
	const UPDATED_AT = 'updated_time';
	protected $table = 'matchs';
	protected $primaryKey = 'matchs_id';
	public $incrementing = false;
	protected $dateFormat = 'U';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'match_group_type' => 'int',
		'match_user_max' => 'int',
        'match_user_sign_count'=> 'int',
	];

	protected $fillable = [
		'matchs_id',
        'match_user_sex',
        'match_user_type',
        'match_user_sign_count',
		'matchs_type_id',
		'mat_matchs_id',
		'matchs_event_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'match_title',
		'match_site',
		'match_champion_prize',
		'match_join_prize',
		'match_group_type',
		'match_user_max',
		'match_champion_prize_description',
		'match_join_prize_description',
		'match_description',
		'match_phone',
		'match_email',
		'match_start_time',
		'match_stopt_time',
		'match_image'
	];

	public function matchs_type()
	{
		return $this->belongsTo(MatchsType::class);
	}

	public function match()
	{
		return $this->belongsTo(Match::class, 'mat_matchs_id');
	}

	public function matchs_event_type()
	{
		return $this->belongsTo(MatchsEventType::class);
	}

	public function matches()
	{
		return $this->hasMany(Match::class, 'mat_matchs_id');
	}

	public function matchs_stages()
	{
		return $this->hasMany(MatchsStage::class, 'matchs_id');
	}

	public function matchs_users()
	{
		return $this->hasMany(MatchsUser::class, 'matchs_id');
	}
}
