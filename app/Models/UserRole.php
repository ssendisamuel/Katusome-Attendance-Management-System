<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'campus_id',
        'faculty_id',
        'department_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get display name for the role with context
     */
    public function getDisplayNameAttribute(): string
    {
        $roleName = ucfirst(str_replace('_', ' ', $this->role));

        if ($this->department) {
            return "{$roleName} - {$this->department->name}";
        }

        if ($this->faculty) {
            return "{$roleName} - {$this->faculty->name}";
        }

        if ($this->campus) {
            return "{$roleName} - {$this->campus->name}";
        }

        return $roleName;
    }
}
