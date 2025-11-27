<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RecognitionLog extends Model
{
    protected $table = 'tbl_recognition_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'recognition_time',
        'camera_name',
        'room_name',
        'building_no',
        'faculty_name',
        'status',
        'distance',
        'faculty_id',
        'camera_id',
        'teaching_load_id',
    ];

    protected $casts = [
        'recognition_time' => 'datetime',
        'distance' => 'decimal:6',
    ];

    /**
     * Get recognition_time as Asia/Manila timezone
     * The time is stored in the database as "YYYY-MM-DD HH:MM:SS" in Asia/Manila timezone.
     * When Laravel casts it to datetime, it may interpret it as UTC if app timezone is UTC.
     * This accessor ensures it's always treated as Asia/Manila.
     */
    public function getRecognitionTimeAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Get the raw value from database (before casting)
        $rawValue = $this->attributes['recognition_time'] ?? null;
        
        if (!$rawValue) {
            return null;
        }

        // Parse the raw database value as Asia/Manila timezone
        // The value is stored as "YYYY-MM-DD HH:MM:SS" in Asia/Manila timezone
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $rawValue, 'Asia/Manila')
                ->setTimezone('Asia/Manila');
        } catch (\Exception $e) {
            // Fallback: if already a Carbon instance, convert to Asia/Manila
            if ($value instanceof Carbon) {
                // If it was interpreted as UTC, we need to add 8 hours to get back to Manila time
                if ($value->timezone && $value->timezone->getName() === 'UTC') {
                    // The UTC time is actually 8 hours behind Manila time
                    // So we add 8 hours to convert back
                    return $value->copy()->addHours(8)->setTimezone('Asia/Manila');
                }
                return $value->setTimezone('Asia/Manila');
            }
            // Last resort: try to parse as string
            return Carbon::parse($rawValue, 'Asia/Manila')->setTimezone('Asia/Manila');
        }
    }

    /**
     * Convert the model instance to an array.
     * Ensures recognition_time is returned as Asia/Manila timezone string.
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Ensure recognition_time is formatted as Asia/Manila timezone string
        if (isset($array['recognition_time']) && $array['recognition_time']) {
            $rawValue = $this->attributes['recognition_time'] ?? null;
            if ($rawValue) {
                try {
                    $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $rawValue, 'Asia/Manila');
                    $array['recognition_time'] = $carbon->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Fallback to original value
                }
            }
        }
        
        return $array;
    }
}
