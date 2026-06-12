<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserPkList
 *
 * @property string $user_pk_list_id
 * @property string|null $user_id
 * @property string|null $pk_room_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property int|null $duration
 * @property int|null $circle_count
 * @property string|null $user_group
 * @property string|null $group_win
 * @property string|null $user_group_title
 * @property string|null $distance
 *
 * @property PkRoom|null $pk_room
 * @property UsrUser|null $usr_user
 *
 * @package App\Models
 */
class UserPkList extends Model
{
	protected $table = 'user_pk_list';
	protected $primaryKey = 'user_pk_list_id';
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
		'duration' => 'int',
		'circle_count' => 'int'
	];

	protected $fillable = [
		'user_pk_list_id',
		'user_id',
		'pk_room_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'duration',
		'circle_count',
		'user_group',
		'group_win',
		'user_group_title',
		'distance'
	];

	public function pk_room()
	{
		return $this->belongsTo(PkRoom::class,'pk_room_id','pk_room_id');
	}

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id','user_id');
	}

    public function user_play()
    {
        return $this->belongsTo(UserPlay::class,'user_pk_list_id','user_pk_list_id');
    }
}
