<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'group_id', 'lecturer_id', 'series_id', 'academic_semester_id', 'location', 'venue_id', 'requires_clock_out', 'start_at', 'end_at',
        'attendance_status', 'attendance_open_at', 'late_at', 'is_cancelled', 'is_online', 'access_code',
        'actual_start_at', 'actual_end_at', 'actual_lecturer_id'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'attendance_open_at' => 'datetime',
        'late_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'requires_clock_out' => 'boolean',
        'is_cancelled' => 'boolean',
        'is_online' => 'boolean',
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

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'lecturer_schedule')
            ->withTimestamps();
    }

    public function series()
    {
        return $this->belongsTo(ScheduleSeries::class, 'series_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }

    public function academicSemester()
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    // Scopes
    public function scopeForSemester($query, $semesterId)
    {
        return $query->where('academic_semester_id', $semesterId);
    }
    public function hasAttendance()
    {
        return $this->attendanceRecords()->exists();
    }

    public function actualLecturer()
    {
        return $this->belongsTo(Lecturer::class, 'actual_lecturer_id');
    }
}
