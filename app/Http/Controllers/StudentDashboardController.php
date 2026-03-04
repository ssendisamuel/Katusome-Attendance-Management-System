<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\AcademicSemester;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access the dashboard.']);
        }

        // Get student's current enrollment
        $enrollment = $student->currentEnrollment();

        // If no enrollment, show message
        if (!$enrollment) {
            $activeSemester = AcademicSemester::where('is_active', true)->first();
            if ($activeSemester) {
                return redirect()->route('enrollment.show')
                    ->with('info', "Please enroll in {$activeSemester->display_name} to view your dashboard.");
            }
            // No active semester at all
            return view('content.dashboards.student', [
                'student' => $student,
                'schedules' => collect([]),
                'attendanceBySchedule' => collect([]),
                'metrics' => [
                    'presentToday' => 0,
                    'lateToday' => 0,
                    'timeSpentTotalHours' => 0,
                    'attendanceTrack' => [],
                    'attendanceTrackTable' => [],
                    'weeklyLabel' => '',
                    'weeklyAttended' => [],
                    'monthlySummary' => ['attended' => 0, 'missed' => 0, 'upcoming' => 0],
                ],
                'noEnrollment' => true,
            ]);
        }

        $semesterId = $enrollment->academic_semester_id;
        $activeSemester = $enrollment->academicSemester;

        // Extract semester name (e.g. "Semester 2")
        $semesterName = $activeSemester->semester;

        // Get course IDs from PROGRAM STRUCTURE for this student's program + year + semester
        $enrolledCourseIds = \Illuminate\Support\Facades\DB::table('course_program')
            ->where('program_id', $enrollment->program_id)
            ->where('year_of_study', $enrollment->year_of_study)
            ->where('semester_offered', $semesterName)
            ->pluck('course_id');

        // 1. Get schedules for the student's CURRENT group + semester, filtered by enrolled courses
        $groupSchedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereIn('course_id', $enrolledCourseIds)
            ->get();

        // 1b. Get schedules for RETAKE/EXTRA courses registered this semester
        $retakeRegistrations = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->withPivot('target_group_id')
             ->get();

        $retakeSchedules = collect([]);

        foreach ($retakeRegistrations as $registration) {
             // For each registered retake course, fetch schedules
             // If target_group_id is set, filter by that group
             // If not set, maybe fetch all (or none? fallback to all for safety)

             $query = Schedule::with(['course', 'lecturer'])
                 ->where('course_id', $registration->id)
                 ->where('academic_semester_id', $semesterId);

             if ($registration->pivot->target_group_id) {
                 $query->where('group_id', $registration->pivot->target_group_id);
             }

             $retakeSchedules = $retakeSchedules->merge($query->get());
        }

        // 2. Get schedules the student has ACTUALLY ATTENDED this semester (even if from a previous group)
        $attendedScheduleIds = Attendance::where('student_id', $student->id)
            ->whereHas('schedule', function ($q) use ($semesterId) {
                $q->where('academic_semester_id', $semesterId);
            })
            ->pluck('schedule_id');

        $attendedSchedules = Schedule::with(['course', 'lecturer'])
            ->whereIn('id', $attendedScheduleIds)
            ->get();

        // Filter out courses that the student explicitly dropped this semester
        $droppedCourseIds = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
            ->where('academic_semester_id', $semesterId)
            ->where('type', 'dropped')
            ->pluck('course_id');

        // 3. Merge and unique by ID to get the complete list of relevant schedules
        $allSchedules = $groupSchedules->merge($attendedSchedules)->merge($retakeSchedules)->unique('id')->sortBy('start_at');

        // Remove schedules for dropped courses
        $allSchedules = $allSchedules->reject(function ($schedule) use ($droppedCourseIds) {
            return $droppedCourseIds->contains($schedule->course_id);
        });

        // Today's Schedules (from the merged list)
        $today = Carbon::today();
        $schedulesToday = $allSchedules->filter(function ($s) use ($today) {
            return $s->start_at->isSameDay($today);
        })->values();

        $attendanceByScheduleToday = Attendance::whereIn('schedule_id', $schedulesToday->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        // Present/Late today
        $presentToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $lateToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();

        // Attendance Track (Course Metrics)
        // Group all relevant schedules by Course
        $groupedByCourse = $allSchedules->groupBy('course_id');
        $courseMetrics = [];
        $totalMinutesAll = 0;

        // Get all attendance records for these schedules once to avoid N+1
        $allAttendance = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get();

        foreach ($groupedByCourse as $courseId => $courseSchedules) {
            $courseName = $courseSchedules->first()->course->name ?? '—';

            // Total sessions (excluding cancelled)
            $validSchedules = $courseSchedules->where('is_cancelled', false);
            $totalSessions = $validSchedules->count();

            $cancelledCount = $courseSchedules->where('is_cancelled', true)->count();

            // Attended for this course
            $attendedCount = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->count();

            // Time Logic
            $courseMinutes = 0;
            $totalPossibleMinutes = 0;

            // Calculate possible minutes from valid schedules
            foreach ($validSchedules as $sch) {
                if ($sch->start_at && $sch->end_at) {
                    $totalPossibleMinutes += $sch->start_at->diffInMinutes($sch->end_at);
                }
            }

            // Calculate actual minutes from attendance
            $courseAttendance = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'));
            foreach ($courseAttendance as $att) {
                if ($att->clock_out_time) {
                    $courseMinutes += $att->marked_at->diffInMinutes($att->clock_out_time);
                } elseif ($att->schedule->end_at && $att->marked_at) { // Active
                     $endTime = now()->lt($att->schedule->end_at) ? now() : $att->schedule->end_at;
                     $courseMinutes += $att->marked_at->diffInMinutes($endTime);
                } elseif ($att->schedule->start_at && $att->schedule->end_at) {
                    // Fallback
                    $courseMinutes += $att->schedule->start_at->diffInMinutes($att->schedule->end_at);
                }
            }
            $totalMinutesAll += $courseMinutes;

            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;

            // Calculate Missed (Past valid schedules not attended)
            $attendedScheduleIds = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->pluck('schedule_id');

            $now = now();
            $missedCount = $validSchedules->filter(function($s) use ($now, $attendedScheduleIds) {
                 $isPast = $s->end_at ? $s->end_at->lt($now) : ($s->start_at ? $s->start_at->lt($now) : false);
                 return $isPast && !$attendedScheduleIds->contains($s->id);
            })->count();

            $courseMetrics[] = [
                'course_name' => $courseName,
                'progress_percent' => $percent,
                'attended_count' => $attendedCount,
                'taught_count' => $totalSessions,
                'missed_count' => $missedCount,
                'cancelled' => $cancelledCount,
                'time_minutes' => $courseMinutes,
                'total_possible_minutes' => $totalPossibleMinutes,
            ];
        }

        $attendanceTrack = collect($courseMetrics)->sortByDesc('progress_percent')->values()->all();
        $attendanceTrackTable = collect($courseMetrics)
            ->sortByDesc('progress_percent')
            ->values()
            ->map(function ($m) {
                return [
                    'name' => $m['course_name'],
                    'progress' => $m['progress_percent'],
                    'attended' => $m['attended_count'],
                    'taught' => $m['taught_count'],
                    'missed' => $m['missed_count'],
                    'cancelled' => $m['cancelled'],
                    'time' => self::minutesToHuman($m['time_minutes']) . ' / ' . self::minutesToHuman($m['total_possible_minutes']),
                ];
            })
            ->all();

        // Weekly attended classes (present/late) within current week
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weeklyAttendances = $allAttendance->whereBetween('marked_at', [$weekStart, $weekEnd])
            ->whereIn('status', ['present', 'late'])
            ->sortBy('marked_at');

        $weeklyAttended = $weeklyAttendances->map(function ($att) {
            $courseName = optional($att->schedule->course)->name ?? '—';
            return [
                'name' => $courseName,
                'date' => $att->marked_at ? $att->marked_at->format('D, M j') : '—',
                'time' => $att->marked_at ? $att->marked_at->format('h:i A') : '—',
            ];
        })->values()->all();

        $weekLabel = $weekStart->format('M j') . ' – ' . $weekEnd->format('M j');

        // Monthly summary
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $attendedMonth = $allAttendance->whereBetween('marked_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['present', 'late'])
            ->count();

        // Missed in Month: Valid Schedules in Month - Attended in Month
        // We use $allSchedules for this calculation
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
            'attendanceTrack' => array_map(fn($m) => [
                'name' => $m['course_name'],
                'percent' => $m['progress_percent']
            ], $attendanceTrack),
            'attendanceTrackTable' => $attendanceTrackTable,
            'weeklyLabel' => $weekLabel,
            'weeklyAttended' => $weeklyAttended,
            'monthlySummary' => [
                'attended' => $attendedMonth,
                'missed' => $missedMonth,
                'upcoming' => $upcomingMonth,
            ],
        ];

        return view('content.dashboards.student', [
            'student' => $student,
            'enrollment' => $enrollment,
            'schedules' => $schedulesToday,
            'attendanceBySchedule' => $attendanceByScheduleToday,
            'metrics' => $metrics,
        ]);
    }

    public function storeRetakeCourse(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login');
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return back()->with('info', 'Please enroll first.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'target_group_id' => ['nullable', 'exists:groups,id'], // Optional but encouraged
            'type' => ['required', 'in:retake,missed,extra'],
        ]);

        // Check if already registered
        $exists = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
            ->where('course_id', $validated['course_id'])
            ->where('academic_semester_id', $enrollment->academic_semester_id)
            ->exists();

        if ($exists) {
            return back()->with('info', 'You are already registered for this course.');
        }

        \App\Models\StudentCourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $validated['course_id'],
            'academic_semester_id' => $enrollment->academic_semester_id,
            'target_group_id' => $validated['target_group_id'] ?? null,
            'type' => $validated['type'],
        ]);

        return back()->with('success', 'Course registered successfully.');
    }

    // API Methods for Dropdowns
    public function getProgramYears(Request $request)
    {
        // Simple 1-4 years for now, or dynamic based on program duration if we had that field
        // Assuming 4 years max for most programs
        return response()->json([
            ['id' => 1, 'name' => 'Year 1'],
            ['id' => 2, 'name' => 'Year 2'],
            ['id' => 3, 'name' => 'Year 3'],
            ['id' => 4, 'name' => 'Year 4'],
        ]);
    }

    public function getProgramCourses(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'semester_id' => 'required|exists:academic_semesters,id'
        ]);

        $user = auth()->user();
        $student = optional($user)->student;
        $enrollment = $student->currentEnrollment();

        if (!$enrollment) return response()->json([]);

        $programId = $enrollment->program_id;
        $year = $request->year;
        $targetSemester = \App\Models\AcademicSemester::find($request->semester_id);

        // Extract semester number (e.g. "Semester 2" -> "2")
        $semesterNumber = null;
        if (preg_match('/(\d+)/', $targetSemester->semester, $matches)) {
             $semesterNumber = $matches[1];
        }
        $validSemesterValues = array_filter([$targetSemester->semester, $semesterNumber]);

        $courses = \App\Models\Course::whereHas('programs', function ($q) use ($programId, $year, $validSemesterValues) {
            $q->where('programs.id', $programId)
              ->where('course_program.year_of_study', $year)
              ->whereIn('course_program.semester_offered', $validSemesterValues);
        })
        ->select('courses.id', 'courses.code', 'courses.name')
        ->orderBy('courses.code')
        ->get();

        return response()->json($courses);
    }

    public function getProgramGroups(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        $enrollment = $student->currentEnrollment();

        if (!$enrollment) return response()->json([]);

        $programId = $enrollment->program_id;

        $groups = \App\Models\Group::where(function($q) use ($programId) {
                $q->where('program_id', $programId)
                  ->orWhereNull('program_id');
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($groups);
    }

    public function destroyRetake(Request $request, $id)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student) abort(403);

        $registration = \App\Models\StudentCourseRegistration::where('id', $id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $registration->delete();

        return back()->with('success', 'Course registration removed.');
    }

    public function dropCourse(Request $request, $course_id)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student) abort(403);

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return back()->with('error', 'No active enrollment found.');
        }

        // Validate course exists
        $course = \App\Models\Course::findOrFail($course_id);

        // Check if already dropped
        $exists = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('academic_semester_id', $enrollment->academic_semester_id)
            ->where('type', 'dropped')
            ->exists();

        if ($exists) {
            return back()->with('info', 'This course is already dropped.');
        }

        // Add dropped record
        \App\Models\StudentCourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_semester_id' => $enrollment->academic_semester_id,
            'type' => 'dropped',
        ]);

        return back()->with('success', 'Course successfully dropped.');
    }

    public function coursesJson()
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return response()->json(['data' => []]);
        }

        // Get student's current enrollment for semester filtering
        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json(['data' => []]);
        }
        $semesterId = $enrollment->academic_semester_id;

        $activeSemester = \App\Models\AcademicSemester::find($semesterId);
        // Extract semester name
        $semesterName = $activeSemester ? $activeSemester->semester : 'Semester 2';

        // Get course IDs from PROGRAM STRUCTURE for this student's program + year + semester
        $enrolledCourseIds = \Illuminate\Support\Facades\DB::table('course_program')
            ->where('program_id', $enrollment->program_id)
            ->where('year_of_study', $enrollment->year_of_study)
            ->where('semester_offered', $semesterName)
            ->pluck('course_id');

        $courseIds = Schedule::where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereIn('course_id', $enrolledCourseIds)
            ->pluck('course_id');

        $retakeCourseIds = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->wherePivot('type', '!=', 'dropped')
             ->pluck('courses.id');

        $droppedCourseIds = \App\Models\StudentCourseRegistration::where('student_id', $student->id)
             ->where('academic_semester_id', $semesterId)
             ->where('type', 'dropped')
             ->pluck('course_id');

        $courseIds = $courseIds->merge($retakeCourseIds)->unique()->values();
        $courseIds = $courseIds->diff($droppedCourseIds)->values();

        $allSchedules = Schedule::where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereIn('course_id', $courseIds)
            ->where('is_cancelled', false) // Exclude cancelled
            ->orderBy('start_at')
            ->get();

        $attendanceBySchedule = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get()
            ->groupBy('schedule_id');

        $rows = [];
        $idCounter = 1;
        foreach ($courseIds as $courseId) {
            $courseSchedules = $allSchedules->where('course_id', $courseId);
            $scheduleIds = $courseSchedules->pluck('id');
            $totalSessions = $scheduleIds->count();
            $attendedCount = Attendance::whereIn('schedule_id', $scheduleIds)
                ->where('student_id', $student->id)
                ->whereIn('status', ['present', 'late'])
                ->count();

            $courseMinutes = 0;
            foreach ($courseSchedules as $sch) {
                $attForSch = optional($attendanceBySchedule->get($sch->id))->first();
                if ($attForSch && in_array($attForSch->status, ['present', 'late'], true)) {
                    // Use actual time if clocked out
                    if ($attForSch->clock_out_time) {
                        $courseMinutes += $attForSch->marked_at->diffInMinutes($attForSch->clock_out_time);
                    }
                    // If active (not clocked out), calculate time so far (capped at schedule end)
                    elseif ($sch->end_at && $attForSch->marked_at) {
                         $endTime = now()->lt($sch->end_at) ? now() : $sch->end_at;
                         $courseMinutes += $attForSch->marked_at->diffInMinutes($endTime);
                    }
                    // Fallback to scheduled duration only if something is really missing
                    elseif ($sch->start_at && $sch->end_at) {
                        $courseMinutes += $sch->start_at->diffInMinutes($sch->end_at);
                    }
                }
            }
            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;
            $courseName = optional($courseSchedules->first()?->course)->name ?? '—';

            $rows[] = [
                'id' => $idCounter++,
                'course name' => $courseName,
                'time' => self::minutesToIsoDuration($courseMinutes),
                'progress' => $percent . '%',
                'attended' => $attendedCount,
            ];
        }

        return response()->json(['data' => $rows]);
    }

    private static function minutesToIsoDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return 'PT' . $hours . 'H' . $mins . 'M';
    }

    private static function minutesToHuman(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return ($hours > 0 ? ($hours . 'h ') : '') . ($mins > 0 ? ($mins . 'm') : ($hours === 0 ? '0m' : ''));
    }

    public function courses()
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login');
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return redirect()->route('student.dashboard')->with('info', 'Please enroll first.');
        }
        // Get course IDs from teaching load for this student's program + year + semester
        $semesterId = $enrollment->academic_semester_id;
        $activeSemester = $enrollment->academicSemester;

        // Extract semester name
        $semesterName = $activeSemester ? $activeSemester->semester : 'Semester 2';

        // Get course IDs from PROGRAM STRUCTURE for this student's program + year + semester
        $enrolledCourseIds = \Illuminate\Support\Facades\DB::table('course_program')
            ->where('program_id', $enrollment->program_id)
            ->where('year_of_study', $enrollment->year_of_study)
            ->where('semester_offered', $semesterName)
            ->pluck('course_id');

        // Show ALL courses from teaching load for this student's program + year + semester
        $courses = \App\Models\Course::whereIn('id', $enrolledCourseIds)->get();

        // MERGE: Add Retake/Missed/Extra courses
        $extraCourses = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->withPivot(['id', 'academic_semester_id', 'type'])
             ->get();

        // Identify dropped courses
        $droppedCourseIds = $extraCourses->where('pivot.type', 'dropped')->pluck('id')->toArray();

        // Filter out dropped courses from standard active list
        $activeCourses = collect();
        foreach ($courses->concat($extraCourses)->unique('id') as $c) {
            if (!in_array($c->id, $droppedCourseIds)) {
                $activeCourses->push($c);
            }
        }

        // Get the pure models of the dropped courses
        $droppedCourses = $courses->whereIn('id', $droppedCourseIds)->values();

        $availableCourses = \App\Models\Course::select('id', 'code', 'name')->orderBy('code')->get();

        return view('content.dashboards.student_courses', compact('student', 'enrollment', 'activeCourses', 'droppedCourses', 'availableCourses'));
    }
}
