<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysRankingType
 *
 * @property string $sys_ranking_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $ranking_title_zh
 * @property string|null $ranking_title_en
 * @property string|null $ranking_type
 * @property string|null $ranking_rule_zh
 * @property string|null $ranking_rule_en
 * @property int|null $ranking_index
 *
 * @package App\Models
 */
class SysRankingType extends Model
{
	protected $table = 'sys_ranking_type';
	protected $primaryKey = 'sys_ranking_type_id';
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
		'ranking_index' => 'int'
	];

	protected $fillable = [
		'sys_ranking_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'ranking_title_zh',
		'ranking_title_en',
		'ranking_type',
		'ranking_rule_zh',
		'ranking_rule_en',
		'ranking_index'
	];
}
