<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'password',
        'otp',
        'otp_expires_at',
        'device_token',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'otp',
        'remember_token',
        'device_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate and save a new secure device token for automatic returning authentication.
     */
    public function createDeviceToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        $this->update([
            'device_token' => hash('sha256', $token)
        ]);
        return $token;
    }

    /**
     * Revoke device token on logout.
     */
    public function revokeDeviceToken(): void
    {
        $this->update([
            'device_token' => null
        ]);
    }
}
