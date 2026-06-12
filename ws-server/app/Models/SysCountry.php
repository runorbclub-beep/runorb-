<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SysCountry
 * 
 * @property string $sys_country_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $name_cn
 * @property string|null $name_en
 * @property string|null $country_code
 * @property string|null $default_language
 * @property int|null $order_index
 * 
 * @property Collection|SysCountryBaseLanguage[] $sys_country_base_languages
 * @property Collection|UsrUser[] $usr_users
 *
 * @package App\Models
 */
class SysCountry extends Model
{
	protected $table = 'sys_country';
	protected $primaryKey = 'sys_country_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'order_index' => 'int'
	];

	protected $fillable = [
		'sys_country_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'name_cn',
		'name_en',
		'country_code',
		'default_language',
		'order_index'
	];

	public function sys_country_base_languages()
	{
		return $this->hasMany(SysCountryBaseLanguage::class);
	}

	public function usr_users()
	{
		return $this->hasMany(UsrUser::class);
	}
}
