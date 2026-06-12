<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WebMatchRanking
 *
 * @property string $web_match_ranking_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $ranking_title
 * @property string|null $ranking_type
 * @property int|null $start_time
 * @property int|null $stop_time
 * @property string|null $ranking_time_type
 *
 * @package App\Models
 */
class WebMatchRanking extends Model
{
	protected $table = 'web_match_ranking';
	protected $primaryKey = 'web_match_ranking_id';
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
		'start_time' => 'int',
		'stop_time' => 'int'
	];

	protected $fillable = [
		'web_match_ranking_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'ranking_title',
		'ranking_type',
		'start_time',
		'stop_time',
		'ranking_time_type',
        'ranking_title_en'
	];
}
