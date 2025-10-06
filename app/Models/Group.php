<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'program_id'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function scheduleSeries()
    {
        return $this->hasMany(ScheduleSeries::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}