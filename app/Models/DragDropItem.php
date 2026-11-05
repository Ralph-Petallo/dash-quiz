<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DragDropItem extends Model
{
    use HasFactory;

    public const PREPARE = 'prepare';
    public const DISASSEMBLE = 'disassemble';
    public const ASSEMBLE = 'assemble';
    public const FINISH = 'finish';

    public const PHASES = [self::PREPARE, self::DISASSEMBLE, self::ASSEMBLE, self::FINISH];

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

    // Instructions are for the admin editor only. They never appear in learner JSON.
    protected $hidden = ['instruction'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function attemptSteps(): HasMany
    {
        return $this->hasMany(DragDropAttemptStep::class, 'expected_item_id');
    }

    /** Active, required steps that belong to the procedure. */
    public function scopeProcedure(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where('required', true)
            ->whereNotNull('component_key')
            ->whereNotNull('target_zone')
            ->whereIn('phase', self::PHASES);
    }

    public function isPreparation(): bool
    {
        return $this->phase === self::PREPARE;
    }

    /** Checklist steps are tapped, not dragged. */
    public function isChecklist(): bool
    {
        return in_array($this->phase, [self::PREPARE, self::FINISH], true);
    }

    /** Chassis parts are moved between the case and the trays. */
    public function isPart(): bool
    {
        return in_array($this->phase, [self::DISASSEMBLE, self::ASSEMBLE], true);
    }
}