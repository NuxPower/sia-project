<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'settings',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings structure.
     */
    public static function defaultSettings(): array
    {
        return [
            'locationType' => 'custom',
            'defaultLocation' => '',
            'selectedFarmId' => '',
            'temperatureUnit' => 'celsius',
            'windSpeedUnit' => 'ms',
            'timeFormat' => '24h',
            'saveSearchHistory' => true,
            'anonymousUsageData' => false,
        ];
    }

    /**
     * Get settings with defaults merged.
     */
    public function getSettingsAttribute($value): array
    {
        // If value is already an array (from cast), use it directly
        if (is_array($value)) {
            return array_merge(self::defaultSettings(), $value);
        }
        
        // Otherwise decode JSON
        $settings = $value ? json_decode($value, true) : [];
        return array_merge(self::defaultSettings(), $settings ?? []);
    }

    /**
     * Set settings attribute - ensure it's stored as JSON.
     */
    public function setSettingsAttribute($value): void
    {
        $this->attributes['settings'] = is_string($value) ? $value : json_encode($value ?? []);
    }
}
