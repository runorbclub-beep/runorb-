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
 * @property int|null $match_group_user_num
 * @property int|null $match_max_sign_count
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
		'pk_person_time' => 'int',
		'pk_group_time' => 'int',
		'pk_group_user' => 'int',
		'exponent_molecular' => 'int',
		'exponent_denominator' => 'float',
		'match_group_user_num' => 'int',
		'match_max_sign_count' => 'int',
        'each_integral' => 'int',
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
		'exponent_denominator_tips_zh',
		'match_group_user_num',
		'match_max_sign_count',
        'each_integral',
        'integral_exchange_ratio'
	];
}
