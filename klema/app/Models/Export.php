<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    protected $primaryKey = 'export_id';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileSizeAttribute()
    {
        $filePath = storage_path('app/' . $this->file_path);
        if (file_exists($filePath)) {
            $bytes = filesize($filePath);
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, 2) . ' ' . $units[$pow];
        }
        return 'Unknown';
    }

    public function getDownloadUrlAttribute()
    {
        return route('exports.download', $this->export_id);
    }
}