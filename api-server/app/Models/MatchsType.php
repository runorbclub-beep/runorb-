<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsType
 *
 * @property string $matchs_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $matchs_type_title
 *
 * @property Collection|MatchsStageRule[] $matchs_stage_rules
 * @property Collection|SysMatch[] $sys_matches
 *
 * @package App\Models
 */
class MatchsType extends Model
{
	protected $table = 'matchs_type';
	protected $primaryKey = 'matchs_type_id';
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
		'status' => 'int'
	];

	protected $fillable = [
		'matchs_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'matchs_type_title'
	];

	public function matchs_stage_rules()
	{
		return $this->hasMany(MatchsStageRule::class);
	}

	public function sys_matches()
	{
		return $this->hasMany(SysMatch::class);
	}
}
