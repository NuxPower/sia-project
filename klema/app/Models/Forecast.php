<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Forecast extends Model
{
    use HasFactory;

    protected $primaryKey = 'forecast_id';
    protected $table = 'forecasts';
    public $timestamps = false;

    protected $fillable = [
        'farm_id',
        'location_name',
        'latitude',
        'longitude',
        'forecast_date',
        'temp_max',
        'temp_min',
        'condition',
        'condition_icon',
        'description',
        'precip_probability',
        'sunrise',
        'sunset',
        'timezone_offset',
        'hourly_data',
        'cached_at',
        'expires_at',
    ];

    protected $casts = [
        'temp_max' => 'decimal:2',
        'temp_min' => 'decimal:2',
        'precip_probability' => 'decimal:2',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'forecast_date' => 'date',
        'hourly_data' => 'array',
        'sunrise' => 'integer',
        'sunset' => 'integer',
        'timezone_offset' => 'integer',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    /**
     * Scope to get only non-expired forecasts.
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get forecasts for a specific location.
     */
    public function scopeForLocation($query, $locationName)
    {
        return $query->where('location_name', strtolower(trim($locationName)));
    }

    /**
     * Scope to get forecasts for coordinates (within tolerance).
     */
    public function scopeForCoordinates($query, $lat, $lon, $tolerance = 0.01)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance]);
    }

    /**
     * Scope to get forecasts for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('forecast_date', [$startDate, $endDate]);
    }
}

