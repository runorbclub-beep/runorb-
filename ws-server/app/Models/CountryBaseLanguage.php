<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CountryBaseLanguage
 * 
 * @property int $country_base_language_id
 * @property int|null $sys_language_id
 * @property int|null $sys_country_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property int|null $created_uid
 * @property int|null $updated_uid
 * @property int|null $status
 * 
 * @property SysLanguage|null $sys_language
 * @property SysCountry|null $sys_country
 *
 * @package App\Models
 */
class CountryBaseLanguage extends Model
{
	protected $table = 'country_base_language';
	protected $primaryKey = 'country_base_language_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'country_base_language_id' => 'int',
		'sys_language_id' => 'int',
		'sys_country_id' => 'int',
		'created_time' => 'int',
		'updated_time' => 'int',
		'created_uid' => 'int',
		'updated_uid' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'country_base_language_id',
		'sys_language_id',
		'sys_country_id',
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
