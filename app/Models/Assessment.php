<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Assessment extends Model
{
    use HasFactory;

    protected $table = 'assessments';

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
        'available' => 'boolean',
    ];

    /**
     * Assessment belongs to a COC.
     */
    public function coc()
    {
        return $this->belongsTo(Quiz::class);
    }
}