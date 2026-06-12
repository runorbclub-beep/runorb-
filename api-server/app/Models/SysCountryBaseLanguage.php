<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysCountryBaseLanguage
 *
 * @property string $sys_country_base_language_id
 * @property string|null $sys_language_id
 * @property string|null $sys_country_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 *
 * @property SysLanguage|null $sys_language
 * @property SysCountry|null $sys_country
 *
 * @package App\Models
 */
class SysCountryBaseLanguage extends Model
{
	protected $table = 'sys_country_base_language';
	protected $primaryKey = 'sys_country_base_language_id';
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
		'status' => 'int'
	];

	protected $fillable = [
		'sys_country_base_language_id',
		'sys_language_id',
		'sys_country_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status'
	];

	public function sys_language()
	{
		return $this->belongsTo(SysLanguage::class);
	}

	public function sys_country()
	{
		return $this->belongsTo(SysCountry::class);
	}
}
