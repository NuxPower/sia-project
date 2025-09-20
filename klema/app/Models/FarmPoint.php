<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmPoint extends Model
{
    use HasFactory;

    protected $primaryKey = 'point_id';

    protected $fillable = [
        'farm_id',
        'label',
        'latitude',
        'longitude',
        'point_type'
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function getPointTypeIconAttribute()
    {
        $iconMap = [
            'water_source' => 'fas fa-tint text-blue-500',
            'storage' => 'fas fa-warehouse text-gray-600',
            'equipment' => 'fas fa-tools text-orange-500',
            'crop_field' => 'fas fa-seedling text-green-500',
            'entrance' => 'fas fa-door-open text-blue-600',
            'shelter' => 'fas fa-home text-brown-500',
        ];

        return $iconMap[$this->point_type] ?? 'fas fa-map-marker-alt text-red-500';
    }
}
