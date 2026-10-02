<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizRecord extends Model
{
    use HasFactory;
    public $table = 'quiz_records';
    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'elapsed_time',

        'correct_answers',
        'total_questions',
        'percentage',
        'passing_score',
        'status',
        'attempt_count',
        'quiz_type',
        'category',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'correct_answers' => 'integer',
        'total_questions' => 'integer',
        'percentage' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'attempt_count' => 'integer',
        'completed_at' => 'datetime',
    ];


    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function user()
    {
        return $this->belongsTo(Dasher::class, 'user_id');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_record_id');
    }

    public function rj45Attempt()
    {
        return $this->hasOne(Rj45Attempt::class, 'quiz_record_id');
    }
}
