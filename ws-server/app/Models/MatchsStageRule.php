<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsStageRule
 * 
 * @property string $matchs_stage_rule_id
 * @property string|null $matchs_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $match_promotion_type
 * @property float|null $match_promotion_value
 * @property string|null $match_rules_title
 * 
 * @property MatchsType|null $matchs_type
 * @property Collection|MatchsStage[] $matchs_stages
 *
 * @package App\Models
 */
class MatchsStageRule extends Model
{
	protected $table = 'matchs_stage_rule';
	protected $primaryKey = 'matchs_stage_rule_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'match_promotion_type' => 'int',
		'match_promotion_value' => 'float'
	];

	protected $fillable = [
		'matchs_stage_rule_id',
		'matchs_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'match_promotion_type',
		'match_promotion_value',
		'match_rules_title'
	];

	public function matchs_type()
	{
		return $this->belongsTo(MatchsType::class);
	}

	public function matchs_stages()
	{
		return $this->hasMany(MatchsStage::class);
	}
}
