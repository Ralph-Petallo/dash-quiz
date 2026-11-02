<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageIdentificationAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'quiz_id',
        'token',
        'question_set',
        'expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'question_set' => 'array',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];
}
