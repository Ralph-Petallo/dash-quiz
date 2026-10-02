<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rj45AttemptPin extends Model
{
    use HasFactory;

    protected $fillable = [
        'rj45_attempt_id',
        'pin_number',
        'actual_wire',
        'expected_wire',
        'is_correct',
    ];

    protected $casts = [
        'pin_number' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(Rj45Attempt::class, 'quiz_record_id');
    }
}
