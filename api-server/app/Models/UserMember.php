<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserMember
 *
 * @property string $user_members_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $order_id
 * @property string|null $usr_user_id
 * @property string|null $weixin_return_data
 * @property string|null $pay_status
 * @property string|null $pay_time
 * @property float|null $pay_amount
 * @property string|null $invite_code
 *
 * @package App\Models
 */
class UserMember extends Model
{
	protected $table = 'user_members';
	protected $primaryKey = 'user_members_id';
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
		'pay_amount' => 'float'
	];

	protected $fillable = [
		'user_members_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'order_id',
		'usr_user_id',
		'weixin_return_data',
		'pay_status',
		'pay_time',
		'pay_amount',
		'invite_code',
        'is_buy_runball'
	];
}
