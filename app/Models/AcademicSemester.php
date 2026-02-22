<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSemester extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'semester',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function scheduleSeries()
    {
        return $this->hasMany(ScheduleSeries::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getDisplayNameAttribute()
    {
        return "{$this->year} {$this->semester}";
    }

    /**
     * Activate this semester and deactivate all others
     */
    public function activate()
    {
        // Deactivate all semesters
        static::query()->update(['is_active' => false]);

        // Activate this semester
        $this->is_active = true;
        $this->save();
    }
}
