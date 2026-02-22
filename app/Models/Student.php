<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'gender', 'student_no', 'reg_no', 'program_id', 'group_id', 'year_of_study'
    ];

    // Proxy identity to the related user to avoid drift
    public function getNameAttribute($value)
    {
        return $this->user ? $this->user->name : $value;
    }

    public function setNameAttribute($value)
    {
        // Keep legacy column for backward compatibility if column exists
        if (Schema::hasColumn($this->getTable(), 'name')) {
            $this->attributes['name'] = $value;
        }
        // Write-through to canonical source of truth
        if ($this->user) {
            $this->user->name = $value;
            $this->user->save();
        }
    }

    public function getEmailAttribute($value)
    {
        return $this->user ? $this->user->email : $value;
    }

    public function setEmailAttribute($value)
    {
        // Keep legacy column for backward compatibility if column exists
        if (Schema::hasColumn($this->getTable(), 'email')) {
            $this->attributes['email'] = $value;
        }
        // Write-through to canonical source of truth
        if ($this->user) {
            $this->user->email = $value;
            $this->user->save();
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get the student's enrollment for the active semester
     */
    public function currentEnrollment()
    {
        $activeSemester = AcademicSemester::where('is_active', true)->first();

        if (!$activeSemester) {
            return null;
        }

        return $this->enrollments()
            ->where('academic_semester_id', $activeSemester->id)
            ->first();
    }

    /**
     * Check if student is enrolled in a specific semester
     */
    public function isEnrolledInSemester($semesterId)
    {
        return $this->enrollments()
            ->where('academic_semester_id', $semesterId)
            ->exists();
    }

    /**
     * Get courses registered for retake/missed/extra
     */
    public function retakeCourses()
    {
        return $this->hasMany(StudentCourseRegistration::class);
    }

    /**
     * Get the actual course models for retakes
     */
    public function extraCourses()
    {
        return $this->belongsToMany(Course::class, 'student_course_registrations')
            ->withPivot(['id', 'academic_semester_id', 'type'])
            ->withTimestamps();
    }

    public function courseLeadership()
    {
        return $this->hasMany(CourseLeader::class);
    }
}
