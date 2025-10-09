<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\Mail;
use App\Mail\AttendanceConfirmationMail;
use Carbon\Carbon;

class DispatchAutoAttendanceMail extends Command
{
    protected $signature = 'mail:test-attendance:auto {--studentId=} {--scheduleId=}';
    protected $description = 'Automatically find a student and schedule and send an attendance confirmation email immediately via SMTP';

    public function handle(): int
    {
        $studentId = $this->option('studentId');
        $scheduleId = $this->option('scheduleId');

        $studentQuery = Student::with('user');
        if ($studentId) { $studentQuery->where('id', $studentId); }
        $student = $studentQuery->first();
        if (!$student) { $this->error('No student found'); return self::FAILURE; }

        $schedule = null;
        if ($scheduleId) {
            $schedule = Schedule::with('course')->find($scheduleId);
        }
        if (!$schedule) {
            $today = Carbon::today();
            $schedule = Schedule::with('course')
                ->where('group_id', $student->group_id)
                ->whereDate('start_at', $today)
                ->orderBy('start_at')
                ->first();
        }
        if (!$schedule) {
            $schedule = Schedule::with('course')
                ->where('group_id', $student->group_id)
                ->orderByDesc('start_at')
                ->first();
        }
        if (!$schedule) { $this->error('No schedule found for student group'); return self::FAILURE; }

        $attendance = Attendance::firstOrCreate([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
        ], [
            'status' => 'present',
            'marked_at' => Carbon::now(),
        ]);

        Mail::mailer('smtp')->to(optional($student->user)->email)->send(new AttendanceConfirmationMail($student, $schedule, $attendance));
        $this->info('Sent attendance confirmation mail immediately for student #'.$student->id.' schedule #'.$schedule->id);
        return self::SUCCESS;
    }
}