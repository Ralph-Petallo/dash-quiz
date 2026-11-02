<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyDasherEmail;

class Dasher extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, Notifiable, HasFactory, MustVerifyEmailTrait;

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

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyDasherEmail);
    }
}
