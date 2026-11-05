<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    public $table = 'assessments';

    protected $fillable = [
        'coc_id',
        'type',
        'title',
        'icon',
        'description',
        'category',
        'difficulty',
        'meta',
        'path',
        'available',
    ];

    protected $casts = [
        'meta' => 'array',
        'available' => 'boolean',
    ];

    /**
     * Assessment belongs to a COC.
     */
    public function coc()
    {
        return $this->belongsTo(Quiz::class, 'coc_id');
    }

    public function quizRecords()
    {
        return $this->hasMany(QuizRecord::class, 'quiz_id', 'coc_id')
            ->whereColumn('quiz_records.quiz_type', '=', 'assessments.type');
    }
}
