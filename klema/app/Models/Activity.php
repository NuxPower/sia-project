<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type',
        'field',
        'start_date',
        'end_date',
        'weather_warning',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'in_progress' => 'blue',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function hasWeatherWarning(): bool
    {
        return !is_null($this->weather_warning);
    }
}