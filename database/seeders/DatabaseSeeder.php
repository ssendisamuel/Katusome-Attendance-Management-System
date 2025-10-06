<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Program;
use App\Models\Group;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Student;
use Database\Seeders\ScheduleSeriesSeeder;
use Database\Seeders\ScheduleSeeder;
use Database\Seeders\AttendanceSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@mubs.ac.ug'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Additional admin for project owner
        $ownerAdmin = User::firstOrCreate(
            ['email' => 'sssendi@mubs.ac.ug'],
            [
                'name' => 'Project Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Basic academic structure
        $program = Program::firstOrCreate(['code' => 'BBA'], ['name' => 'Bachelor of Business Administration']);
        $group = Group::firstOrCreate(['name' => 'Group A', 'program_id' => $program->id]);
        $course = Course::firstOrCreate(['code' => 'ACC101'], [
            'name' => 'Accounting Basics',
            'description' => 'Introductory accounting course',
            'program_id' => $program->id,
        ]);

        // Lecturer user and profile
        $lecturerUser = User::firstOrCreate(
            ['email' => 'lecturer@mubs.ac.ug'],
            [
                'name' => 'Jane Lecturer',
                'password' => Hash::make('password'),
                'role' => 'lecturer',
            ]
        );
        $lecturer = Lecturer::firstOrCreate([
            'user_id' => $lecturerUser->id,
            'email' => $lecturerUser->email,
        ], [
            'name' => $lecturerUser->name,
            'phone' => '256700000000',
        ]);

        // Student user and profile
        $studentUser = User::firstOrCreate(
            ['email' => 'student@mubs.ac.ug'],
            [
                'name' => 'John Student',
                'password' => Hash::make('password'),
                'role' => 'student',
            ]
        );
        $student = Student::firstOrCreate([
            'email' => $studentUser->email,
            'student_no' => 'S123456',
        ], [
            'user_id' => $studentUser->id,
            'name' => $studentUser->name,
            'phone' => '256711111111',
            'gender' => 'male',
            'reg_no' => 'REG2025-001',
            'program_id' => $program->id,
            'group_id' => $group->id,
            'year_of_study' => 1,
        ]);

        // Additional sample students
        for ($i = 2; $i <= 5; $i++) {
            User::firstOrCreate(
                ['email' => "student{$i}@mubs.ac.ug"],
                [
                    'name' => "Student {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'student',
                ]
            );
            $user = User::where('email', "student{$i}@mubs.ac.ug")->first();
            Student::firstOrCreate(
                ['email' => $user->email, 'student_no' => "S12345{$i}"],
                [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'phone' => '256711111111',
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'reg_no' => "REG2025-00{$i}",
                    'program_id' => $program->id,
                    'group_id' => $group->id,
                    'year_of_study' => 1,
                ]
            );
        }

        // Series, schedules and attendance seeders
        $this->call([
            ScheduleSeriesSeeder::class,
            ScheduleSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
