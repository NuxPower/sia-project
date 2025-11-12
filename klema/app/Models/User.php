<?php

// app/Models/User.php
namespace App\Models;

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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    /**
     * Determine the default abilities for issued personal access tokens.
     *
     * @return array<int, string>
     */
    public function defaultTokenAbilities(): array
    {
        if ($this->isAdmin()) {
            return ['*', 'role:admin', 'token:refresh', 'tokens:manage'];
        }

        $abilities = [
            'weather:read',
            'weather:write',
            'farms:read',
            'farms:write',
            'alerts:read',
            'alerts:write',
            'activities:read',
            'activities:write',
            'role:farmer',
            'token:refresh',
            'tokens:manage',
        ];

        return array_values(array_unique($abilities));
    }

    /**
     * Issue a scoped personal access token, replacing older tokens with the same name.
     *
     * @param  array<int, string>|null  $abilities
     */
    public function issueToken(string $name = 'klema-api-token', ?int $expirationMinutes = null, ?array $abilities = null): string
    {
        $this->tokens()->where('name', $name)->delete();

        $expiresAt = $expirationMinutes ? now()->addMinutes($expirationMinutes) : null;
        $resolvedAbilities = $abilities ?? $this->defaultTokenAbilities();

        return $this->createToken($name, $resolvedAbilities, $expiresAt)->plainTextToken;
    }
}