<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'academic_semester_id',
        'target_group_id',
        'type',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function academicSemester()
    {
        return $this->belongsTo(AcademicSemester::class);
    }

    public function targetGroup()
    {
        return $this->belongsTo(Group::class, 'target_group_id');
    }
}
