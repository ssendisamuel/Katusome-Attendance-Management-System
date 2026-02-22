<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Course;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    /**
     * Get full dashboard data (Stats, Charts, Schedule).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json([
                'enrollment' => null,
                'schedules' => [],
                'metrics' => [],
                'message' => 'Not enrolled in current semester.'
            ]);
        }

        $semesterId = $enrollment->academic_semester_id;

        // 1. Get schedules for the student's CURRENT group
        $groupSchedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereHas('course.programs', function ($q) use ($enrollment) {
                $q->where('programs.id', $enrollment->program_id)
                  ->where('course_program.year_of_study', $enrollment->year_of_study);
            })
            ->get();

        // 1b. Get schedules for RETAKE/EXTRA courses
        $retakeRegistrations = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->withPivot('target_group_id')
             ->get();

        $retakeSchedules = collect([]);
        foreach ($retakeRegistrations as $registration) {
             $query = Schedule::with(['course', 'lecturer'])
                 ->where('course_id', $registration->id)
                 ->where('academic_semester_id', $semesterId);
             if ($registration->pivot->target_group_id) {
                 $query->where('group_id', $registration->pivot->target_group_id);
             }
             $retakeSchedules = $retakeSchedules->merge($query->get());
        }

        // 2. Get schedules actually attended
        $attendedScheduleIds = Attendance::where('student_id', $student->id)
            ->whereHas('schedule', function ($q) use ($semesterId) {
                $q->where('academic_semester_id', $semesterId);
            })
            ->pluck('schedule_id');

        $attendedSchedules = Schedule::with(['course', 'lecturer'])
            ->whereIn('id', $attendedScheduleIds)
            ->get();

        // 3. Merge all
        $allSchedules = $groupSchedules->merge($attendedSchedules)->merge($retakeSchedules)->unique('id')->sortBy('start_at');

        // Today's Schedules
        $today = Carbon::today();
        $schedulesToday = $allSchedules->filter(function ($s) use ($today) {
            return $s->start_at->isSameDay($today);
        })->values();

        // Load details for today's response
        $enrollment->load(['program:id,name,code', 'academicSemester:id,semester,year', 'group:id,name']);

        $attendanceByScheduleToday = Attendance::whereIn('schedule_id', $schedulesToday->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        // Metrics Calculations
        $presentToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $lateToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();

        // Course Progress & History
        $groupedByCourse = $allSchedules->groupBy('course_id');
        $courseMetrics = [];
        $totalMinutesAll = 0;

        $allAttendance = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get();

        foreach ($groupedByCourse as $courseId => $courseSchedules) {
            $courseName = $courseSchedules->first()->course->name ?? '—';
            $validSchedules = $courseSchedules->where('is_cancelled', false);
            $totalSessions = $validSchedules->count();
            $cancelledCount = $courseSchedules->where('is_cancelled', true)->count();

            $attendedCount = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->count();

            // Time Logic
            $courseMinutes = 0;
            $courseAttendance = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'));

            foreach ($courseAttendance as $att) {
                if ($att->clock_out_time) {
                    $courseMinutes += $att->marked_at->diffInMinutes($att->clock_out_time);
                } elseif ($att->schedule->end_at && $att->marked_at) {
                     $endTime = now()->lt($att->schedule->end_at) ? now() : $att->schedule->end_at;
                     $courseMinutes += $att->marked_at->diffInMinutes($endTime);
                } elseif ($att->schedule->start_at && $att->schedule->end_at) {
                    $courseMinutes += $att->schedule->start_at->diffInMinutes($att->schedule->end_at);
                }
            }
            $totalMinutesAll += $courseMinutes;
            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;

            // Missed
            $attendedIds = $courseAttendance->whereIn('status', ['present', 'late'])->pluck('schedule_id');
            $now = now();
            $missedCount = $validSchedules->filter(function($s) use ($now, $attendedIds) {
                 $isPast = $s->end_at ? $s->end_at->lt($now) : ($s->start_at ? $s->start_at->lt($now) : false);
                 return $isPast && !$attendedIds->contains($s->id);
            })->count();

            $courseMetrics[] = [
                'name' => $courseName,
                'progress' => $percent,
                'attended' => $attendedCount,
                'taught' => $totalSessions,
                'missed' => $missedCount,
                'cancelled' => $cancelledCount,
            ];
        }

        // Weekly Activity
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weeklyAttendances = $allAttendance->whereBetween('marked_at', [$weekStart, $weekEnd])
            ->whereIn('status', ['present', 'late'])
            ->sortByDesc('marked_at'); // Newest first

        $weeklyAttended = $weeklyAttendances->map(function ($att) {
            return [
                'name' => optional($att->schedule->course)->name ?? '—',
                'date' => $att->marked_at ? $att->marked_at->format('D, M j') : '—',
                'time' => $att->marked_at ? $att->marked_at->format('h:i A') : '—',
            ];
        })->values()->all();

        // Monthly Summary
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $attendedMonth = $allAttendance->whereBetween('marked_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['present', 'late'])
            ->count();

        $schedulesInMonth = $allSchedules->filter(function($s) use ($monthStart) {
             return $s->start_at >= $monthStart && $s->end_at < now() && !$s->is_cancelled;
        });
        $attendedScheduleIdsMonth = $allAttendance->whereBetween('marked_at', [$monthStart, now()])
             ->whereIn('status', ['present', 'late'])
             ->pluck('schedule_id');

        $missedMonth = $schedulesInMonth->whereNotIn('id', $attendedScheduleIdsMonth)->count();

        $upcomingMonth = $allSchedules->filter(function($s) use ($monthEnd) {
             return $s->end_at > now() && $s->end_at <= $monthEnd && !$s->is_cancelled;
        })->count();

        $metrics = [
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'timeSpentTotalHours' => (int) round($totalMinutesAll / 60),
            'attendanceTrack' => $courseMetrics,
            'weeklyAttended' => $weeklyAttended,
            'weeklyLabel' => $weekStart->format('M j') . ' – ' . $weekEnd->format('M j'),
            'monthlySummary' => [
                'attended' => $attendedMonth,
                'missed' => $missedMonth,
                'upcoming' => $upcomingMonth,
            ],
        ];

        return response()->json([
            'enrollment' => $enrollment,
            'schedules' => $schedulesToday,
            'attendance' => $attendanceByScheduleToday,
            'metrics' => $metrics,
            'location_settings' => \App\Models\LocationSetting::current(),
        ]);
    }

    /**
     * Get list of courses for the student's current enrollment.
     */
    public function courses(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json([
                'courses' => [],
                'message' => 'Not enrolled in current semester.'
            ]);
        }

        $activeSemester = $enrollment->academicSemester;

        // Extract semester number via regex if possible (e.g. "Semester 2" -> "2")
        $semesterNumber = null;
        if (preg_match('/(\d+)/', $activeSemester->semester, $matches)) {
             $semesterNumber = $matches[1];
        }
        $validSemesterValues = array_filter([$activeSemester->semester, $semesterNumber]);

        // 1. Standard Program Courses
        $courses = Course::whereHas('programs', function ($q) use ($enrollment, $validSemesterValues) {
            $q->where('programs.id', $enrollment->program_id)
              ->where('course_program.year_of_study', $enrollment->year_of_study)
              ->whereIn('course_program.semester_offered', $validSemesterValues);
        })
        ->with(['lecturers.user:id,name', 'programs' => function($q) use ($enrollment) {
             $q->where('programs.id', $enrollment->program_id);
        }])
        ->get();

        // 2. Extra/Retake Courses
        $extraCourses = $student->extraCourses()
             ->wherePivot('academic_semester_id', $activeSemester->id)
             ->withPivot(['type'])
             ->with('lecturers.user:id,name')
             ->get();

        // Merge and Format
        $allCourses = $courses->concat($extraCourses)->unique('id')->values();

        $data = $allCourses->map(function ($course) {
            // Determine credit units
            $cu = $course->credit_units ?? 3;
            if ($course->pivot && isset($course->pivot->credit_units)) {
                $cu = $course->pivot->credit_units; // From program pivot
            }

            // Determine Type
            $type = 'Core';
            if ($course->pivot && isset($course->pivot->type)) {
                 $type = ucfirst($course->pivot->type); // Retake/Missed/Extra
            } elseif ($course->pivot && isset($course->pivot->course_type)) {
                 $type = ucfirst($course->pivot->course_type); // Core/Elective
            }

            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'credit_units' => $cu,
                'type' => $type,
                'lecturers' => $course->lecturers->map(fn($l) => ['id' => $l->id, 'name' => $l->user->name ?? 'Unknown']),
            ];
        });

        return response()->json(['courses' => $data]);
    }

    /**
     * Get detailed attendance history (Tracking).
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json(['history' => []]);
        }
        $semesterId = $enrollment->academic_semester_id;

        // 1. Get schedules for the student's CURRENT group
        $groupSchedules = Schedule::with('course')
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereHas('course.programs', function ($q) use ($enrollment) {
                $q->where('programs.id', $enrollment->program_id)
                  ->where('course_program.year_of_study', $enrollment->year_of_study);
            })
            ->get();

        // 1b. Get schedules for RETAKE/EXTRA courses
        $retakeRegistrations = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->withPivot('target_group_id')
             ->get();

        $retakeSchedules = collect([]);
        foreach ($retakeRegistrations as $registration) {
             $query = Schedule::with('course')
                 ->where('course_id', $registration->id)
                 ->where('academic_semester_id', $semesterId);
             if ($registration->pivot->target_group_id) {
                 $query->where('group_id', $registration->pivot->target_group_id);
             }
             $retakeSchedules = $retakeSchedules->merge($query->get());
        }

        // 2. Get schedules actually attended
        $attendedScheduleIds = Attendance::where('student_id', $student->id)
            ->whereHas('schedule', function ($q) use ($semesterId) {
                $q->where('academic_semester_id', $semesterId);
            })
            ->pluck('schedule_id');

        $attendedSchedules = Schedule::with('course')
            ->whereIn('id', $attendedScheduleIds)
            ->get();

        // 3. Merge all
        $allSchedules = $groupSchedules->merge($attendedSchedules)->merge($retakeSchedules)->unique('id')->sortBy('start_at');

        // Group by Course
        $groupedByCourse = $allSchedules->groupBy('course_id');

        $allAttendance = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get();

        $history = [];

        foreach ($groupedByCourse as $courseId => $courseSchedules) {
            $courseName = $courseSchedules->first()->course->name ?? '—';
            $courseCode = $courseSchedules->first()->course->code ?? '';

            $validSchedules = $courseSchedules->where('is_cancelled', false);
            $totalSessions = $validSchedules->count();
            $cancelledCount = $courseSchedules->where('is_cancelled', true)->count();

            $attendedCount = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->count();

            // Time Logic
            $courseMinutes = 0;
            $totalPossibleMinutes = 0;

            foreach ($validSchedules as $sch) {
                if ($sch->start_at && $sch->end_at) {
                    $totalPossibleMinutes += $sch->start_at->diffInMinutes($sch->end_at);
                }
            }

            $courseAttendance = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'));
            foreach ($courseAttendance as $att) {
                if ($att->clock_out_time) {
                    $courseMinutes += $att->marked_at->diffInMinutes($att->clock_out_time);
                } elseif ($att->schedule->end_at && $att->marked_at) {
                     $endTime = now()->lt($att->schedule->end_at) ? now() : $att->schedule->end_at;
                     $courseMinutes += $att->marked_at->diffInMinutes($endTime);
                } elseif ($att->schedule->start_at && $att->schedule->end_at) {
                    $courseMinutes += $att->schedule->start_at->diffInMinutes($att->schedule->end_at);
                }
            }

            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;

            // Calculate Missed
            $attendedIds = $courseAttendance->whereIn('status', ['present', 'late'])->pluck('schedule_id');
            $now = now();
            $missedCount = $validSchedules->filter(function($s) use ($now, $attendedIds) {
                 $isPast = $s->end_at ? $s->end_at->lt($now) : ($s->start_at ? $s->start_at->lt($now) : false);
                 return $isPast && !$attendedIds->contains($s->id);
            })->count();

            $history[] = [
                'course_id' => $courseId,
                'course_name' => $courseName,
                'course_code' => $courseCode,
                'progress_percent' => $percent,
                'attended' => $attendedCount,
                'total_sessions' => $totalSessions,
                'missed' => $missedCount,
                'cancelled' => $cancelledCount,
                'time_minutes' => (int)$courseMinutes,
                'total_possible_minutes' => (int)$totalPossibleMinutes,
            ];
        }

        // Sort by progress descending
        usort($history, fn($a, $b) => $b['progress_percent'] <=> $a['progress_percent']);

        return response()->json(['history' => $history]);
    }

    /**
     * Register for a retake/extra course.
     */
    public function storeRetake(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json(['message' => 'Please enroll first.'], 400);
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'target_group_id' => ['nullable', 'exists:groups,id'],
            'type' => ['required', 'in:retake,missed,extra'],
        ]);

        // Check if already registered
        $exists = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
            ->where('course_id', $validated['course_id'])
            ->where('academic_semester_id', $enrollment->academic_semester_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You are already registered for this course.'], 400);
        }

        \App\Models\StudentCourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $validated['course_id'],
            'academic_semester_id' => $enrollment->academic_semester_id,
            'target_group_id' => $validated['target_group_id'] ?? null,
            'type' => $validated['type'],
        ]);

        return response()->json(['message' => 'Course registered successfully.'], 201);
    }

    /**
     * Remove a retake/extra course registration.
     */
    public function destroyRetake(Request $request, $id)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
             return response()->json(['message' => 'Student record not found.'], 404);
        }

        // Try to find by registration ID first (backward compatibility)
        $registration = \App\Models\StudentCourseRegistration::where('id', $id)
            ->where('student_id', $student->id)
            ->first();

        // If not found by ID, try to find by course_id and current semester
        if (!$registration) {
            $enrollment = $student->currentEnrollment();
            if ($enrollment) {
                $registration = \App\Models\StudentCourseRegistration::where('course_id', $id)
                    ->where('student_id', $student->id)
                    ->where('academic_semester_id', $enrollment->academic_semester_id)
                    ->first();
            }
        }

        if (!$registration) {
            return response()->json(['message' => 'Registration not found.'], 404);
        }

        $registration->delete();

        return response()->json(['message' => 'Course registration removed.']);
    }

    /**
     * Get data for retake/extra course registration (available courses and groups).
     * Pass ?year=X to filter courses by year of study.
     */
    public function getRetakeData(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json([
                'courses' => [],
                'groups' => [],
                'enrollment' => null,
                'message' => 'Not enrolled in current semester.'
            ]);
        }

        $year = $request->input('year');
        $activeSemester = $enrollment->academicSemester;

        // If year is provided, filter courses by year
        if ($year) {
            // Extract semester number (e.g. "Semester 2" -> "2")
            $semesterNumber = null;
            if (preg_match('/(\d+)/', $activeSemester->semester, $matches)) {
                 $semesterNumber = $matches[1];
            }
            $validSemesterValues = array_filter([$activeSemester->semester, $semesterNumber]);

            $courses = Course::whereHas('programs', function ($q) use ($enrollment, $year, $validSemesterValues) {
                $q->where('programs.id', $enrollment->program_id)
                  ->where('course_program.year_of_study', $year)
                  ->whereIn('course_program.semester_offered', $validSemesterValues);
            })->select('id', 'code', 'name')->orderBy('code')->get();
        } else {
            // No year selected, return empty courses
            $courses = [];
        }

        // Get all groups for the student's program (or all groups if program_id is null in groups table)
        $groups = \App\Models\Group::when($enrollment->program_id, function($q) use ($enrollment) {
                return $q->where('program_id', $enrollment->program_id)
                         ->orWhereNull('program_id'); // Include groups without program
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'courses' => $courses,
            'groups' => $groups,
            'enrollment' => [
                'program_name' => $enrollment->program->name ?? '',
                'year_of_study' => $enrollment->year_of_study,
                'semester' => $activeSemester->display_name ?? $activeSemester->semester,
            ],
        ]);
    }
}
