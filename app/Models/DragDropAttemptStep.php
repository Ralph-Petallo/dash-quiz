<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DragDropAttemptStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'drag_drop_attempt_id',
        'expected_item_id',
        'sequence_number',
        'expected_position',
        'phase',
        'expected_action',
        'performed_action',
        'expected_component_key',
        'performed_component_key',
        'source_zone',
        'destination_zone',
        'is_correct',
        'first_try',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'expected_position' => 'integer',
        'is_correct' => 'boolean',
        'first_try' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DragDropAttempt::class, 'drag_drop_attempt_id');
    }

    public function expectedItem(): BelongsTo
    {
        return $this->belongsTo(DragDropItem::class, 'expected_item_id');
    }
}