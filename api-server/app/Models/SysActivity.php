<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysNew
 *
 * @property string $sys_new_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $news_title
 * @property int|null $news_type
 * @property string|null $news_content
 * @property int|null $view_num
 * @property string|null $news_img
 * @property string|null $news_title_en
 * @property string|null $news_content_en
 *
 * @package App\Models
 */
class SysActivity extends Model
{
	protected $table = 'sys_activity';
	protected $primaryKey = 'sys_activity_id';
	public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';


	protected $fillable = [
		'sys_activity_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'title_cn',
		'type',
		'content_cn',
		'view_num',
		'img',
		'title_en',
		'content_en',
        'created_date'
	];
}
