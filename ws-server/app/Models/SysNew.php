<?php

/**
 * Created by Reliese Model.
 */

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
 *
 * @package App\Models
 */
class SysNew extends Model
{
	protected $table = 'sys_new';
	protected $primaryKey = 'sys_new_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'news_type' => 'int',
		'view_num' => 'int'
	];

	protected $fillable = [
		'sys_new_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'news_title',
		'news_type',
		'news_content',
		'view_num',
		'news_img'
	];
}
