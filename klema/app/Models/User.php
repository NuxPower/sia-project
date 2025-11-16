<?php

// app/Models/User.php
namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
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
        // Queue the notification - it implements ShouldQueue so it will be queued
        // If QUEUE_CONNECTION=sync, it runs immediately but we catch errors
        // If QUEUE_CONNECTION=database, it gets queued and processed by worker
        try {
            $this->notify(new QueuedVerifyEmail);
        } catch (\Exception $e) {
            // Log error but don't throw - registration should succeed even if email fails
            Log::warning('Failed to queue email verification notification', [
                'user_id' => $this->id,
                'email' => $this->email,
                'error' => $e->getMessage(),
            ]);
        }
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