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
}
