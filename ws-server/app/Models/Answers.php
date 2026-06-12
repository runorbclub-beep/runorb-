<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $a_id
 * @property int $q_id
 * @property int $created_time
 * @property int $updated_time
 * @property string $answers_id
 * @property string $question_id
 * @property string $type
 * @property string $created_at
 * @property string $content
 * @property string $author_name
 * @property string $author_id
 * @property string $author_avatar_url
 * @property int $voteup_count
 * @property int $createdat
 * @property string $answers_answers_id
 * @property string $reply_to_author
 * @property Question $question
 */
class Answers extends Model
{
    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'a_id';

    /**
     * @var array
     */
    protected $fillable = ['q_id', 'created_time', 'updated_time', 'answers_id', 'question_id', 'type', 'created_at', 'content', 'author_name', 'author_id', 'author_avatar_url', 'voteup_count', 'createdat', 'answers_answers_id', 'reply_to_author'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question()
    {
        return $this->belongsTo('App\Models\Question', 'q_id', 'q_id');
    }
}
