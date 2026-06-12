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
 * @property string|null $app_version_ios
 * @property string|null $app_version_android
 * @property string|null $app_description_android_cn
 * @property string|null $app_description_android_en
 * @property string|null $app_description_ios_cn
 * @property string|null $app_description_ios_en
 * @property string|null $app_package_path_android
 * @property string|null $app_package_path_ios
 * @property int|null $app_android_code
 * @property int|null $is_strong_update
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
		'app_android_code' => 'int'
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
		'app_image_android',
		'app_version_ios',
		'app_version_android',
		'app_description_android_cn',
		'app_description_android_en',
		'app_description_ios_cn',
		'app_description_ios_en',
		'app_package_path_android',
		'app_package_path_ios',
		'app_android_code',
        'is_strong_update'
	];
}
