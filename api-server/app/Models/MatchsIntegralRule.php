<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsIntegralRule
 *
 * @property string $matchs_integral_rule_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $integral_rules_title
 * @property int|null $max_integral
 * @property int|null $sub_integral
 * @property int|null $get_integral_type
 * @property float|null $get_integral_value
 *
 * @property Collection|MatchsStage[] $matchs_stages
 *
 * @package App\Models
 */
class MatchsIntegralRule extends Model
{
	protected $table = 'matchs_integral_rule';
	protected $primaryKey = 'matchs_integral_rule_id';
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
		'max_integral' => 'int',
		'sub_integral' => 'int',
		'get_integral_type' => 'int',
		'get_integral_value' => 'float'
	];

	protected $fillable = [
		'matchs_integral_rule_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'integral_rules_title',
		'max_integral',
		'sub_integral',
		'get_integral_type',
		'get_integral_value'
	];

	public function matchs_stages()
	{
		return $this->hasMany(MatchsStage::class);
	}
}
