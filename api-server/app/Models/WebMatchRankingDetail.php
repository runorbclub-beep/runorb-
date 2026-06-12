<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WebMatchRankingDetail
 *
 * @property string $web_match_ranking_detail_id
 * @property string|null $web_match_ranking_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $user_name
 * @property string|null $usr_user_id
 * @property string|null $user_img
 * @property float|null $value
 * @property string|null $unit
 * @property string|null $join_time
 * @property string|null $value_format
 *
 * @package App\Models
 */
class WebMatchRankingDetail extends Model
{
	protected $table = 'web_match_ranking_detail';
	protected $primaryKey = 'web_match_ranking_detail_id';
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
		'value' => 'float'
	];

	protected $fillable = [
		'web_match_ranking_detail_id',
		'web_match_ranking_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'user_name',
		'usr_user_id',
		'user_img',
		'value',
		'unit',
		'join_time',
		'value_format'
	];
}
