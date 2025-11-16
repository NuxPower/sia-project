<?php

// app/Models/User.php
namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function farms()
    {
        return $this->hasMany(Farm::class);
    }

    public function exports()
    {
        return $this->hasMany(Export::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    /**
     * Send the email verification notification.
     * Override to queue the notification instead of sending synchronously.
     * This prevents email connection timeouts from blocking HTTP requests.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // Queue the verification email to prevent blocking HTTP requests
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Determine the default abilities for issued personal access tokens.
     *
     * @return array<int, string>
     */
    public function defaultTokenAbilities(): array
    {
        $abilities = [
            '*',
            'weather:read',
            'weather:write',
            'farms:read',
            'farms:write',
            'alerts:read',
            'alerts:write',
            'activities:read',
            'activities:write',
            'exports:read',
            'exports:write',
            'users:read',
            'users:write',
            'role:farmer',
        ];

        return array_unique($abilities);
    }

    /**
     * Issue a scoped personal access token, replacing older tokens with the same name.
     */
    public function issueToken(string $name = 'klema-api-token', ?int $expirationMinutes = null): string
    {
        $this->tokens()->where('name', $name)->delete();

        $expiresAt = $expirationMinutes ? now()->addMinutes($expirationMinutes) : null;

        return $this->createToken($name, $this->defaultTokenAbilities(), $expiresAt)->plainTextToken;
    }
}