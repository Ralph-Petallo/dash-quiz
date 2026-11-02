<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DragDropAttempt extends Model
{
    use HasFactory;

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
        'expires_at',
    ];

    /**
     * Laravel 10 model casts.
     */
    protected $casts = [
        'completed_steps' => 'integer',
        'correct_steps' => 'integer',
        'total_steps' => 'integer',
        'mistake_count' => 'integer',

        'percentage' => 'decimal:2',

        'passed' => 'boolean',

        'elapsed_time' => 'integer',

        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function quizRecord(): BelongsTo
    {
        return $this->belongsTo(QuizRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            Dasher::class,
            'user_id'
        );
    }

    public function steps(): HasMany
    {
        return $this->hasMany(
            DragDropAttemptStep::class
        )->orderBy('sequence_number');
    }
}