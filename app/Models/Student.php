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
}