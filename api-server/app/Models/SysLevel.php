<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SysLevel
 *
 * @property string $sys_level_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $user_medal_name
 * @property string|null $description
 * @property string|null $medal_conditions
 *
 * @property Collection|UserLevelAssociated[] $user_level_associateds
 *
 * @package App\Models
 */
class SysLevel extends Model
{
	protected $table = 'sys_level';
	protected $primaryKey = 'sys_level_id';
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
		'sys_level_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'user_medal_name',
		'description',
		'medal_conditions'
	];

	public function user_level_associateds()
	{
		return $this->hasMany(UserLevelAssociated::class);
	}
}
