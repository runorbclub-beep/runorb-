<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WebsiteAboutme
 * 
 * @property string $website_aboutme_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $title
 * @property string|null $content
 *
 * @package App\Models
 */
class WebsiteAboutme extends Model
{
	protected $table = 'website_aboutme';
	protected $primaryKey = 'website_aboutme_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'website_aboutme_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'title',
		'content'
	];
}
