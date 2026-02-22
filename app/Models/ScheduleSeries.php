<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'course_id', 'group_id', 'lecturer_id', 'academic_semester_id',
        'location', 'venue_id', 'is_online', 'access_code', 'requires_clock_out',
        'start_date', 'end_date', 'start_time', 'end_time', 'days_of_week', 'is_recurring'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'days_of_week' => 'array',
        'is_recurring' => 'boolean',
        'is_online' => 'boolean',
        'requires_clock_out' => 'boolean',
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

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'series_id');
    }

    public function academicSemester()
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
