<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rj45Pinout extends Model
{
    use HasFactory;

    protected $fillable = [
        'rj45_assessment_id',
        'standard',
        'pin_number',
        'wire_code',
        'wire_name',
        'short_label',
        'color_hex',
        'striped',
    ];

    protected $casts = [
        'pin_number' => 'integer',
        'striped' => 'boolean',
    ];

    public function assessment()
    {
        return $this->belongsTo(Rj45Assessment::class, 'rj45_assessment_id');
    }
}
