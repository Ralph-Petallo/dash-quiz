<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InputOutput extends Model
{
    use HasFactory;

    public $table = 'input_output_devices';

    protected $fillable = [
        'name',
        'type',
        'image_key',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];


    // device attempts relation
    public function attempts(): HasMany
    {
        return $this->hasMany(InputOutputAttempt::class, 'device_id');
    }
    // device active attempts relation
    public function activeAttempts(): HasMany
    {
        return $this->hasMany(InputOutputAttempt::class, 'device_id')
            ->where('is_correct', true);
    }

}