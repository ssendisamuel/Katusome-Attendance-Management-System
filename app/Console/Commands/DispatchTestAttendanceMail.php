<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;
use App\Mail\AttendanceConfirmationMail;
use Carbon\Carbon;

class DispatchTestAttendanceMail extends Command
{
    protected $signature = 'mail:test-attendance {studentId} {scheduleId}';
    protected $description = 'Send an attendance confirmation email immediately for a given student and schedule';

    public function handle(): int
    {
        $student = Student::with('user')->find($this->argument('studentId'));
        $schedule = Schedule::with('course')->find($this->argument('scheduleId'));
        if (!$student || !$schedule) {
            $this->error('Invalid student or schedule');
            return self::FAILURE;
        }
        $attendance = Attendance::firstOrCreate([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
        ], [
            'status' => 'present',
            'marked_at' => Carbon::now(),
        ]);

        Mail::mailer('smtp')->to(optional($student->user)->email)->send(new AttendanceConfirmationMail($student, $schedule, $attendance));
        $this->info('Sent attendance confirmation mail immediately via SMTP.');
        return self::SUCCESS;
    }
}