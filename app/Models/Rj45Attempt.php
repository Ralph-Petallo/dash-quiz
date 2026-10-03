<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rj45Attempt extends Model
{
    use HasFactory;

    public $table = 'rj45_attempts';

    protected $fillable = [
    'rj45_assessment_id',
    'quiz_record_id',
    'quiz_id',
    'user_id',
    'standard',
    'wiring',
    'correct_pins',
    'score_percent',
    'passed',
    'duration_ms',
    'started_at',
    'submitted_at',
];

    protected $casts = [
        'wiring' => 'array',
        'correct_pins' => 'integer',
        'score_percent' => 'decimal:2',
        'passed' => 'boolean',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function assessment()
    {
        return $this->belongsTo(Rj45Assessment::class, 'rj45_assessment_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pins()
    {
        return $this->hasMany(Rj45AttemptPin::class)->orderBy('pin_number');
    }

    public function record()
    {
        return $this->belongsTo(QuizRecord::class, 'quiz_record_id');
    }
}
