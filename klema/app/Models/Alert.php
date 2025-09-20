<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $primaryKey = 'alert_id';
    public $timestamps = false;

    protected $fillable = [
        'farm_id',
        'alert_type',
        'message',
        'issued_at',
        'resolved'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'resolved' => 'boolean',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function getAlertTypeColorAttribute()
    {
        $colorMap = [
            'weather' => 'bg-yellow-500',
            'pest' => 'bg-red-500',
            'irrigation' => 'bg-blue-500',
            'harvest' => 'bg-green-500',
            'maintenance' => 'bg-purple-500',
        ];

        return $colorMap[$this->alert_type] ?? 'bg-gray-500';
    }

    public function getAlertTypeIconAttribute()
    {
        $iconMap = [
            'weather' => 'fas fa-cloud-rain',
            'pest' => 'fas fa-bug',
            'irrigation' => 'fas fa-tint',
            'harvest' => 'fas fa-cut',
            'maintenance' => 'fas fa-wrench',
        ];

        return $iconMap[$this->alert_type] ?? 'fas fa-exclamation-triangle';
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }
}
