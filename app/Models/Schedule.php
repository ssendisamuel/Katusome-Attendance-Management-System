<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'group_id', 'lecturer_id', 'series_id', 'location', 'start_at', 'end_at'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function series()
    {
        return $this->belongsTo(ScheduleSeries::class, 'series_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }
}