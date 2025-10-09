<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'email', 'phone'];

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

    public function schedules()
    {
        // Legacy single-lecturer relation via foreign key
        return $this->hasMany(Schedule::class);
    }

    public function scheduleSeries()
    {
        return $this->hasMany(ScheduleSeries::class);
    }

    public function assignedSchedules()
    {
        return $this->belongsToMany(Schedule::class, 'lecturer_schedule')
            ->withTimestamps();
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_lecturer')
            ->withTimestamps();
    }
}