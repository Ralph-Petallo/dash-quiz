<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InputOutputAttempt extends Model
{
    use HasFactory;

    public $table = 'input_output_attempts';

    protected $fillable = [
        'user_id',
        'quiz_record_id',
        'session_uuid',
        'device_id',
        'selected_type',
        'is_correct',
        'points',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points' => 'integer',
    ];

    // device table relation
    public function device(): BelongsTo
    {
        return $this->belongsTo(InputOutput::class, 'device_id');
    }

    // user table relation
    public function user(): BelongsTo
    {
        return $this->belongsTo(Dasher::class, 'user_id');
    }

    // record table relation
    public function record(): BelongsTo
    {
        return $this->belongsTo(QuizRecord::class, 'quiz_record_id');
    }

}