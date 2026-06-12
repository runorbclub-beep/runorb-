<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysMessage
 * 
 * @property string $sys_message_id
 * @property string|null $user_id
 * @property string|null $sys_message_type_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $message_content
 * @property int|null $is_read
 * @property int|null $sent_user_id
 * 
 * @property UsrUser|null $usr_user
 * @property SysMessageType|null $sys_message_type
 *
 * @package App\Models
 */
class SysMessage extends Model
{
	protected $table = 'sys_message';
	protected $primaryKey = 'sys_message_id';
	public $incrementing = false;
	
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'is_read' => 'int',
		'sent_user_id' => 'int'
	];

	protected $fillable = [
		'sys_message_id',
		'user_id',
		'sys_message_type_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'message_content',
		'is_read',
		'sent_user_id'
	];

	public function usr_user()
	{
		return $this->belongsTo(UsrUser::class, 'user_id');
	}

	public function sys_message_type()
	{
		return $this->belongsTo(SysMessageType::class);
	}
}
