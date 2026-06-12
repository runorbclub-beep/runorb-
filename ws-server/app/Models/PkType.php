<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PkType
 * 
 * @property string $pk_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $pk_type_title
 * 
 * @property Collection|PkRoom[] $pk_rooms
 *
 * @package App\Models
 */
class PkType extends Model
{
	const CREATED_AT = 'created_time';
	const UPDATED_AT = 'updated_time';
	protected $table = 'pk_type';
	protected $primaryKey = 'pk_type_id';
	public $incrementing = false;
	protected $dateFormat = 'U';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int'
	];

	protected $fillable = [
		'pk_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'pk_type_title'
	];

	public function pk_rooms()
	{
		return $this->hasMany(PkRoom::class);
	}
}
