<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = Schedule::take(3)->get();
        $students = Student::take(5)->get();

        if ($schedules->isEmpty() || $students->isEmpty()) {
            return;
        }

        foreach ($schedules as $schedule) {
            foreach ($students as $index => $student) {
                $status = ['present', 'absent', 'late'][$index % 3];
                Attendance::firstOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $status,
                        'marked_at' => Carbon::parse($schedule->start_at)->addMinutes(10),
                        'lat' => null,
                        'lng' => null,
                        'selfie_path' => null,
                    ]
                );
            }
        }
    }
}