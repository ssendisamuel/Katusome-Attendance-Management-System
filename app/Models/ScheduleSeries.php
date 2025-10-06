<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'course_id', 'group_id', 'lecturer_id', 'location', 'start_date', 'end_date', 'start_time', 'end_time', 'days_of_week', 'is_recurring'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'days_of_week' => 'array',
        'is_recurring' => 'boolean',
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
        return $this->hasMany(Schedule::class);
    }
}