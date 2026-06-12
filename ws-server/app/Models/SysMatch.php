<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SysMatch
 * 
 * @property string $sys_match_id
 * @property string|null $matchs_type_id
 * @property string|null $sys_sys_match_id
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
 * @property string|null $match_champion_prize_description
 * @property string|null $match_join_prize_description
 * @property string|null $match_description
 * @property string|null $match_phone
 * @property string|null $match_email
 * @property string|null $match_start_time
 * @property string|null $match_stop_time
 * @property string|null $match_image
 * @property string|null $match_user_type
 * @property string|null $match_user_sex
 * @property int|null $match_user_sign_count
 * @property string|null $match_phone_prefix
 * @property string|null $match_user_type_description
 * @property string|null $match_user_sex_description
 * @property string|null $match_join_time
 * @property int|null $match_status
 * @property int|null $is_group
 * 
 * @property MatchsType|null $matchs_type
 * @property SysMatch|null $sys_match
 * @property MatchsEventType|null $matchs_event_type
 * @property Collection|MatchsStage[] $matchs_stages
 * @property Collection|MatchsUser[] $matchs_users
 * @property Collection|SysMatch[] $sys_matches
 *
 * @package App\Models
 */
class SysMatch extends Model
{
	protected $table = 'sys_match';
	protected $primaryKey = 'sys_match_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'match_group_type' => 'int',
		'match_user_max' => 'int',
		'match_user_sign_count' => 'int',
		'match_status' => 'int',
		'is_group' => 'int'
	];

	protected $fillable = [
		'sys_match_id',
		'matchs_type_id',
		'sys_sys_match_id',
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
		'match_stop_time',
		'match_image',
		'match_user_type',
		'match_user_sex',
		'match_user_sign_count',
		'match_phone_prefix',
		'match_user_type_description',
		'match_user_sex_description',
		'match_join_time',
		'match_status',
		'is_group'
	];

	public function matchs_type()
	{
		return $this->belongsTo(MatchsType::class);
	}

	public function sys_match()
	{
		return $this->belongsTo(SysMatch::class, 'sys_sys_match_id');
	}

	public function matchs_event_type()
	{
		return $this->belongsTo(MatchsEventType::class);
	}

	public function matchs_stages()
	{
		return $this->hasMany(MatchsStage::class);
	}

	public function matchs_users()
	{
		return $this->hasMany(MatchsUser::class);
	}

	public function sys_matches()
	{
		return $this->hasMany(SysMatch::class, 'sys_sys_match_id');
	}
}
