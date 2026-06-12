<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SysMember
 *
 * @property string $sys_member_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $title_cn
 * @property string|null $title_en
 * @property float|null $members_amount
 * @property string|null $members_description_cn
 * @property string|null $members_description_en
 * @property string|null $currency
 *
 * @package App\Models
 */
class SysMember extends Model
{
	protected $table = 'sys_member';
	protected $primaryKey = 'sys_member_id';
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
		'members_amount' => 'float'
	];

	protected $fillable = [
		'sys_member_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'title_cn',
		'title_en',
		'members_amount',
		'members_description_cn',
		'members_description_en',
		'currency'
	];
}
