<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function lecturer()
    {
        return $this->hasOne(Lecturer::class);
    }

    // ── Role constants ──
    const ROLE_SUPER_ADMIN  = 'super_admin';
    const ROLE_ADMIN        = 'admin';
    const ROLE_PRINCIPAL    = 'principal';
    const ROLE_REGISTRAR    = 'registrar';
    const ROLE_CAMPUS_CHIEF = 'campus_chief';
    const ROLE_QA_DIRECTOR  = 'qa_director';
    const ROLE_DEAN         = 'dean';
    const ROLE_HOD          = 'hod';
    const ROLE_LECTURER     = 'lecturer';
    const ROLE_STUDENT      = 'student';

    public static function allRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_PRINCIPAL,
            self::ROLE_REGISTRAR, self::ROLE_CAMPUS_CHIEF, self::ROLE_QA_DIRECTOR,
            self::ROLE_DEAN, self::ROLE_HOD, self::ROLE_LECTURER, self::ROLE_STUDENT,
        ];
    }

    public function isAdmin(): bool  { return in_array($this->role, ['admin', 'super_admin']); }
    public function isDean(): bool   { return $this->role === self::ROLE_DEAN; }
    public function isHod(): bool    { return $this->role === self::ROLE_HOD; }
    public function isQA(): bool     { return $this->role === self::ROLE_QA_DIRECTOR; }
    public function isPrincipal(): bool { return $this->role === self::ROLE_PRINCIPAL; }
    public function isRegistrar(): bool { return $this->role === self::ROLE_REGISTRAR; }
    public function isCampusChief(): bool { return $this->role === self::ROLE_CAMPUS_CHIEF; }
    public function isLecturer(): bool { return $this->role === self::ROLE_LECTURER; }
    public function isStudent(): bool  { return $this->role === self::ROLE_STUDENT; }

    // Relationship: faculty where this user is dean
    public function deanOfFaculty()
    {
        return $this->hasOne(Faculty::class, 'dean_user_id');
    }

    // Relationship: department where this user is HOD
    public function hodOfDepartment()
    {
        return $this->hasOne(Department::class, 'hod_user_id');
    }
}
