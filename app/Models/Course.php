<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'program_id'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleSeries()
    {
        return $this->hasMany(ScheduleSeries::class);
    }
}