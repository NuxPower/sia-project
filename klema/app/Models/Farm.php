<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    use HasFactory;

    protected $primaryKey = 'farm_id';
    
    protected $fillable = [
        'user_id',
        'farm_name',
        'latitude',
        'longitude',
        'size_hectares',
        'soil_type',
        'description',
        'boundary',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'size_hectares' => 'decimal:2',
        'boundary_geojson' => 'array',
    ];

    protected $appends = ['boundary'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function weatherData()
    {
        return $this->hasMany(WeatherData::class, 'farm_id');
    }

    public function farmPoints()
    {
        return $this->hasMany(FarmPoint::class, 'farm_id');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'farm_id');
    }

    public function getLatestWeatherAttribute()
    {
        return $this->weatherData()->latest('recorded_at')->first();
    }

    public function getActiveAlertsAttribute()
    {
        return $this->alerts()->where('resolved', false)->get();
    }

    public function setBoundaryAttribute($value): void
    {
        $geoJson = $this->normalizeBoundary($value);
        $this->attributes['boundary_geojson'] = $geoJson ? json_encode($geoJson) : null;
    }

    public function getBoundaryAttribute(): ?array
    {
        $raw = $this->attributes['boundary_geojson'] ?? null;

        if ($raw === null) {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function normalizeBoundary($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (is_array($value) && isset($value['type']) && strtolower($value['type']) === 'polygon') {
            return $value;
        }

        if (is_array($value) && isset($value['coordinates'])) {
            return [
                'type' => 'Polygon',
                'coordinates' => $value['coordinates'],
            ];
        }

        if (is_array($value) && $this->isCoordinateCollection($value)) {
            $ring = array_map(function ($point) {
                $lat = $point['lat'] ?? $point[0] ?? null;
                $lng = $point['lng'] ?? $point['lon'] ?? $point[1] ?? null;

                return [
                    (float) $lng,
                    (float) $lat,
                ];
            }, $value);

            if (count($ring) < 3) {
                return null;
            }

            if ($ring[0] !== end($ring)) {
                $ring[] = $ring[0];
            }

            return [
                'type' => 'Polygon',
                'coordinates' => [$ring],
            ];
        }

        return null;
    }

    private function isCoordinateCollection(array $value): bool
    {
        if (empty($value)) {
            return false;
        }

        foreach ($value as $point) {
            if (! is_array($point)) {
                return false;
            }

            $hasNamed = isset($point['lat'], $point['lng']) || isset($point['lat'], $point['lon']);
            $hasIndexed = array_key_exists(0, $point) && array_key_exists(1, $point);

            if (! $hasNamed && ! $hasIndexed) {
                return false;
            }
        }

        return true;
    }
}
