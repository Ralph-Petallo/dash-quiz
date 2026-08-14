<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DragDrop extends Model
{
    use HasFactory;

    public $table = 'drag_drop_items';
    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'item_text',
        'item_image_path',
        'correct_position',
    ];

    
}
