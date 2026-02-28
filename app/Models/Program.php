<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'department_id', 'faculty_id', 'duration_years'];

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function faculty()
    {
        return $this->belongsTo(\App\Models\Faculty::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_program')
            ->withPivot(['year_of_study', 'semester_offered', 'credit_units', 'course_type'])
            ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
