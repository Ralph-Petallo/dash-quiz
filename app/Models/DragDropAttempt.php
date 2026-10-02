<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DragDropAttempt extends Model
{
    use HasFactory;

    protected $table = 'drag_drop_attempts';

    protected $fillable = [
        'quiz_id',
        'question_id',
        'quiz_record_id',
        'user_id',

        'completed_steps',
        'correct_steps',
        'total_steps',
        'mistake_count',

        'percentage',
        'passed',

        'elapsed_time',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps' => 'integer',
        'correct_steps' => 'integer',
        'total_steps' => 'integer',
        'mistake_count' => 'integer',

        'percentage' => 'decimal:2',
        'passed' => 'boolean',

        'elapsed_time' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(
            Quiz::class,
            'quiz_id'
        );
    }

    public function question()
    {
        return $this->belongsTo(
            Question::class,
            'question_id'
        );
    }

    public function record()
    {
        return $this->belongsTo(
            QuizRecord::class,
            'quiz_record_id'
        );
    }

    public function quizRecord()
    {
        return $this->belongsTo(
            QuizRecord::class,
            'quiz_record_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            Dasher::class,
            'user_id'
        );
    }

    public function steps()
    {
        return $this->hasMany(
            DragDropAttemptStep::class,
            'drag_drop_attempt_id'
        )->orderBy('sequence_number');
    }
}