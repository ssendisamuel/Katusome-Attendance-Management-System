<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id', 'student_id', 'status', 'marked_at',
        'clock_in_time', 'clock_out_time', 'is_auto_clocked_out',
        'lat', 'lng', 'clock_out_lat', 'clock_out_lng',
        'accuracy', 'distance_meters', 'selfie_path', 'clock_out_selfie_path',
        'ip_address', 'user_agent', 'platform'
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'is_auto_clocked_out' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
