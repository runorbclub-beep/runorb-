<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysSetting
 * 
 * @property string $sys_setting_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $pk_person_time
 * @property int|null $pk_group_time
 * @property int|null $pk_group_user
 * @property string|null $exponent_title_description_en
 * @property string|null $exponent_title_description_zh
 * @property int|null $exponent_molecular
 * @property float|null $exponent_denominator
 * @property string|null $match_stop_tips_en
 * @property string|null $match_stop_tips_zh
 * @property string|null $exponent_molecular_tips_en
 * @property string|null $exponent_molecular_tips_zh
 * @property string|null $exponent_denominator_tips_en
 * @property string|null $exponent_denominator_tips_zh
 *
 * @package App\Models
 */
class SysSetting extends Model
{
	protected $table = 'sys_setting';
	protected $primaryKey = 'sys_setting_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'pk_person_time' => 'int',
		'pk_group_time' => 'int',
		'pk_group_user' => 'int',
		'exponent_molecular' => 'int',
		'exponent_denominator' => 'float'
	];

	protected $fillable = [
		'sys_setting_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'pk_person_time',
		'pk_group_time',
		'pk_group_user',
		'exponent_title_description_en',
		'exponent_title_description_zh',
		'exponent_molecular',
		'exponent_denominator',
		'match_stop_tips_en',
		'match_stop_tips_zh',
		'exponent_molecular_tips_en',
		'exponent_molecular_tips_zh',
		'exponent_denominator_tips_en',
		'exponent_denominator_tips_zh'
	];
}
