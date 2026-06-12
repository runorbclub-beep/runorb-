<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SysMedal
 * 
 * @property string $sys_medal_id
 * @property string|null $sys_sys_medal_id
 * @property string|null $sys_sys_medal_id2
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $user_medal_name_cn
 * @property string|null $description_cn
 * @property string|null $medal_conditions
 * @property string|null $level_name
 * @property string|null $medal_image
 * @property string|null $medal_image_active
 * @property string|null $description_en
 * @property string|null $user_medal_name_en
 * 
 * @property SysMedal|null $sys_medal
 * @property Collection|SysMedal[] $sys_medals
 * @property Collection|UserMedalAssociated[] $user_medal_associateds
 *
 * @package App\Models
 */
class SysMedal extends Model
{
	protected $table = 'sys_medal';
	protected $primaryKey = 'sys_medal_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'sys_medal_id',
		'sys_sys_medal_id',
		'sys_sys_medal_id2',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'user_medal_name_cn',
		'description_cn',
		'medal_conditions',
		'level_name',
		'medal_image',
		'medal_image_active',
		'description_en',
		'user_medal_name_en'
	];

	public function sys_medal()
	{
		return $this->belongsTo(SysMedal::class, 'sys_sys_medal_id2');
	}

	public function sys_medals()
	{
		return $this->hasMany(SysMedal::class, 'sys_sys_medal_id2');
	}

	public function user_medal_associateds()
	{
		return $this->hasMany(UserMedalAssociated::class);
	}
}
