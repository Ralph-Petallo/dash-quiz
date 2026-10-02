<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // important
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Models\QuizRecord;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Dasher extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $table = 'dasher';

    protected $primaryKey = 'id';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'role',
        'profile_photo',
        'password',
        'last_activity',
        'active_status',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'active_status' => 'boolean',
    ];

    protected $hidden = [
        'active_status',
        'password',
        'remember_token',
    ];

    public function get_profile()
    {
        return $this->profile_photo
            ? asset('storage/images/profiles/' . $this->profile_photo)
            : asset('images/profiles/default.jpg'); // fallback if no image
    }

    public function quizRecords()
    {
        return $this->hasMany(QuizRecord::class, 'user_id');
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucfirst($value),
        );
    }
}
