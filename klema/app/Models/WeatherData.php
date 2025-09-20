<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    use HasFactory;

    protected $primaryKey = 'weather_id';
    protected $table = 'weather_data';
    public $timestamps = false;

    protected $fillable = [
        'farm_id',
        'temperature',
        'humidity',
        'rainfall',
        'wind_speed',
        'condition',
        'recorded_at'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'rainfall' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function getFormattedTemperatureAttribute()
    {
        return $this->temperature ? round($this->temperature) . '°C' : 'N/A';
    }

    public function getWeatherIconAttribute()
    {
        $condition = strtolower($this->condition ?? '');
        
        if (strpos($condition, 'rain') !== false) {
            return 'fas fa-cloud-rain text-blue-400';
        } elseif (strpos($condition, 'cloud') !== false) {
            return 'fas fa-cloud text-gray-400';
        } elseif (strpos($condition, 'clear') !== false || strpos($condition, 'sun') !== false) {
            return 'fas fa-sun text-yellow-400';
        } else {
            return 'fas fa-question text-gray-400';
        }
    }
}
