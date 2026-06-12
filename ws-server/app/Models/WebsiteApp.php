<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WebsiteApp
 * 
 * @property string $website_app_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $app_version
 * @property string|null $app_image
 * @property string|null $app_update_time
 * @property string|null $app_image_ios
 * @property string|null $app_image_android
 *
 * @package App\Models
 */
class WebsiteApp extends Model
{
	protected $table = 'website_app';
	protected $primaryKey = 'website_app_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'website_app_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'app_version',
		'app_image',
		'app_update_time',
		'app_image_ios',
		'app_image_android'
	];
}
