<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rj45Assessment extends Model
{
    use HasFactory;

    public $table = 'rj45_assessments';

    protected $fillable = [
        'quiz_id',
        'app_name',
        'assessment_id',
        'project_title',
        'project_subtitle',
        'title',
        'description',
        'coc_label',
        'default_standard',
        'test_pin_delay_ms',
        'tray_order',
        'initial_wiring',
        'simulation_note',
        'is_active',
    ];

    protected $casts = [
        'tray_order' => 'array',
        'initial_wiring' => 'array',
        'is_active' => 'boolean',
        'test_pin_delay_ms' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function pinouts()
    {
        return $this->hasMany(Rj45Pinout::class);
    }

    public function attempts()
    {
        return $this->hasMany(Rj45Attempt::class);
    }
}
