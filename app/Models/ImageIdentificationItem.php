<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageIdentificationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'item_name',
        'image_path',
        'description',
        'sort_order',
    ];
}