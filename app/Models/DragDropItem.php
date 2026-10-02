<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DragDropItem extends Model
{
    use HasFactory;

    protected $table = 'drag_drop_items';

    protected $fillable = [
        'question_id',

        'component_key',
        'display_name',
        'item_type',
        'quantity',

        'item_text',
        'instruction',
        'item_image_path',

        'phase',
        'action',
        'target_zone',

        'correct_position',
        'required',
        'active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'correct_position' => 'integer',
        'required' => 'boolean',
        'active' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(
            Question::class,
            'question_id'
        );
    }

    public function attemptSteps()
    {
        return $this->hasMany(
            DragDropAttemptStep::class,
            'expected_item_id'
        );
    }
}