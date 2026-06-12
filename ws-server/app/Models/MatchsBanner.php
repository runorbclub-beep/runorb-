<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsBanner
 * 
 * @property string $matchs_banner_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $img_path
 * @property string|null $banner_matchs_id
 *
 * @package App\Models
 */
class MatchsBanner extends Model
{
	protected $table = 'matchs_banner';
	protected $primaryKey = 'matchs_banner_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'matchs_banner_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'img_path',
		'banner_matchs_id'
	];
}
