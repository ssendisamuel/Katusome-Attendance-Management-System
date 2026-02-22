<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MarkAbsentAttendance extends Command
{
    protected $signature = 'attendance:mark-absent {--days=7 : Number of days to look back}';
    protected $description = 'Mark students as absent for past schedules if no attendance record exists';

    public function handle()
    {
        $bufferMinutes = 45; // Wait 45 mins after class ends to be sure
        $now = \Carbon\Carbon::now();

        // Find schedules that ended at least bufferMinutes ago and are not cancelled
        // And maybe limit to recent past (e.g., last 24 hours) to avoid huge backfill on each run
        // Or better: chunk check.
        $this->info("Checking for missed classes...");

        // Get schedules that ended before (now - buffer)
        // Adjust lookback window as needed. For now, look back 7 days to catch up, then it will run regularly.
        $days = (int) $this->option('days');
        $lookback = $now->copy()->subDays($days);
        $threshold = $now->copy()->subMinutes($bufferMinutes);

        $schedules = \App\Models\Schedule::where('end_at', '<', $threshold)
            ->where('end_at', '>', $lookback) // Optimization
            ->where('attendance_status', '!=', 'cancelled') // Exclude cancelled via status
            ->where('is_cancelled', false) // Exclude cancelled via flag
            ->with(['group.students', 'attendanceRecords']) // Eager load
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            $group = $schedule->group;
            if (!$group) continue;

            $existingStudentIds = $schedule->attendanceRecords->pluck('student_id')->toArray();

            // Filter eligible students (e.g., active ones)
            // Assuming all students in group should attend
            foreach ($group->students as $student) {
                if (in_array($student->id, $existingStudentIds)) {
                    continue; // Already has record (present, late, or already marked absent)
                }

                // FIX: Check if student is actually enrolled in this semester
                // We use the schedule's semester to find the relevant enrollment
                $enrollment = \App\Models\StudentEnrollment::where('student_id', $student->id)
                    ->where('academic_semester_id', $schedule->academic_semester_id)
                    ->first();

                if (!$enrollment) {
                    continue; // Student not enrolled in this semester (e.g. drop-out or not registered)
                }

                // FIX: Check if Course is valid for this student's Year of Study
                // OR if they have a specific retake registration for it
                $isCourseValid = false;

                // 1. Check Standard Curriculum Match
                // Does the course belong to student's Program & Year?
                // We need to check if the course is offered in the student's current year (from enrollment)
                // And ideally, check if it's offered in this semester (but 'Both' or matching semester name is tricky text logic)
                // Generally, if it's their year and program, they should be attending.
                $matchesCurriculum = \Illuminate\Support\Facades\DB::table('course_program')
                    ->where('course_id', $schedule->course_id)
                    ->where('program_id', $enrollment->program_id)
                    ->where('year_of_study', $enrollment->year_of_study);

                // Add Semester Check
                $semester = \App\Models\AcademicSemester::find($schedule->academic_semester_id);
                if ($semester) {
                    $semesterNumber = null;
                    if (preg_match('/(\d+)/', $semester->semester, $matches)) {
                        $semesterNumber = $matches[1];
                    }
                    $validSemesterValues = array_filter([$semester->semester, $semesterNumber]);

                    $matchesCurriculum->where(function ($q) use ($validSemesterValues) {
                        $q->whereIn('semester_offered', $validSemesterValues)
                          ->orWhere('semester_offered', 'Both');
                    });
                }

                $matchesCurriculum = $matchesCurriculum->exists();

                if ($matchesCurriculum) {
                    $isCourseValid = true;
                } else {
                    // 2. Check Retake/Extra Registration
                    $isRetake = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
                        ->where('course_id', $schedule->course_id)
                        ->where('academic_semester_id', $schedule->academic_semester_id)
                        ->exists();

                    if ($isRetake) {
                        $isCourseValid = true;
                    }
                }

                if (!$isCourseValid) {
                    continue; // Course not for this student
                }

                // Create Absent Record
                \App\Models\Attendance::create([
                    'schedule_id' => $schedule->id,
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'marked_at' => $schedule->start_at, // Mark as of start time
                    'ip_address' => '127.0.0.1', // System
                    'user_agent' => 'System Auto-Mark',
                    'platform' => 'system',
                ]);
                $count++;
            }
        }

        $this->info("Marked {$count} students as absent.");
    }
}
