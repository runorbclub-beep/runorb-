<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SysLanguage
 * 
 * @property string $sys_language_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $language_code
 * @property string|null $chinese
 * @property string|null $english
 * @property string|null $japanese
 * @property string|null $korean
 * @property string|null $french
 * @property string|null $german
 * @property string|null $russian
 * @property string|null $italy
 * @property string|null $indonesian
 * @property string|null $arabic
 * @property string|null $spanish
 * @property string|null $portuguese
 * 
 * @property Collection|SysCountryBaseLanguage[] $sys_country_base_languages
 *
 * @package App\Models
 */
class SysLanguage extends Model
{
	protected $table = 'sys_language';
	protected $primaryKey = 'sys_language_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'sys_language_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'language_code',
		'chinese',
		'english',
		'japanese',
		'korean',
		'french',
		'german',
		'russian',
		'italy',
		'indonesian',
		'arabic',
		'spanish',
		'portuguese'
	];

	public function sys_country_base_languages()
	{
		return $this->hasMany(SysCountryBaseLanguage::class);
	}
}
