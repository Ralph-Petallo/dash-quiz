<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    public $table = 'quizzes';

    protected $fillable = [
        'title',
        'description',
        'quiz_type',
        'coc_number',
        'difficulty',
        'category',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function records()
    {
        return $this->hasMany(QuizRecord::class, 'quiz_id');
    }

    public function attemptsCount()
    {
        return $this->records()->count();
    }

    public function rj45Assessment()
    {
        return $this->hasOne(Rj45Assessment::class);
    }
    
    public function quizRecords()
    {
        return $this->hasMany(QuizRecord::class, 'dasher_id');
    }

    public function hardwareProcedureAttempts()
    {
        return $this->hasMany(DragDropAttempt::class, 'quiz_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'coc_id')
            ->orderBy('id');
    }
}
