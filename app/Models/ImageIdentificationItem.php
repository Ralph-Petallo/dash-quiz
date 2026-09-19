<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageIdentificationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'item_name',
        'image_path',
        'description',
        'sort_order',
    ];

    /**
     * The image identification question this item belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(
            ImageIdentificationQuestion::class,
            'question_id'
        );
    }
}