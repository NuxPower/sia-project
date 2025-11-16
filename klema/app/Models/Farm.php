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
        
        // Auto-calculate area from boundary if boundary is set
        // Only update size_hectares if it hasn't been manually set in this request
        if ($geoJson && !$this->isDirty('size_hectares')) {
            $calculatedArea = $this->calculateAreaFromBoundary($geoJson);
            if ($calculatedArea !== null) {
                $this->attributes['size_hectares'] = $calculatedArea;
            }
        }
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

    /**
     * Calculate the area of a polygon in hectares using spherical geometry.
     * Uses the spherical excess formula for accurate area calculation on Earth's surface.
     * 
     * @param array $geoJson GeoJSON polygon format
     * @return float|null Area in hectares, or null if calculation fails
     */
    public function calculateAreaFromBoundary(?array $geoJson): ?float
    {
        if (!$geoJson || !isset($geoJson['coordinates']) || !isset($geoJson['coordinates'][0])) {
            return null;
        }

        $coordinates = $geoJson['coordinates'][0];
        
        // Need at least 3 points to form a polygon
        if (count($coordinates) < 3) {
            return null;
        }

        // Earth's radius in meters (WGS84)
        $earthRadius = 6378137.0;
        
        $area = 0.0;
        $n = count($coordinates);
        
        // Ensure polygon is closed
        if ($coordinates[0] !== $coordinates[$n - 1]) {
            $coordinates[] = $coordinates[0];
            $n++;
        }
        
        // Calculate area using spherical excess formula
        // This accounts for Earth's curvature and works well for polygons of any size
        for ($i = 0; $i < $n - 1; $i++) {
            $lon1 = deg2rad($coordinates[$i][0]);
            $lat1 = deg2rad($coordinates[$i][1]);
            $lon2 = deg2rad($coordinates[$i + 1][0]);
            $lat2 = deg2rad($coordinates[$i + 1][1]);
            
            $area += ($lon2 - $lon1) * (2 + sin($lat1) + sin($lat2));
        }
        
        // Calculate absolute area in square meters
        $area = abs($area) * $earthRadius * $earthRadius / 2.0;
        
        // Convert from square meters to hectares (1 hectare = 10,000 square meters)
        $areaHectares = $area / 10000.0;
        
        return round($areaHectares, 2);
    }

    /**
     * Get the calculated area from boundary if available.
     * 
     * @return float|null Area in hectares
     */
    public function getCalculatedAreaAttribute(): ?float
    {
        $boundary = $this->getBoundaryAttribute();
        return $this->calculateAreaFromBoundary($boundary);
    }
}
