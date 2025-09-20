<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Farm extends Model
{
    use HasFactory;

    protected $primaryKey = 'farm_id';
    
    protected $fillable = [
        'user_id',
        'farm_name',
        'latitude',
        'longitude',
        'boundary'
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    // PostgreSQL geometry handling
    protected $geometry = ['boundary'];

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

    // PostgreSQL geometry helper methods
    public function setBoundaryAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['boundary'] = DB::raw("ST_GeomFromText('POLYGON((" . implode(',', array_map(function($point) {
                return $point['lng'] . ' ' . $point['lat'];
            }, $value)) . "))', 4326)");
        }
    }

    public function getBoundaryAttribute($value)
    {
        if ($value) {
            $result = DB::select("SELECT ST_AsGeoJSON(?) as boundary", [$value]);
            return json_decode($result[0]->boundary, true);
        }
        return null;
    }
}
