<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'dean_user_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'campus_faculty')->withTimestamps();
    }

    public function dean()
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
