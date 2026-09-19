<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageIdentificationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'instructions',
    ];

    /**
     * The quiz this image identification question belongs to.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * The image identification items in this question.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImageIdentificationItem::class, 'question_id')
            ->orderBy('sort_order');
    }
}