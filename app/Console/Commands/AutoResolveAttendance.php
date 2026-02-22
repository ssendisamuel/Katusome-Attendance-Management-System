<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoResolveAttendance extends Command
{
    protected $signature = 'attendance:auto-resolve';
    protected $description = 'Auto-clock out students who forgot to clock out';

    public function handle()
    {
        $now = \Carbon\Carbon::now();
        $this->info("Running Auto-Resolution at {$now}");

        // Find schedules that have ended at least 30 mins ago (buffer) and require clock out
        $schedules = \App\Models\Schedule::where('requires_clock_out', true)
            ->where('end_at', '<', $now->subMinutes(30))
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            // Find attendances that haven't clocked out
            $attendances = \App\Models\Attendance::where('schedule_id', $schedule->id)
                ->whereNull('clock_out_time')
                ->where('is_auto_clocked_out', false)
                ->whereNotIn('status', ['absent', 'cancelled', 'excused']) // Fix: Ignore final statuses
                ->with('student.user') // Eager load for email
                ->get();

            foreach ($attendances as $attendance) {
                $attendance->update([
                    'clock_out_time' => $schedule->end_at,
                    'is_auto_clocked_out' => true,
                    // User Request: Mark as "incomplete" / "partially present"
                    'status' => 'incomplete',
                ]);

                // Send Email
                if ($attendance->student && $attendance->student->user && $attendance->student->user->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($attendance->student->user->email)
                            ->send(new \App\Mail\AttendanceSummaryMail($attendance->student, $schedule, $attendance));
                    } catch (\Exception $e) {
                        $this->error("Failed to email ID {$attendance->id}: " . $e->getMessage());
                    }
                }
                $count++;
            }
        }

        $this->info("Auto-resolved {$count} attendances.");
    }
}
