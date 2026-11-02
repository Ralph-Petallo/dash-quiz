<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public $table = 'questions';
    protected $fillable = [
        'quiz_id',
        'question_text',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function dragDropItems(): HasMany
    {
        return $this->hasMany(DragDropItem::class, 'question_id');
    }

}
