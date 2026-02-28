<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'faculty_id', 'hod_user_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_user_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
