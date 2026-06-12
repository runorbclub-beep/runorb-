<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchsEventType
 *
 * @property string $matchs_event_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $match_events_type_title
 * @property float|null $match_events_distance_value
 * @property int|null $index
 * @property string|null $match_events_type_title_en
 *
 * @property Collection|PkRoom[] $pk_rooms
 * @property Collection|SysMatch[] $sys_matches
 *
 * @package App\Models
 */
class MatchsEventType extends Model
{
	protected $table = 'matchs_event_type';
	protected $primaryKey = 'matchs_event_type_id';
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
		'match_events_distance_value' => 'float',
		'index' => 'int'
	];

	protected $fillable = [
		'matchs_event_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'match_events_type_title',
		'match_events_distance_value',
		'index',
		'match_events_type_title_en'
	];

	public function pk_rooms()
	{
		return $this->hasMany(PkRoom::class);
	}

	public function sys_matches()
	{
		return $this->hasMany(SysMatch::class);
	}
}
