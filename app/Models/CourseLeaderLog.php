<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLeaderLog extends Model
{
    protected $fillable = [
        'student_id',
        'schedule_id',
        'action',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
