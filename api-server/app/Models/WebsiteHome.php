<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WebsiteHome
 *
 * @property string $website_home_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $title_cn
 * @property string|null $title_en
 * @property string|null $subtitle
 * @property string|null $content
 * @property string|null $source
 * @property int|null $source_type
 * @property int|null $index
 * @property string|null $content_en
 *
 * @package App\Models
 */
class WebsiteHome extends Model
{
	protected $table = 'website_home';
	protected $primaryKey = 'website_home_id';
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
		'source_type' => 'int',
		'index' => 'int'
	];

	protected $fillable = [
		'website_home_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'title_cn',
		'title_en',
		'subtitle',
		'content',
		'source',
		'source_type',
		'index',
		'content_en'
	];
}
