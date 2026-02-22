<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoClockOutJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $gracePeriodMinutes = 30;
        $now = Carbon::now();

        // Find ALL schedules that ended at least 30 minutes ago
        // to Ensure they are marked as closed.
        $schedules = Schedule::where('end_at', '<=', $now->subMinutes($gracePeriodMinutes))
            ->where('attendance_status', '!=', 'closed')
            ->get();

        foreach ($schedules as $schedule) {
            // Only perform Auto-Clock-Out logic if REQUIRED
            if ($schedule->requires_clock_out) {
                $attendancesToUpdate = Attendance::where('schedule_id', $schedule->id)
                    ->whereNull('clock_out_time')
                    ->where('status', '!=', 'absent')
                    ->get();

                foreach ($attendancesToUpdate as $attendance) {
                    $attendance->update([
                        'clock_out_time' => $schedule->end_at, // Set to class end time
                        'is_auto_clocked_out' => true,
                    ]);

                    // Send Notification
                    $student = $attendance->student;
                    $user = $student->user;

                    if ($user && $user->email) {
                        try {
                            Mail::raw("You were automatically clocked out of {$schedule->course->name} because you forgot to clock out.", function ($message) use ($user) {
                                $message->to($user->email)
                                    ->subject('Auto-Clock Out Notice');
                            });
                        } catch (\Exception $e) {
                            Log::error("Failed to send auto-clockout email for attendance {$attendance->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            // Auto-Close the schedule (for ALL past schedules)
            $schedule->update(['attendance_status' => 'closed']);
        }
    }
}
