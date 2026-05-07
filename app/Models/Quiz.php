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
}
