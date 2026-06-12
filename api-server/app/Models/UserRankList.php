<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserRankList extends Model
{
	protected $table = 'user_rank_list';
	protected $primaryKey = 'user_rank_list_id';
	public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

	protected $fillable = [
		'user_rank_list_id',
		'user_id',
		'title',
		'json_data',
		'created_time',
		'updated_time',
		'status',
	];

}
