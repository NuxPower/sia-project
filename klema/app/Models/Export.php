<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Export extends Model
{
    use HasFactory;

    protected $primaryKey = 'export_id';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'disk'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileSizeAttribute()
    {
        $disk = $this->disk ?? 'local';
        
        try {
            if (\Storage::disk($disk)->exists($this->file_path)) {
                $bytes = \Storage::disk($disk)->size($this->file_path);
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                $bytes /= pow(1024, $pow);
                return round($bytes, 2) . ' ' . $units[$pow];
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::warning('Failed to get file size for export: ' . $e->getMessage());
        }
        
        return 'Unknown';
    }

    public function getDownloadUrlAttribute()
    {
        return route('exports.download', $this->export_id);
    }
}