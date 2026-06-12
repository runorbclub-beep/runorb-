<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $q_id
 * @property int $created_time
 * @property int $updated_time
 * @property string $question_id
 * @property string $created_at
 * @property string $channel
 * @property string $type
 * @property int $voteup_count
 * @property string $url
 * @property string $content
 * @property int $createdat
 * @property string $title
 * @property Answer[] $answers
 */
class Question extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'question';

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'q_id';

    /**
     * @var array
     */
    protected $fillable = ['created_time', 'updated_time', 'question_id', 'created_at', 'channel', 'type', 'voteup_count', 'url', 'content', 'createdat', 'title'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->hasMany('App\Models\Answer', 'q_id', 'q_id');
    }
}
