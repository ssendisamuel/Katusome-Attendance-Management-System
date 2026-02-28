<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\CourseLeader;
use Illuminate\Support\Str;
use App\Models\Venue;
use App\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use App\Models\CourseLeaderLog;

class CourseLeaderController extends Controller
{
    /**
     * Get the student's assigned cohorts
     */
    private function getCohorts()
    {
        $student = auth()->user()->student;
        if (!$student) return collect();

        return CourseLeader::where('student_id', $student->id)->get();
    }

    /**
     * Check if the student has permission to manage a specific schedule
     */
    private function canManageSchedule(Schedule $schedule)
    {
        $cohorts = $this->getCohorts();
        // A schedule belongs to a program, year, and group.
        // We check if the schedule's group_id matches, and optionally program_id
        // For simplicity based on DB design, matching group_id is typically enough if groups are unique per cohort
        // But let's check group_id and program_id (via course)

        $scheduleProgramIds = DB::table('course_program')
            ->where('course_id', $schedule->course_id)
            ->pluck('program_id')
            ->toArray();

        foreach ($cohorts as $cohort) {
            if ($cohort->group_id == $schedule->group_id && in_array($cohort->program_id, $scheduleProgramIds)) {
                return true;
            }
        }
        return false;
    }

    public function index(Request $request)
    {
        $cohorts = $this->getCohorts();
        if ($cohorts->isEmpty()) {
            abort(403, 'You are not assigned as a Course Leader.');
        }

        $group_ids = $cohorts->pluck('group_id')->toArray();
        $program_ids = $cohorts->pluck('program_id')->toArray();

        // Get schedules matching the student's cohorts for active semester
        $activeSemester = \App\Models\AcademicSemester::where('is_active', true)->first();

        // Base query for all schedules (both past and upcoming)
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['course.lecturers', 'group', 'lecturer', 'actualLecturer', 'venue'];
        if ($hasPivot) { $withRels[] = 'lecturers'; }

        $baseQuery = Schedule::with($withRels)
            ->whereIn('group_id', $group_ids)
            ->where('academic_semester_id', $activeSemester?->id)
            ->whereHas('course.programs', function($q) use ($program_ids) {
                $q->whereIn('programs.id', $program_ids);
            });
        $allUpcoming = (clone $baseQuery)
            ->whereDate('start_at', '>=', now()->toDateString())
            ->orderBy('start_at')
            ->get();

        $pastSchedules = (clone $baseQuery)
            ->whereDate('start_at', '<', now()->toDateString())
            ->orderByDesc('start_at')
            ->take(20) // Limit past schedules to prevent massive lists
            ->get();

        $today = now()->startOfDay();
        $endOfWeek = now()->endOfWeek();

        $todaySchedules = $allUpcoming->filter(function ($schedule) use ($today) {
            return $schedule->start_at->isSameDay($today);
        });

        $thisWeekSchedules = $allUpcoming->filter(function ($schedule) use ($today, $endOfWeek) {
            return $schedule->start_at->isAfter($today->endOfDay()) && $schedule->start_at->isBefore($endOfWeek);
        });

        $upcomingSchedules = $allUpcoming->filter(function ($schedule) use ($endOfWeek) {
            return $schedule->start_at->isAfter($endOfWeek);
        });
        // Get lecturers assigned to these courses for the 'actuals' dropdown
        $course_ids = $allUpcoming->pluck('course_id')->unique();
        $lecturers = Lecturer::whereHas('courses', function($q) use ($course_ids) {
            $q->whereIn('courses.id', $course_ids);
        })->get();

        return view('student.course-leader.dashboard', compact('todaySchedules', 'thisWeekSchedules', 'upcomingSchedules', 'pastSchedules', 'lecturers', 'cohorts'));
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        if (!$this->canManageSchedule($schedule)) abort(403);

        $request->validate([
            'status' => 'required|in:cancelled' // Currently only allowing them to cancel/not taught
        ]);

        if ($request->status === 'cancelled') {
            $schedule->update([
                'is_cancelled' => true,
                'attendance_status' => 'closed'
            ]);

            CourseLeaderLog::create([
                'student_id' => auth()->user()->student->id,
                'schedule_id' => $schedule->id,
                'action' => 'status_updated',
                'details' => ['status' => 'cancelled']
            ]);

            return back()->with('success', 'Class marked as not taught / cancelled.');
        }

        return back()->with('error', 'Invalid status update.');
    }

    public function updateVenue(Request $request, Schedule $schedule)
    {
        if (!$this->canManageSchedule($schedule)) abort(403);

        $request->validate([
            'venue_id' => 'required|exists:venues,id'
        ]);

        $venue = Venue::find($request->venue_id);
        $schedule->update([
            'venue_id' => $venue->id,
            'location' => $venue->fullName()
        ]);

        CourseLeaderLog::create([
            'student_id' => auth()->user()->student->id,
            'schedule_id' => $schedule->id,
            'action' => 'venue_updated',
            'details' => ['venue_id' => $venue->id, 'location' => $venue->fullName()]
        ]);

        return back()->with('success', 'Venue updated successfully.');
    }

    public function updateMode(Request $request, Schedule $schedule)
    {
        if (!$this->canManageSchedule($schedule)) abort(403);

        $request->validate([
            'is_online' => 'required|boolean'
        ]);

        $is_online = $request->boolean('is_online');
        $data = ['is_online' => $is_online];

        if ($is_online && !$schedule->access_code) {
            $data['access_code'] = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        } elseif (!$is_online) {
            $data['access_code'] = null; // Clear if switching back to physical
        }

        $schedule->update($data);

        CourseLeaderLog::create([
            'student_id' => auth()->user()->student->id,
            'schedule_id' => $schedule->id,
            'action' => 'mode_updated',
            'details' => $data
        ]);

        $msg = $is_online ? 'Class switched to Online. Access code generated.' : 'Class switched to Physical.';
        return back()->with('success', $msg);
    }

    public function logActuals(Request $request, Schedule $schedule)
    {
        if (!$this->canManageSchedule($schedule)) abort(403);

        $request->validate([
            'actual_lecturer_id' => 'required|exists:lecturers,id',
            'actual_start_date' => 'required|date',
            'actual_start_time' => 'required|date_format:H:i',
            'actual_end_date' => 'required|date',
            'actual_end_time' => 'required|date_format:H:i',
        ]);

        $startAt = \Carbon\Carbon::parse($request->actual_start_date . ' ' . $request->actual_start_time);
        $endAt = \Carbon\Carbon::parse($request->actual_end_date . ' ' . $request->actual_end_time);

        if ($endAt->lte($startAt)) {
            return back()->withErrors(['actual_end_time' => 'End time must be after start time.'])->withInput();
        }

        $schedule->update([
            'actual_lecturer_id' => $request->actual_lecturer_id,
            'actual_start_at' => $startAt,
            'actual_end_at' => $endAt,
        ]);

        CourseLeaderLog::create([
            'student_id' => auth()->user()->student->id,
            'schedule_id' => $schedule->id,
            'action' => 'actuals_logged',
            'details' => [
                'actual_lecturer_id' => $request->actual_lecturer_id,
                'actual_start_at' => $startAt->toDateTimeString(),
                'actual_end_at' => $endAt->toDateTimeString(),
            ]
        ]);

        return back()->with('success', 'Actual class details logged successfully.');
    }
}
