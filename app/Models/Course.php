<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'course_program')
            ->withPivot(['year_of_study', 'semester_offered', 'credit_units', 'course_type'])
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleSeries()
    {
        return $this->hasMany(ScheduleSeries::class);
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'course_lecturer')
            ->withTimestamps();
    }
}
