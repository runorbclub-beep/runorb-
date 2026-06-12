<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $spider_his_id
 * @property int $created_time
 * @property int $updated_time
 * @property int $new_question
 * @property int $new_answers
 * @property string $created_at
 */
class SpiderHis extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'spider_his_id';

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * @var array
     */
    protected $fillable = ['created_time', 'updated_time', 'created_at','new_question','new_answers'];

}
