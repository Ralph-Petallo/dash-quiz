<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DragDropItem extends Model
{
    use HasFactory;

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

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function attemptSteps(): HasMany
    {
        return $this->hasMany(
            DragDropAttemptStep::class,
            'expected_item_id'
        );
    }
}