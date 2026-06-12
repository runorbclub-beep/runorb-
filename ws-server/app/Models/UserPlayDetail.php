<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserPlayDetail
 * 
 * @property string $user_play_detail_id
 * @property string|null $user_play_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $section_duration
 * @property string|null $speed_detail
 * @property int|null $speed_interval
 * 
 * @property UserPlay|null $user_play
 *
 * @package App\Models
 */
class UserPlayDetail extends Model
{
	protected $table = 'user_play_detail';
	protected $primaryKey = 'user_play_detail_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'speed_interval' => 'int'
	];

	protected $fillable = [
		'user_play_detail_id',
		'user_play_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'section_duration',
		'speed_detail',
		'speed_interval'
	];

	public function user_play()
	{
		return $this->belongsTo(UserPlay::class);
	}
}
