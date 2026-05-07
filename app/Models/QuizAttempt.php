<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $table = 'quiz_attempts';

    protected $fillable = [
        'quiz_record_id',
        'question_id',
        'selected_option_id',
        'is_correct',
    ];

    public function record()
    {
        return $this->belongsTo(QuizRecord::class, 'quiz_record_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
