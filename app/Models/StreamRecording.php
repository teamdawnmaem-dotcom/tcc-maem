<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamRecording extends Model
{
    protected $table = 'tbl_stream_recordings';
    protected $primaryKey = 'recording_id';
    
    protected $fillable = [
        'camera_id',
        'filename',
        'filepath',
        'start_time',
        'duration',
        'frames',
        'file_size'
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'duration' => 'integer',
        'frames' => 'integer',
        'file_size' => 'integer',
    ];
    
    /**
     * Get the camera that owns the recording
     */
    public function camera()
    {
        return $this->belongsTo(Camera::class, 'camera_id', 'camera_id');
    }
    
    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        $seconds = $this->duration;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
