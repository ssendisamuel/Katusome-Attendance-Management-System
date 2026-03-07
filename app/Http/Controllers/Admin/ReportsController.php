<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasAttendanceFilters;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\StudentEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\AcademicSemester;

class ReportsController extends Controller
{
    use HasAttendanceFilters;

    public function dashboard(Request $request)
    {
        return view('admin.reports.dashboard');
    }

    /**
     * Daily Attendance Report - IMPROVED
     * Shows all attendance records for a specific date with accurate calculations
     */
    public function daily(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        // Base query with relationships
        $query = Attendance::with(['student.enrollments', 'schedule.course', 'schedule.group', 'schedule.lecturer'])
            ->whereHas('schedule', function($q) use ($date) {
                $q->whereDate('start_at', $date);
            });

        // Apply hierarchical filters
        $query = $this->applyHierarchicalFilters($query, $request, 'student');

        // Additional filters
        if ($courseId = $request->input('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $courseId));
        }
        if ($groupId = $request->input('group_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('group_id', $groupId));
        }
        if ($lecturerId = $request->input('lecturer_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('lecturer_id', $lecturerId));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('reg_no', 'like', "%$search%")
                ->orWhere('student_no', 'like', "%$search%"));
        }

        $attendances = $query->orderByDesc('marked_at')->paginate(25);

        // ACCURATE SUMMARY CALCULATION
        // Get all schedules for this date (filtered)
        $schedulesQuery = Schedule::whereDate('start_at', $date)
            ->where('is_cancelled', false);

        // Apply same filters to schedules
        if ($courseId = $request->input('course_id')) {
            $schedulesQuery->where('course_id', $courseId);
        }
        if ($groupId = $request->input('group_id')) {
            $schedulesQuery->where('group_id', $groupId);
        }
        if ($lecturerId = $request->input('lecturer_id')) {
            $schedulesQuery->where('lecturer_id', $lecturerId);
        }

        $schedules = $schedulesQuery->with('group')->get();

        // Calculate expected: Sum of (students in each group × 1 schedule)
        $expectedTotal = 0;
        $uniqueStudentIds = collect();

        foreach ($schedules as $schedule) {
            if ($schedule->group_id) {
                $studentQuery = Student::where('group_id', $schedule->group_id);

                // Apply hierarchical filters to student count
                if ($campusId = $request->input('campus_id')) {
                    $studentQuery->whereHas('enrollments', fn($q) => $q->where('campus_id', $campusId));
                }
                if ($facultyId = $request->input('faculty_id')) {
                    $studentQuery->whereHas('enrollments.program.department.faculty', fn($q) => $q->where('id', $facultyId));
                }
                if ($departmentId = $request->input('department_id')) {
                    $studentQuery->whereHas('enrollments.program.department', fn($q) => $q->where('id', $departmentId));
                }
                if ($programId = $request->input('program_id')) {
                    $studentQuery->whereHas('enrollments.program', fn($q) => $q->where('id', $programId));
                }
                if ($year = $request->input('year_of_study')) {
                    $studentQuery->whereHas('enrollments', fn($q) => $q->where('year_of_study', $year));
                }

                $groupStudentCount = $studentQuery->count();
                $expectedTotal += $groupStudentCount;

                // Track unique students for better metrics
                $uniqueStudentIds = $uniqueStudentIds->merge($studentQuery->pluck('id'));
            }
        }

        $uniqueStudentIds = $uniqueStudentIds->unique();

        // Get attendance counts (with same filters applied via the query above)
        $allAttendances = $query->get();
        $present = $allAttendances->where('status', 'present')->count();
        $late = $allAttendances->where('status', 'late')->count();
        $excused = $allAttendances->where('status', 'excused')->count();
        $explicitAbsent = $allAttendances->where('status', 'absent')->count();

        // Calculate implicit absences
        $totalRecorded = $allAttendances->count();
        $implicitAbsent = max(0, $expectedTotal - $totalRecorded);
        $totalAbsent = $explicitAbsent + $implicitAbsent;

        // Attendance rate: (Present + Late) / Expected
        $attended = $present + $late;
        $percentage = $this->calculateAttendancePercentage($attended, $expectedTotal);

        // Incomplete sessions (auto-clocked out)
        $incomplete = $allAttendances->filter(function($att) {
            return in_array($att->status, ['present', 'late']) &&
                   $att->clock_out_time &&
                   $att->is_auto_clocked_out;
        })->count();

        return view('admin.reports.daily', array_merge([
            'attendances' => $attendances,
            'date' => $date,
            'expected' => $expectedTotal,
            'present' => $present,
            'late' => $late,
            'excused' => $excused,
            'absent' => $totalAbsent,
            'explicit_absent' => $explicitAbsent,
            'implicit_absent' => $implicitAbsent,
            'incomplete' => $incomplete,
            'percentage' => $percentage,
            'unique_students' => $uniqueStudentIds->count(),
            'total_schedules' => $schedules->count(),
            'lecturers' => Lecturer::with('user')->get()->sortBy('user.name'),
        ], $this->getFilterData()));
    }

    /**
     * Schedule Selector for Class Reports
     * Allows filtering and selecting a schedule to view its attendance
     */
    public function scheduleSelector(Request $request)
    {
        $query = Schedule::with(['course', 'group', 'lecturer', 'venue', 'academicSemester'])
            ->where('is_cancelled', false)
            ->orderByDesc('start_at');

        // Date filter
        if ($date = $request->input('date')) {
            $query->whereDate('start_at', $date);
        } else {
            // Default to today and upcoming
            $query->whereDate('start_at', '>=', today());
        }

        // Apply hierarchical filters
        if ($campusId = $request->input('campus_id')) {
            $query->whereHas('group.students.enrollments', fn($q) => $q->where('campus_id', $campusId));
        }
        if ($facultyId = $request->input('faculty_id')) {
            $query->whereHas('course.programs.department.faculty', fn($q) => $q->where('id', $facultyId));
        }
        if ($departmentId = $request->input('department_id')) {
            $query->whereHas('course.programs.department', fn($q) => $q->where('id', $departmentId));
        }
        if ($programId = $request->input('program_id')) {
            $query->whereHas('course.programs', fn($q) => $q->where('id', $programId));
        }
        if ($courseId = $request->input('course_id')) {
            $query->where('course_id', $courseId);
        }
        if ($groupId = $request->input('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($lecturerId = $request->input('lecturer_id')) {
            $query->where('lecturer_id', $lecturerId);
        }

        $schedules = $query->paginate(25);

        return view('admin.reports.schedule-selector', array_merge([
            'schedules' => $schedules,
            'date' => $date ?? today()->toDateString(),
            'lecturers' => Lecturer::with('user')->get()->sortBy('user.name'),
        ], $this->getFilterData()));
    }

    /**
     * API: Get faculties by campus
     */
    public function facultiesByCampus(Request $request)
    {
        $campusId = $request->input('campus_id');
        if (!$campusId) {
            return response()->json([]);
        }

        $faculties = Faculty::where('campus_id', $campusId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($faculties);
    }

    /**
     * API: Get departments by faculty
     */
    public function departmentsByFaculty(Request $request)
    {
        $facultyId = $request->input('faculty_id');
        if (!$facultyId) {
            return response()->json([]);
        }

        $departments = Department::where('faculty_id', $facultyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($departments);
    }

    /**
     * API: Get programs by department
     */
    public function programsByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');
        if (!$departmentId) {
            return response()->json([]);
        }

        $programs = Program::where('department_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($programs);
    }

    /**
     * API: Get courses by program (and optionally year of study)
     */
    public function coursesByProgram(Request $request)
    {
        $programId = $request->input('program_id');
        if (!$programId) {
            return response()->json([]);
        }

        $query = Course::whereHas('programs', function($q) use ($programId) {
            $q->where('programs.id', $programId);
        });

        // Filter by year of study if provided
        if ($year = $request->input('year_of_study')) {
            $query->whereHas('programs', function($q) use ($programId, $year) {
                $q->where('programs.id', $programId)
                  ->where('course_program.year_of_study', $year);
            });
        }

        $courses = $query->orderBy('code')->get(['id', 'name', 'code']);

        return response()->json($courses);
    }

    /**
     * API: Get groups by program (and optionally year of study)
     */
    public function groupsByProgram(Request $request)
    {
        $programId = $request->input('program_id');
        if (!$programId) {
            return response()->json([]);
        }

        // Get groups that have students enrolled in this program
        $query = Group::whereHas('students.enrollments', function($q) use ($programId) {
            $q->where('program_id', $programId);
        });

        // Filter by year of study if provided
        if ($year = $request->input('year_of_study')) {
            $query->whereHas('students.enrollments', function($q) use ($programId, $year) {
                $q->where('program_id', $programId)
                  ->where('year_of_study', $year);
            });
        }

        $groups = $query->orderBy('name')->get(['id', 'name']);

        return response()->json($groups);
    }

    /**
     * Real-Time Schedule Attendance Report - NEW
     * Shows live attendance for a specific class session
     */
    public function scheduleAttendance(Request $request, Schedule $schedule)
    {
        $schedule->load(['course', 'group', 'lecturer', 'venue', 'academicSemester']);

        // Get expected students for this schedule
        $expectedStudentsQuery = Student::whereHas('enrollments', function($q) use ($schedule) {
            $q->where('group_id', $schedule->group_id)
              ->where('academic_semester_id', $schedule->academic_semester_id);
        });

        $expectedStudents = $expectedStudentsQuery->with(['enrollments.program'])->orderBy('name')->get();
        $expectedCount = $expectedStudents->count();

        // Get attendance records for this schedule
        $attendances = Attendance::where('schedule_id', $schedule->id)
            ->with('student')
            ->get();

        // Calculate metrics
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $excused = $attendances->where('status', 'excused')->count();
        $explicitAbsent = $attendances->where('status', 'absent')->count();

        $checkedIn = $present + $late;
        $notCheckedIn = $expectedCount - $attendances->count();
        $implicitAbsent = max(0, $notCheckedIn);
        $totalAbsent = $explicitAbsent + $implicitAbsent;

        $attendanceRate = $this->calculateAttendancePercentage($checkedIn, $expectedCount);

        // Average check-in time
        $avgCheckInTime = null;
        if ($checkedIn > 0) {
            $checkInTimes = $attendances->whereIn('status', ['present', 'late'])
                ->map(function($att) use ($schedule) {
                    return $att->marked_at->diffInMinutes($schedule->start_at);
                });
            $avgCheckInTime = round($checkInTimes->avg());
        }

        // Build student list with status
        $studentList = $expectedStudents->map(function($student) use ($attendances) {
            $attendance = $attendances->firstWhere('student_id', $student->id);

            return [
                'student' => $student,
                'attendance' => $attendance,
                'status' => $attendance ? $attendance->status : 'not_checked_in',
                'checked_in_at' => $attendance ? $attendance->marked_at : null,
                'has_selfie' => $attendance && $attendance->selfie_path ? true : false,
                'clocked_out' => $attendance && $attendance->clock_out_time ? true : false,
            ];
        });

        // Sort: checked in first, then by name
        $studentList = $studentList->sortBy([
            fn($a, $b) => ($a['status'] === 'not_checked_in' ? 1 : 0) <=> ($b['status'] === 'not_checked_in' ? 1 : 0),
            fn($a, $b) => $a['student']->name <=> $b['student']->name,
        ]);

        $stats = [
            'expected' => $expectedCount,
            'checked_in' => $checkedIn,
            'not_checked_in' => $notCheckedIn,
            'present' => $present,
            'late' => $late,
            'excused' => $excused,
            'absent' => $totalAbsent,
            'explicit_absent' => $explicitAbsent,
            'implicit_absent' => $implicitAbsent,
            'attendance_rate' => $attendanceRate,
            'avg_check_in_time' => $avgCheckInTime,
            'late_arrivals' => $late,
        ];

        // Export to PDF if requested
        if ($request->input('export') === 'pdf') {
            return $this->exportScheduleAttendancePdf($schedule, $studentList, $stats);
        }

        return view('admin.reports.schedule-attendance', compact('schedule', 'studentList', 'stats'));
    }

    public function monthly(Request $request)
    {
        $month = (int)($request->input('month', Carbon::today()->month));
        $year = (int)($request->input('year', Carbon::today()->year));

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        // Get students with filters
        $studentsQuery = Student::with(['group', 'program', 'enrollments']);

        // Apply hierarchical filters
        if ($campusId = $request->input('campus_id')) {
            $studentsQuery->whereHas('enrollments', fn($q) => $q->where('campus_id', $campusId));
        }
        if ($facultyId = $request->input('faculty_id')) {
            $studentsQuery->whereHas('enrollments.program.department.faculty', fn($q) => $q->where('id', $facultyId));
        }
        if ($departmentId = $request->input('department_id')) {
            $studentsQuery->whereHas('enrollments.program.department', fn($q) => $q->where('id', $departmentId));
        }
        if ($programId = $request->input('program_id')) {
            $studentsQuery->whereHas('enrollments.program', fn($q) => $q->where('id', $programId));
        }
        if ($year_of_study = $request->input('year_of_study')) {
            $studentsQuery->whereHas('enrollments', fn($q) => $q->where('year_of_study', $year_of_study));
        }
        if ($groupId = $request->input('group_id')) {
            $studentsQuery->whereHas('enrollments', fn($q) => $q->where('group_id', $groupId));
        }

        $students = $studentsQuery->get();

        // Optimization: Get expected schedule counts per group for this month
        $groupScheduleCounts = Schedule::whereBetween('start_at', [$start, $end])
            ->where('is_cancelled', false)
            ->whereNotNull('group_id')
            ->select('group_id', DB::raw('count(*) as total'))
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $summary = [];
        $atRiskCount = 0;

        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();

            $present = $records->where('status', 'present')->count();
            $late = $records->where('status', 'late')->count();
            $excused = $records->where('status', 'excused')->count();
            $explicitAbsent = $records->where('status', 'absent')->count();

            // Calculate expected based on group schedules
            $expected = $groupScheduleCounts[$student->group_id] ?? 0;

            // Ensure expected is at least the recorded count (for data integrity)
            $expected = max($expected, $records->count());

            // Calculate implicit absences
            $totalRecorded = $records->count();
            $implicitAbsent = max(0, $expected - $totalRecorded);
            $totalAbsent = $explicitAbsent + $implicitAbsent;

            // Attendance percentage
            $attended = $present + $late;
            $percentage = $this->calculateAttendancePercentage($attended, $expected);

            // Flag at-risk students (< 70%)
            $atRisk = $percentage < 70;
            if ($atRisk) {
                $atRiskCount++;
            }

            $summary[] = [
                'student' => $student,
                'present' => $present,
                'late' => $late,
                'excused' => $excused,
                'absent' => $totalAbsent,
                'explicit_absent' => $explicitAbsent,
                'implicit_absent' => $implicitAbsent,
                'expected' => $expected,
                'percentage' => $percentage,
                'at_risk' => $atRisk,
            ];
        }

        // Sort by percentage (at-risk first)
        usort($summary, function($a, $b) {
            if ($a['at_risk'] !== $b['at_risk']) {
                return $b['at_risk'] <=> $a['at_risk']; // at_risk first
            }
            return $a['percentage'] <=> $b['percentage']; // then by percentage ascending
        });

        // Group aggregates
        $byGroup = collect($summary)->groupBy(fn($row) => optional($row['student']->group)->name)->map(function ($rows) {
            $expected = $rows->sum('expected');
            $present = $rows->sum('present');
            $late = $rows->sum('late');
            $absent = $rows->sum('absent');
            $attended = $present + $late;
            $rate = $expected > 0 ? round(($attended / $expected) * 100, 2) : 0;
            $atRiskCount = $rows->where('at_risk', true)->count();

            return [
                'expected' => $expected,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate,
                'at_risk_count' => $atRiskCount,
                'student_count' => $rows->count(),
            ];
        });

        return view('admin.reports.monthly', array_merge([
            'summary' => $summary,
            'byGroup' => $byGroup,
            'month' => $month,
            'year' => $year,
            'at_risk_count' => $atRiskCount,
            'total_students' => count($summary),
        ], $this->getFilterData()));
    }

    // JSON endpoints for full-data export
    public function dailyJson(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $query = Attendance::with(['student', 'schedule.course', 'schedule.group', 'schedule.lecturer'])
            ->whereDate('marked_at', $date);
        if ($courseId = $request->input('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $courseId));
        }
        if ($groupId = $request->input('group_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('group_id', $groupId));
        }
        if ($lecturerId = $request->input('lecturer_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('lecturer_id', $lecturerId));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->whereHas('student', fn($q) => $q->where('name', 'like', "%$search%"));
        }
        $rows = $query->orderBy('marked_at')->get();
        // Summary and meta
        $expected = Schedule::whereDate('start_at', $date)->where('is_cancelled', false)->count();
        $present = Attendance::whereDate('marked_at', $date)->where('status', 'present')->count();
        $absent = Attendance::whereDate('marked_at', $date)->where('status', 'absent')->count();
        $late = Attendance::whereDate('marked_at', $date)->where('status', 'late')->count();
        $total = $present + $absent + $late;
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $courseName = $request->input('course_id') ? optional(Course::find($request->input('course_id')))->name : null;
        $groupName = $request->input('group_id') ? optional(Group::find($request->input('group_id')))->name : null;
        $lecturerName = $request->input('lecturer_id') ? optional(Lecturer::find($request->input('lecturer_id')))->name : null;
        $academicYear = (int)Carbon::parse($date)->month >= 8 ? Carbon::parse($date)->year . '/' . (Carbon::parse($date)->year + 1) : (Carbon::parse($date)->year - 1) . '/' . Carbon::parse($date)->year;
        $semester = (int)Carbon::parse($date)->month >= 8 && (int)Carbon::parse($date)->month <= 12 ? 'Semester I' : 'Semester II';
        $reportId = 'REP-ATT-' . Carbon::parse($date)->format('Ymd') . '-' . $nowEat->format('His');
        return response()->json([
            'title' => 'Daily Attendance — ' . $date,
            'columns' => ['Name', 'ID', 'Group', 'Course', 'Time In', 'Status'],
            'rows' => $rows->map(function ($r) {
                return [
                    optional($r->student)->name,
                    optional($r->student)->student_no ?? optional($r->student)->reg_no,
                    optional($r->schedule->group)->name,
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('Y-m-d H:i'),
                    ucfirst($r->status),
                ];
            }),
            'meta' => [
                'institution' => 'Makerere University Business School',
                'system' => 'Katusome Attendance Management System',
                'faculty' => 'Faculty of Computing and Informatics',
                'logo' => asset('storage/mubslogo.png'),
                'category' => 'Operational Report',
                'course' => $courseName,
                'group' => $groupName,
                'lecturer' => $lecturerName,
                'date_range' => Carbon::parse($date)->format('d M Y') . ' – ' . Carbon::parse($date)->format('d M Y'),
                'generated_on' => $nowEat->format('d M Y, h:i A') . ' (EAT)',
                'generated_by' => $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System',
                'academic_year' => 'Academic Year ' . $academicYear,
                'semester' => $semester,
                'report_id' => $reportId,
                'address' => 'Makerere University Business School, Nakawa Campus, Kampala, Uganda',
                'email' => 'attendance@mubs.ac.ug',
                'website' => 'https://katusome.ssendi.dev',
                'confidentiality' => 'This report contains confidential academic data intended for authorized personnel only.',
                'export_note' => $user && ($user->role === 'Lecturer') ? 'Authorized Lecturer Export.' : 'Official Administrative Copy.'
            ],
            'summary' => [
                'expected' => $expected,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate
            ]
        ]);
    }

    public function monthlyJson(Request $request)
    {
        $month = (int)($request->input('month', Carbon::today()->month));
        $year = (int)($request->input('year', Carbon::today()->year));
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $students = Student::with(['group'])->get();

        // Optimization: Get expected schedule counts per group for this month
        $groupScheduleCounts = Schedule::whereBetween('start_at', [$start, $end])
            ->where('is_cancelled', false)
            ->whereNotNull('group_id')
            ->select('group_id', DB::raw('count(*) as total'))
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $rows = [];
        $presentTotal = 0; $absentTotal = 0; $lateTotal = 0; $expectedTotal = 0;
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $present = $records->where('status', 'present')->count();
            $absent = $records->where('status', 'absent')->count();
            $late = $records->where('status', 'late')->count();

            $expected = $groupScheduleCounts[$student->group_id] ?? $records->count();
            $expected = max($expected, $records->count());

            $percentage = $expected > 0 ? round(($present / $expected) * 100) : 0;

            $presentTotal += $present; $absentTotal += $absent; $lateTotal += $late; $expectedTotal += $expected;
            $rows[] = [
                $student->name,
                optional($student->group)->name,
                $present,
                $absent,
                $late,
                $percentage,
            ];
        }
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $academicYear = $month >= 8 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
        $semester = ($month >= 8 && $month <= 12) ? 'Semester I' : 'Semester II';
        $reportId = 'REP-ATT-' . $start->format('Ym') . '-' . $nowEat->format('His');
        return response()->json([
            'title' => 'Monthly Summary — ' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
            'columns' => ['Name', 'Group', 'Present', 'Absent', 'Late', 'Attendance %'],
            'rows' => $rows,
            'meta' => [
                'institution' => 'Makerere University Business School',
                'system' => 'Katusome Attendance Management System',
                'faculty' => 'Faculty of Computing and Informatics',
                'logo' => asset('storage/mubslogo.png'),
                'category' => 'Operational Report',
                'course' => null,
                'group' => 'All Groups',
                'lecturer' => null,
                'date_range' => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
                'generated_on' => $nowEat->format('d M Y, h:i A') . ' (EAT)',
                'generated_by' => $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System',
                'academic_year' => 'Academic Year ' . $academicYear,
                'semester' => $semester,
                'report_id' => $reportId,
                'address' => 'Makerere University Business School, Nakawa Campus, Kampala, Uganda',
                'email' => 'attendance@mubs.ac.ug',
                'website' => 'https://katusome.ssendi.dev',
                'confidentiality' => 'This report contains confidential academic data intended for authorized personnel only.',
                'export_note' => $user && ($user->role === 'Lecturer') ? 'Authorized Lecturer Export.' : 'Official Administrative Copy.'
            ],
            'summary' => [
                'expected' => $expectedTotal,
                'present' => $presentTotal,
                'late' => $lateTotal,
                'absent' => $absentTotal,
                'rate' => ($expectedTotal > 0 ? round(($presentTotal / $expectedTotal) * 100, 1) : 0)
            ]
        ]);
    }

    public function individualJson(Request $request)
    {
        $studentId = $request->input('student_id');
        $student = $studentId ? Student::with(['group'])->find($studentId) : null;
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        $records = Attendance::with(['schedule.course'])
            ->where('student_id', $student->id)
            ->orderBy('marked_at')
            ->get();
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();

        // Calculate Expected
        $start = optional($records->first()->marked_at) ? Carbon::parse($records->first()->marked_at) : Carbon::today()->startOfMonth();
        $end = optional($records->last()->marked_at) ? Carbon::parse($records->last()->marked_at) : Carbon::today();

        $expected = Schedule::where('group_id', $student->group_id)
            ->whereBetween('start_at', [$start, $end])
            ->where('is_cancelled', false)
            ->count();

        $expected = max($expected, $records->count());
        $rate = $expected > 0 ? round(($present / $expected) * 100, 1) : 0;

        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $academicYear = $start->month >= 8 ? $start->year . '/' . ($start->year + 1) : ($start->year - 1) . '/' . $start->year;
        $semester = ($start->month >= 8 && $start->month <= 12) ? 'Semester I' : 'Semester II';
        $reportId = 'REP-ATT-' . $student->id . '-' . $nowEat->format('Ymd') . '-' . $nowEat->format('His');
        return response()->json([
            'title' => 'Individual Attendance — ' . $student->name,
            'columns' => ['Date', 'Course', 'Time In', 'Status'],
            'rows' => $records->map(function ($r) {
                return [
                    optional($r->marked_at)?->format('Y-m-d'),
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('H:i'),
                    ucfirst($r->status),
                ];
            }),
            'meta' => [
                'institution' => 'Makerere University Business School',
                'system' => 'Katusome Attendance Management System',
                'faculty' => 'Faculty of Computing and Informatics',
                'logo' => asset('storage/mubslogo.png'),
                'category' => 'Operational Report',
                'course' => null,
                'group' => optional($student->group)->name,
                'lecturer' => null,
                'date_range' => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
                'generated_on' => $nowEat->format('d M Y, h:i A') . ' (EAT)',
                'generated_by' => $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System',
                'academic_year' => 'Academic Year ' . $academicYear,
                'semester' => $semester,
                'report_id' => $reportId,
                'address' => 'Makerere University Business School, Nakawa Campus, Kampala, Uganda',
                'email' => 'attendance@mubs.ac.ug',
                'website' => 'https://katusome.ssendi.dev',
                'confidentiality' => 'This report contains confidential academic data intended for authorized personnel only.',
                'export_note' => $user && ($user->role === 'Lecturer') ? 'Authorized Lecturer Export.' : 'Official Administrative Copy.'
            ],
            'summary' => [
                'expected' => $expected,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate
            ]
        ]);
    }

    public function absenteeismJson(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfWeek()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->endOfWeek()->toDateString()));
        $threshold = (int)($request->input('threshold', 3));
        $students = Student::with('group')->get();

        // Optimize: Get expected schedule counts per group in range
        $groupScheduleCounts = Schedule::whereBetween('start_at', [$start, $end])
            ->where('is_cancelled', false)
            ->whereNotNull('group_id')
            ->select('group_id', DB::raw('count(*) as total'))
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $rows = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();

            $late = $records->where('status', 'late')->count();
            $recordedAbsent = $records->where('status', 'absent')->count();
            $present = $records->where('status', 'present')->count();
            $excused = $records->where('status', 'excused')->count();

            // Expected schedules
            $expected = $groupScheduleCounts[$student->group_id] ?? 0;
            // Total attended (present/late/excused)
            $attended = $present + $late + $excused;

            // Implicit absences = Expected - Attended
            // But we must respect recorded absences too (maybe extra classes)
            // Logic: Absent = Max(Recorded Absent, Expected - Attended)
            // Or simply: Absent = (Expected - Attended) if Expected > Attended + RecordedAbsent?
            // Let's stick to the standard: Absent = Expected - Attended.
            // If recorded absent exists, it should be part of "Expected" technically.
            // But if Mark Absent job ran, Recorded Absent + Attended should = Expected.
            // If it didn't run, Attended < Expected, so (Expected - Attended) is the true absent count.

            $calculatedAbsent = max(0, $expected - $attended);
            // In case there are extra marked absences (e.g. from individual schedules), take the higher.
            $absent = max($recordedAbsent, $calculatedAbsent);

            // Flag if late or absent exceeds threshold
            $flag = ($late >= $threshold || $absent >= $threshold) ? 'Flagged' : 'OK';

            if ($late > 0 || $absent > 0) {
                $rows[] = [
                    $student->name,
                    optional($student->group)->name,
                    $late,
                    $absent,
                    $flag,
                ];
            }
        }
        return response()->json([
            'title' => 'Absenteeism & Lateness',
            'columns' => ['Student', 'Group', 'Late Count', 'Absences', 'Flag'],
            'rows' => $rows,
            'meta' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'threshold' => $threshold]
        ]);
    }

    public function devicesJson(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfMonth()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->toDateString()));

        // Filter: Only show records that likely involved a device interaction (Present, Late, or has Location data)
        // Absent records usually mean *no* device interaction, so they are noise here.
        $records = Attendance::with(['student', 'schedule.course'])
            ->whereBetween('marked_at', [$start, $end])
            ->where(function($q) {
                $q->whereIn('status', ['present', 'late', 'excused'])
                  ->orWhereNotNull('lat');
            })
            ->orderByDesc('marked_at')
            ->get();

        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        return response()->json([
            'title' => 'Device / Source Logs',
            'columns' => ['User', 'Course', 'Timestamp', 'Status', 'Lat', 'Lng', 'Selfie'],
            'rows' => $records->map(function ($r) {
                return [
                    optional($r->student)->name,
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('Y-m-d H:i'),
                    ucfirst($r->status),
                    $r->lat,
                    $r->lng,
                    $r->selfie_path ? 'Yes' : 'No',
                ];
            }),
            'meta' => [
                'institution' => 'Makerere University Business School',
                'system' => 'Katusome Attendance Management System',
                'faculty' => 'Faculty of Computing and Informatics',
                'logo' => asset('storage/mubslogo.png'),
                'category' => 'Operational Report',
                'date_range' => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
                'generated_on' => $nowEat->format('d M Y, h:i A') . ' (EAT)',
                'generated_by' => $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System',
                'address' => 'Makerere University Business School, Nakawa Campus, Kampala, Uganda',
                'email' => 'attendance@mubs.ac.ug',
                'website' => 'https://katusome.ssendi.dev',
                'confidentiality' => 'This report contains confidential academic data intended for authorized personnel only.',
                'export_note' => $user && ($user->role === 'Lecturer') ? 'Authorized Lecturer Export.' : 'Official Administrative Copy.'
            ],
            'summary' => [
                'expected' => $records->count(),
                'present' => $present,
                'late' => $late,
                'absent' => $absent
            ]
        ]);
    }

    public function individual(Request $request)
    {
        $studentId = $request->input('student_id');
        $student = $studentId ? Student::with(['group', 'program'])->find($studentId) : null;
        $records = collect();

        if ($student) {
            $query = Attendance::with(['schedule.course'])
                ->where('student_id', $student->id);

            // Filters
            if ($courseId = $request->input('course_id')) {
                $query->whereHas('schedule', fn($q) => $q->where('course_id', $courseId));
            }

            if ($semesterId = $request->input('semester_id')) {
                $query->whereHas('schedule', fn($q) => $q->where('academic_semester_id', $semesterId));
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            // Year of Study Filter
            if ($year = $request->input('year_of_study')) {
                 $query->whereHas('schedule.course.programs', function($q) use ($year) {
                     $q->where('course_program.year_of_study', $year);
                 });
            }

            // Date Range
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (!$startDate && !$endDate) {
                 // Default to Today if no filters
                 $query->whereDate('marked_at', Carbon::today());
            } else {
                 if ($startDate) {
                     $query->whereDate('marked_at', '>=', $startDate);
                 }
                 if ($endDate) {
                     $query->whereDate('marked_at', '<=', $endDate);
                 }
            }

            $records = $query->orderByDesc('marked_at')->paginate(25);
        }

        // Data for filters
        $courses = $student ? Course::whereHas('schedules', function($q) use ($student) {
            $q->whereHas('attendanceRecords', fn($sq) => $sq->where('student_id', $student->id));
        })->orderBy('name')->get() : collect();

        $semesters = AcademicSemester::orderByDesc('start_date')->get();
        $years = [1, 2, 3, 4];

        return view('admin.reports.individual', [
            'student' => $student,
            'records' => $records,
            'students' => Student::with('group')->orderBy('id')->limit(200)->get(),
            'courses' => $courses,
            'semesters' => $semesters,
            'years' => $years
        ]);
    }

    public function absenteeism(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfWeek()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->endOfWeek()->toDateString()));
        $threshold = (int)($request->input('threshold', 3));

        $students = Student::with('group')->get();

        // Optimize: Get expected schedule counts
        $groupScheduleCounts = Schedule::whereBetween('start_at', [$start, $end])
            ->where('is_cancelled', false)
            ->whereNotNull('group_id')
            ->select('group_id', DB::raw('count(*) as total'))
            ->groupBy('group_id')
            ->pluck('total', 'group_id');

        $patterns = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();

            $late = $records->where('status', 'late')->count();
            $recordedAbsent = $records->where('status', 'absent')->count();
            $present = $records->where('status', 'present')->count();
            $excused = $records->where('status', 'excused')->count();

            $expected = $groupScheduleCounts[$student->group_id] ?? 0;
            $attended = $present + $late + $excused;

            $calculatedAbsent = max(0, $expected - $attended);
            $absent = max($recordedAbsent, $calculatedAbsent);

            $flag = ($late >= $threshold || $absent >= $threshold);

            if ($late > 0 || $absent > 0) {
                $patterns[] = compact('student', 'late', 'absent', 'flag');
            }
        }

        return view('admin.reports.absenteeism', compact('patterns', 'start', 'end', 'threshold'));
    }

    public function devices(Request $request)
    {
        // If future device/source logging exists, list raw attendance with lat/lng and selfie
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfMonth()->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end', Carbon::today()->toDateString()))->endOfDay();

        $records = Attendance::with(['student', 'schedule.course'])
            ->whereBetween('marked_at', [$start, $end])
            ->where(function($q) {
                // Show Present/Late/Excused OR any record with location data
                $q->whereIn('status', ['present', 'late', 'excused'])
                  ->orWhereNotNull('lat');
            })
            ->orderByDesc('marked_at')
            ->paginate(25);

        return view('admin.reports.devices', compact('records', 'start', 'end'));
    }

    // --- Advanced Reporting Methods ---

    private function getActiveSemester($reqId = null)
    {
        if ($reqId) {
            return AcademicSemester::find($reqId);
        }
        return AcademicSemester::active()->first() ?? AcademicSemester::orderByDesc('start_date')->first();
    }

    /**
     * Course Attendance Report - IMPROVED
     * Shows attendance statistics per course with 70% threshold view
     */
    public function course(Request $request)
    {
        $semesters = AcademicSemester::orderByDesc('start_date')->get();
        $semester = $this->getActiveSemester($request->input('semester_id'));

        $selectedCourse = null;
        $stats = [];
        $breakdown = [];
        $studentsAboveThreshold = [];
        $studentsBelowThreshold = [];

        if ($request->has('course_id')) {
            $selectedCourse = Course::find($request->input('course_id'));

            if ($selectedCourse && $semester) {
                // Get schedules for this course in this semester
                $schedulesQuery = Schedule::where('course_id', $selectedCourse->id)
                    ->where('academic_semester_id', $semester->id)
                    ->where('is_cancelled', false);

                // Apply filters
                if ($campusId = $request->input('campus_id')) {
                    $schedulesQuery->whereHas('group.students.enrollments', fn($q) => $q->where('campus_id', $campusId));
                }
                if ($facultyId = $request->input('faculty_id')) {
                    $schedulesQuery->whereHas('group.students.enrollments.program.department.faculty', fn($q) => $q->where('id', $facultyId));
                }
                if ($departmentId = $request->input('department_id')) {
                    $schedulesQuery->whereHas('group.students.enrollments.program.department', fn($q) => $q->where('id', $departmentId));
                }
                if ($programId = $request->input('program_id')) {
                    $schedulesQuery->whereHas('group.students.enrollments.program', fn($q) => $q->where('id', $programId));
                }
                if ($year = $request->input('year_of_study')) {
                    $schedulesQuery->whereHas('group.students.enrollments', fn($q) => $q->where('year_of_study', $year));
                }
                if ($groupId = $request->input('group_id')) {
                    $schedulesQuery->where('group_id', $groupId);
                }

                $schedules = $schedulesQuery->with('group')->get();
                $totalClasses = $schedules->count();

                // Get all attendances for these schedules
                $attendances = Attendance::whereIn('schedule_id', $schedules->pluck('id'))->get();

                // Breakdown by Group
                $groupIds = $schedules->pluck('group_id')->unique()->filter();

                foreach ($groupIds as $gid) {
                    $grp = Group::find($gid);
                    if (!$grp) continue;

                    $gScheds = $schedules->where('group_id', $gid);
                    $gSchedCount = $gScheds->count();
                    $gAtt = Attendance::whereIn('schedule_id', $gScheds->pluck('id'))->get();

                    $present = $gAtt->where('status', 'present')->count();
                    $late = $gAtt->where('status', 'late')->count();
                    $attended = $present + $late;

                    // Expected = Students in Group × Classes Held
                    $studentCount = Student::where('group_id', $gid)->count();
                    $expectedTotal = $studentCount * $gSchedCount;

                    $rate = $this->calculateAttendancePercentage($attended, $expectedTotal);

                    $breakdown[] = [
                        'group' => $grp,
                        'classes' => $gSchedCount,
                        'students' => $studentCount,
                        'expected' => $expectedTotal,
                        'attended' => $attended,
                        'rate' => $rate
                    ];
                }

                // Student-level breakdown with 70% threshold
                $participatingGroupIds = $groupIds->values();
                $allStudentsQuery = Student::whereIn('group_id', $participatingGroupIds)
                    ->with(['group', 'enrollments.program']);

                // Apply same filters to students
                if ($campusId = $request->input('campus_id')) {
                    $allStudentsQuery->whereHas('enrollments', fn($q) => $q->where('campus_id', $campusId));
                }
                if ($facultyId = $request->input('faculty_id')) {
                    $allStudentsQuery->whereHas('enrollments.program.department.faculty', fn($q) => $q->where('id', $facultyId));
                }
                if ($departmentId = $request->input('department_id')) {
                    $allStudentsQuery->whereHas('enrollments.program.department', fn($q) => $q->where('id', $departmentId));
                }
                if ($programId = $request->input('program_id')) {
                    $allStudentsQuery->whereHas('enrollments.program', fn($q) => $q->where('id', $programId));
                }
                if ($year = $request->input('year_of_study')) {
                    $allStudentsQuery->whereHas('enrollments', fn($q) => $q->where('year_of_study', $year));
                }

                $allStudents = $allStudentsQuery->get();
                $groupedRecords = $attendances->groupBy('student_id');

                foreach ($allStudents as $student) {
                    $records = $groupedRecords->get($student->id, collect());

                    $present = $records->where('status', 'present')->count();
                    $late = $records->where('status', 'late')->count();
                    $attended = $present + $late;

                    // Expected = Classes held for this student's group
                    $groupSchedCount = $schedules->where('group_id', $student->group_id)->count();
                    $expected = $groupSchedCount;

                    $percentage = $this->calculateAttendancePercentage($attended, $expected);
                    $meetsThreshold = $percentage >= 70;

                    $studentData = [
                        'student' => $student,
                        'expected' => $expected,
                        'attended' => $attended,
                        'present' => $present,
                        'late' => $late,
                        'percentage' => $percentage,
                        'meets_threshold' => $meetsThreshold,
                    ];

                    if ($meetsThreshold) {
                        $studentsAboveThreshold[] = $studentData;
                    } else {
                        $studentsBelowThreshold[] = $studentData;
                    }
                }

                // Sort both lists by percentage
                usort($studentsAboveThreshold, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
                usort($studentsBelowThreshold, fn($a, $b) => $a['percentage'] <=> $b['percentage']);

                // Overall stats
                $totalStudents = count($studentsAboveThreshold) + count($studentsBelowThreshold);
                $totalExpected = collect($breakdown)->sum('expected');
                $totalAttended = collect($breakdown)->sum('attended');
                $overallRate = $this->calculateAttendancePercentage($totalAttended, $totalExpected);

                $stats = [
                    'total_classes' => $totalClasses,
                    'total_students' => $totalStudents,
                    'students_above_70' => count($studentsAboveThreshold),
                    'students_below_70' => count($studentsBelowThreshold),
                    'total_expected' => $totalExpected,
                    'total_attended' => $totalAttended,
                    'overall_rate' => $overallRate,
                ];

                // CSV Export
                if ($request->input('export') === 'csv') {
                    return $this->exportCourseReportCsv($selectedCourse, $semester, $stats, $studentsAboveThreshold, $studentsBelowThreshold);
                }
            }
        }

        return view('admin.reports.course', array_merge([
            'semesters' => $semesters,
            'semester' => $semester,
            'selectedCourse' => $selectedCourse,
            'stats' => $stats,
            'breakdown' => $breakdown,
            'studentsAboveThreshold' => $studentsAboveThreshold,
            'studentsBelowThreshold' => $studentsBelowThreshold,
        ], $this->getFilterData()));
    }

    public function group(Request $request)
    {
        $semesters = AcademicSemester::orderByDesc('start_date')->get();
        $semester = $this->getActiveSemester($request->input('semester_id'));
        $programs = Program::orderBy('name')->get();

        $selectedProgramId = $request->input('program_id');
        $yearFilter = $request->input('year');

        if($selectedProgramId) {
            $prog = Program::find($selectedProgramId);
            if ($prog) {
                // Find groups via Course -> Schedule -> Group
                // This ensures we show groups that are actually "in" this program's classes
                $courseQuery = $prog->courses();
                if ($yearFilter) {
                    $courseQuery->wherePivot('year_of_study', $yearFilter);
                }
                $cIds = $courseQuery->pluck('courses.id');

                // Get groups from schedules in this semester? Or any semester?
                // Probably better to check current semester to match "Active" context,
                // but user might want to see history.
                // Let's use the selected semester.
                $sIds = Schedule::whereIn('course_id', $cIds)
                    ->where('academic_semester_id', $semester->id)
                    ->pluck('group_id')
                    ->unique();

                $groups = Group::whereIn('id', $sIds)->orderBy('name')->get();

                // Fallback/Augment: Filter by name if the result is empty?
                // Sometimes groups exist but haven't been scheduled yet.
                // If the user wants to Run a report for a group with no schedules, it will be empty anyway.
                // So showing only groups with schedules is a feature, not a bug.
            } else {
                 $groups = collect();
            }
        } else {
            // No program selected, show all groups
            $q = Group::orderBy('name');
            if ($yearFilter) {
                 $q->where(function($query) use ($yearFilter) {
                     $query->where('name', 'LIKE', '%Year ' . $yearFilter . '%')
                           ->orWhere('name', 'LIKE', '%Yr ' . $yearFilter . '%');
                 });
            }
            $groups = $q->get();
        }

        $selectedGroup = null;
        $stats = [];
        $courseBreakdown = [];

        if ($request->has('group_id')) {
            $selectedGroup = Group::find($request->input('group_id'));
            if ($selectedGroup && $semester) {
                // Scheds for this group
                $schedules = Schedule::where('group_id', $selectedGroup->id)
                    ->where('academic_semester_id', $semester->id)
                    ->where('is_cancelled', false)
                    ->pluck('id');

                $totalClasses = $schedules->count();
                $attendances = Attendance::whereIn('schedule_id', $schedules)->get();
                $present = $attendances->where('status', 'present')->count();
                $absent = $attendances->where('status', 'absent')->count();
                $late = $attendances->where('status', 'late')->count();
                $totalAtt = $present + $absent + $late;

                $studentCount = Student::where('group_id', $selectedGroup->id)->count();

                $stats = [
                    'total_classes' => $totalClasses,
                    'present' => $present,
                    // Expected = Students * Classes
                    'rate' => ($studentCount * $totalClasses) > 0
                        ? round(($present / ($studentCount * $totalClasses)) * 100)
                        : 0,
                ];

                // Breakdown by Course
                $courseIds = Schedule::where('group_id', $selectedGroup->id)
                     ->where('academic_semester_id', $semester->id)
                     ->distinct()
                     ->pluck('course_id');

                foreach ($courseIds as $cid) {
                    $crs = Course::find($cid);
                    if (!$crs) continue;
                     $cScheds = Schedule::where('group_id', $selectedGroup->id)
                        ->where('course_id', $cid)
                        ->where('academic_semester_id', $semester->id)
                        ->where('is_cancelled', false)
                        ->pluck('id');

                     $cSchedCount = $cScheds->count();
                     $cAtt = Attendance::whereIn('schedule_id', $cScheds)->get();
                     $p = $cAtt->where('status', 'present')->count();

                     // Expected for this course = Students * Classes for Course
                     $expectedTotal = $studentCount * $cSchedCount;
                     $rate = $expectedTotal > 0 ? round(($p / $expectedTotal) * 100) : 0;

                     $courseBreakdown[] = [
                         'course' => $crs,
                         'classes' => $cSchedCount,
                         'rate' => $rate
                     ];
                }
                if ($request->input('export') === 'csv') {
                    $filename = 'group_report_' . $selectedGroup->name . '_' . now()->format('Ymd_His') . '.csv';
                    return response()->streamDownload(function () use ($courseBreakdown, $selectedGroup, $semester) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, ['Group Report: ' . $selectedGroup->name]);
                        fputcsv($file, ['Semester: ' . $semester->year . ' ' . $semester->semester]);
                        fputcsv($file, []);
                        fputcsv($file, ['Course Name', 'Course Code', 'Classes Held', 'Attendance Rate (%)']);

                        foreach ($courseBreakdown as $row) {
                            fputcsv($file, [
                                $row['course']->name,
                                $row['course']->code,
                                $row['classes'],
                                $row['rate']
                            ]);
                        }
                        fclose($file);
                    }, $filename);
                }
            }
        }

return view('admin.reports.group', compact('semesters', 'semester', 'groups', 'programs', 'selectedGroup', 'stats', 'courseBreakdown', 'selectedProgramId'));
    }

    public function program(Request $request)
    {
        $semesters = AcademicSemester::orderByDesc('start_date')->get();
        $semester = $this->getActiveSemester($request->input('semester_id'));
        $programs = Program::orderBy('name')->get();

        $selectedProgram = null;
        $groupStats = [];

        if ($request->has('program_id')) {
            $selectedProgram = Program::with('groups')->find($request->input('program_id'));
            if ($selectedProgram && $semester) {
                // Determine relevant courses for this program
                $courseQuery = $selectedProgram->courses();

                // Apply Year Filter if present
                if ($request->filled('year')) {
                    $courseQuery->wherePivot('year_of_study', $request->input('year'));
                }

                $courseIds = $courseQuery->pluck('courses.id');

                // Find all schedules for these courses in this semester
                $schedules = Schedule::whereIn('course_id', $courseIds)
                    ->where('academic_semester_id', $semester->id)
                    ->where('is_cancelled', false)
                    ->get();

                // Identify groups that participated
                $groupIds = $schedules->pluck('group_id')->unique();
                $groups = Group::whereIn('id', $groupIds)->orderBy('name')->get();

                foreach ($groups as $group) {
                    $groupSchedules = $schedules->where('group_id', $group->id);
                    $scheduleIds = $groupSchedules->pluck('id');
                    $gSchedCount = $groupSchedules->count();

                    if ($scheduleIds->isEmpty()) continue;

                    $attendances = Attendance::whereIn('schedule_id', $scheduleIds)->get();
                    $present = $attendances->where('status', 'present')->count();
                    $total = $attendances->count(); // Total recorded

                    // Enhanced Rate Calculation:
                    // Expected = StudentsInGroup * ClassesHeld
                    $studentCount = Student::where('group_id', $group->id)->count();
                    $expectedTotal = $studentCount * $gSchedCount;

                    $rate = $expectedTotal > 0 ? round(($present / $expectedTotal) * 100) : 0;

                    $groupStats[] = [
                        'group' => $group,
                        'classes' => $gSchedCount,
                        'rate' => $rate,
                        'total_records' => $expectedTotal // Showing expected total makes more sense with the new rate?
                                                          // Or keep showing "Total Records" as actual marked?
                                                          // Let's show "Total Expected" implicitly via the rate,
                                                          // but maybe keep "total_records" as actual records for debug?
                                                          // Actually, let's update the label in view or just pass expected here.
                                                          // For consistency with other reports, let's pass `total_records` as expected.

                    ];
                }

                if ($request->input('export') === 'csv') {
                    $yearLabel = $request->input('year') ? 'Year ' . $request->input('year') : 'All Years';
                    $filename = 'program_report_' . $selectedProgram->code . '_' . now()->format('Ymd_His') . '.csv';

                    return response()->streamDownload(function () use ($groupStats, $selectedProgram, $semester, $yearLabel) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, ['Program Report: ' . $selectedProgram->name . ' (' . $yearLabel . ')']);
                        fputcsv($file, ['Semester: ' . $semester->year . ' ' . $semester->semester]);
                        fputcsv($file, []);
                        fputcsv($file, ['Group', 'Classes Held', 'Total Records', 'Attendance Rate (%)']);

                        foreach ($groupStats as $row) {
                            fputcsv($file, [
                                $row['group']->name,
                                $row['classes'],
                                $row['total_records'],
                                $row['rate']
                            ]);
                        }
                        fclose($file);
                    }, $filename);
                }

            }
        }

        return view('admin.reports.program', compact('semesters', 'semester', 'programs', 'selectedProgram', 'groupStats'));
    }

    public function session(Request $request, Schedule $schedule)
    {
        $schedule->load(['course', 'group', 'lecturer', 'attendanceRecords.student']);

        $stats = [
            'present' => $schedule->attendanceRecords->where('status', 'present')->count(),
            'absent' => $schedule->attendanceRecords->where('status', 'absent')->count(),
            'late' => $schedule->attendanceRecords->where('status', 'late')->count(),
        ];
        $total = array_sum($stats);
        $stats['rate'] = $total > 0 ? round(($stats['present'] / $total) * 100) : 0;

        if ($request->input('export') === 'csv') {
            $filename = 'session_report_' . $schedule->id . '_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($schedule) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Session Report']);
                fputcsv($file, ['Course: ' . $schedule->course->name]);
                fputcsv($file, ['Group: ' . $schedule->group->name]);
                fputcsv($file, ['Date: ' . $schedule->start_at->toDayDateTimeString()]);
                fputcsv($file, []);
                fputcsv($file, ['Student Name', 'Reg No', 'Status', 'Time In']);

                foreach ($schedule->attendanceRecords as $att) {
                    fputcsv($file, [
                        $att->student->name,
                        $att->student->reg_no,
                        ucfirst($att->status),
                        $att->created_at->format('H:i')
                    ]);
                }
                fclose($file);
            }, $filename);
        }

        return view('admin.reports.session', compact('schedule', 'stats'));
    }

    // Simple CSV export endpoints using stream response
    public function exportDailyCsv(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $rows = Attendance::with(['student', 'schedule.course', 'schedule.group'])
            ->whereDate('marked_at', $date)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="daily-attendance-' . $date . '.csv"'
        ];

        $expected = Schedule::whereDate('start_at', $date)->count();
        $present = Attendance::whereDate('marked_at', $date)->where('status', 'present')->count();
        $absent = Attendance::whereDate('marked_at', $date)->where('status', 'absent')->count();
        $late = Attendance::whereDate('marked_at', $date)->where('status', 'late')->count();
        $rate = ($present + $absent + $late) > 0 ? round(($present / max(($present + $absent + $late), 1)) * 100, 1) : 0;
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $callback = function () use ($rows, $date, $expected, $present, $absent, $late, $rate, $user, $nowEat) {
            $out = fopen('php://output', 'w');
            // Institutional header rows
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Katusome Attendance Management System']);
            fputcsv($out, ['Faculty of Computing and Informatics']);
            fputcsv($out, ['']);
            fputcsv($out, [strtoupper('Daily Attendance Summary')]);
            fputcsv($out, ['Date', Carbon::parse($date)->format('d M Y')]);
            fputcsv($out, ['Generated On', $nowEat->format('d M Y, h:i A') . ' (EAT)']);
            fputcsv($out, ['Generated By', $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System']);
            fputcsv($out, ['']);
            // Summary metrics
            fputcsv($out, ['Total Expected', $expected]);
            fputcsv($out, ['Present', $present]);
            fputcsv($out, ['Late', $late]);
            fputcsv($out, ['Absent', $absent]);
            fputcsv($out, ['Attendance Rate', $rate . '%']);
            fputcsv($out, ['']);
            fputcsv($out, ['Name', 'ID', 'Department', 'Course', 'Time In', 'Status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    optional($r->student)->name,
                    optional($r->student)->student_no ?? optional($r->student)->reg_no,
                    optional($r->schedule->group)->name,
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('Y-m-d H:i'),
                    ucfirst($r->status),
                ]);
            }
            // Footer contact info
            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug', 'https://katusome.ssendi.dev']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportMonthlyCsv(Request $request)
    {
        $month = (int)($request->input('month', Carbon::today()->month));
        $year = (int)($request->input('year', Carbon::today()->year));
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $students = Student::with(['group'])->get();
        $rows = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $present = $records->where('status', 'present')->count();
            $absent = $records->where('status', 'absent')->count();
            $late = $records->where('status', 'late')->count();
            $totalDays = $records->count();
            $percentage = $totalDays > 0 ? round(($present / $totalDays) * 100) : 0;
            $rows[] = [
                'name' => $student->name,
                'group' => optional($student->group)->name,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'percentage' => $percentage
            ];
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="monthly-summary-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv"'
        ];

        $presentTotal = collect($rows)->sum('present');
        $absentTotal = collect($rows)->sum('absent');
        $lateTotal = collect($rows)->sum('late');
        $expectedTotal = collect($rows)->sum(function($r){ return $r['present'] + $r['absent'] + $r['late']; });
        $rateTotal = ($expectedTotal > 0 ? round(($presentTotal / $expectedTotal) * 100, 1) : 0);
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $callback = function () use ($rows, $year, $month, $presentTotal, $absentTotal, $lateTotal, $expectedTotal, $rateTotal, $user, $nowEat) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Katusome Attendance Management System']);
            fputcsv($out, ['Faculty of Computing and Informatics']);
            fputcsv($out, ['']);
            fputcsv($out, [strtoupper('Monthly Attendance Summary')]);
            fputcsv($out, ['Date Range', Carbon::create($year, $month, 1)->startOfMonth()->format('d M Y') . ' – ' . Carbon::create($year, $month, 1)->endOfMonth()->format('d M Y')]);
            fputcsv($out, ['Generated On', $nowEat->format('d M Y, h:i A') . ' (EAT)']);
            fputcsv($out, ['Generated By', $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System']);
            fputcsv($out, ['']);
            fputcsv($out, ['Total Expected', $expectedTotal]);
            fputcsv($out, ['Present', $presentTotal]);
            fputcsv($out, ['Late', $lateTotal]);
            fputcsv($out, ['Absent', $absentTotal]);
            fputcsv($out, ['Attendance Rate', $rateTotal . '%']);
            fputcsv($out, ['']);
            fputcsv($out, ['Name', 'Group', 'Present', 'Absent', 'Late', 'Attendance %']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['name'], $r['group'], $r['present'], $r['absent'], $r['late'], $r['percentage']]);
            }
            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug', 'https://katusome.ssendi.dev']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportIndividualCsv(Request $request)
    {
        $studentId = $request->input('student_id');
        $student = $studentId ? Student::with('group')->find($studentId) : null;
        if (!$student) {
            return response('Student not found', 404);
        }
        $records = Attendance::with(['schedule.course'])
            ->where('student_id', $student->id)
            ->orderBy('marked_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="individual-' . $student->id . '.csv"'
        ];
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();
        $totalDays = $records->count();
        $rate = $totalDays > 0 ? round(($present / $totalDays) * 100, 1) : 0;
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $callback = function () use ($records, $student, $present, $absent, $late, $totalDays, $rate, $user, $nowEat) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Katusome Attendance Management System']);
            fputcsv($out, ['Faculty of Computing and Informatics']);
            fputcsv($out, ['']);
            fputcsv($out, [strtoupper('Individual Attendance Report')]);
            fputcsv($out, ['Student', $student->name]);
            fputcsv($out, ['Group', optional($student->group)->name]);
            fputcsv($out, ['Generated On', $nowEat->format('d M Y, h:i A') . ' (EAT)']);
            fputcsv($out, ['Generated By', $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System']);
            fputcsv($out, ['']);
            fputcsv($out, ['Total Expected', $totalDays]);
            fputcsv($out, ['Present', $present]);
            fputcsv($out, ['Late', $late]);
            fputcsv($out, ['Absent', $absent]);
            fputcsv($out, ['Attendance Rate', $rate . '%']);
            fputcsv($out, ['']);
            fputcsv($out, ['Date', 'Course', 'Time In', 'Status']);
            foreach ($records as $r) {
                fputcsv($out, [
                    optional($r->marked_at)?->format('Y-m-d'),
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('H:i'),
                    ucfirst($r->status),
                ]);
            }
            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug', 'https://katusome.ssendi.dev']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportAbsenteeismCsv(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfWeek()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->endOfWeek()->toDateString()));
        $threshold = (int)($request->input('threshold', 3));
        $students = Student::with('group')->get();
        $rows = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $late = $records->where('status', 'late')->count();
            $absent = $records->where('status', 'absent')->count();
            $flag = ($late >= $threshold || $absent >= $threshold) ? 'Flagged' : 'OK';
            if ($late > 0 || $absent > 0) {
                $rows[] = [
                    'name' => $student->name,
                    'group' => optional($student->group)->name,
                    'late' => $late,
                    'absent' => $absent,
                    'flag' => $flag
                ];
            }
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="absenteeism-' . $start->toDateString() . '-to-' . $end->toDateString() . '.csv"'
        ];
        $presentTotal = collect($rows)->sum('present');
        $absentTotal = collect($rows)->sum('absent');
        $lateTotal = collect($rows)->sum('late');
        $expectedTotal = $presentTotal + $absentTotal; // late overlaps; include separately
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $callback = function () use ($rows, $start, $end, $presentTotal, $absentTotal, $lateTotal, $expectedTotal, $user, $nowEat) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Katusome Attendance Management System']);
            fputcsv($out, ['Faculty of Computing and Informatics']);
            fputcsv($out, ['']);
            fputcsv($out, [strtoupper('Absenteeism & Lateness Report')]);
            fputcsv($out, ['Date Range', $start->format('d M Y') . ' – ' . $end->format('d M Y')]);
            fputcsv($out, ['Generated On', $nowEat->format('d M Y, h:i A') . ' (EAT)']);
            fputcsv($out, ['Generated By', $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System']);
            fputcsv($out, ['']);
            fputcsv($out, ['Total Expected', $expectedTotal]);
            fputcsv($out, ['Present', $presentTotal]);
            fputcsv($out, ['Late', $lateTotal]);
            fputcsv($out, ['Absent', $absentTotal]);
            fputcsv($out, ['']);
            fputcsv($out, ['Student', 'Group', 'Late Count', 'Absences', 'Flag']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['name'], $r['group'], $r['late'], $r['absent'], $r['flag']]);
            }
            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug', 'https://katusome.ssendi.dev']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportDevicesCsv(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfMonth()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->toDateString()));
        $records = Attendance::with(['student', 'schedule.course'])
            ->whereBetween('marked_at', [$start, $end])
            ->orderBy('marked_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="device-logs-' . $start->toDateString() . '-to-' . $end->toDateString() . '.csv"'
        ];
        $present = $records->where('status', 'present')->count();
        $absent = $records->where('status', 'absent')->count();
        $late = $records->where('status', 'late')->count();
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $callback = function () use ($records, $start, $end, $present, $absent, $late, $user, $nowEat) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Katusome Attendance Management System']);
            fputcsv($out, ['Faculty of Computing and Informatics']);
            fputcsv($out, ['']);
            fputcsv($out, [strtoupper('Device / Source Logs')]);
            fputcsv($out, ['Date Range', $start->format('d M Y') . ' – ' . $end->format('d M Y')]);
            fputcsv($out, ['Generated On', $nowEat->format('d M Y, h:i A') . ' (EAT)']);
            fputcsv($out, ['Generated By', $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System']);
            fputcsv($out, ['']);
            fputcsv($out, ['Present', $present]);
            fputcsv($out, ['Late', $late]);
            fputcsv($out, ['Absent', $absent]);
            fputcsv($out, ['']);
            fputcsv($out, ['User', 'Course', 'Timestamp', 'Status', 'Lat', 'Lng', 'Selfie']);
            foreach ($records as $r) {
                fputcsv($out, [
                    optional($r->student)->name,
                    optional($r->schedule->course)->name,
                    optional($r->marked_at)?->format('Y-m-d H:i'),
                    ucfirst($r->status),
                    $r->lat,
                    $r->lng,
                    $r->selfie_path,
                ]);
            }
            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug', 'https://katusome.ssendi.dev']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Schedule Attendance to PDF
     */
    protected function exportScheduleAttendancePdf($schedule, $studentList, $stats)
    {
        // For now, return a simple CSV until PDF library is configured
        $filename = 'schedule_attendance_' . $schedule->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($schedule, $studentList, $stats) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Real-Time Schedule Attendance Report']);
            fputcsv($out, ['']);
            fputcsv($out, ['Course', $schedule->course->name . ' (' . $schedule->course->code . ')']);
            fputcsv($out, ['Group', $schedule->group->name ?? 'N/A']);
            fputcsv($out, ['Lecturer', $schedule->lecturer->name ?? 'N/A']);
            fputcsv($out, ['Date & Time', $schedule->start_at->format('d M Y, H:i') . ' - ' . $schedule->end_at->format('H:i')]);
            fputcsv($out, ['Venue', $schedule->location]);
            fputcsv($out, ['']);

            // Summary
            fputcsv($out, ['Summary Statistics']);
            fputcsv($out, ['Expected Students', $stats['expected']]);
            fputcsv($out, ['Checked In', $stats['checked_in']]);
            fputcsv($out, ['Not Checked In', $stats['not_checked_in']]);
            fputcsv($out, ['Present', $stats['present']]);
            fputcsv($out, ['Late', $stats['late']]);
            fputcsv($out, ['Attendance Rate', $stats['attendance_rate'] . '%']);
            if ($stats['avg_check_in_time'] !== null) {
                fputcsv($out, ['Avg Check-in Time', $stats['avg_check_in_time'] . ' minutes after start']);
            }
            fputcsv($out, ['']);

            // Student List
            fputcsv($out, ['Student Name', 'Reg No', 'Status', 'Check-in Time', 'Has Selfie', 'Clocked Out']);

            foreach ($studentList as $item) {
                fputcsv($out, [
                    $item['student']->name,
                    $item['student']->reg_no ?? $item['student']->student_no,
                    ucfirst(str_replace('_', ' ', $item['status'])),
                    $item['checked_in_at'] ? $item['checked_in_at']->format('H:i') : '—',
                    $item['has_selfie'] ? 'Yes' : 'No',
                    $item['clocked_out'] ? 'Yes' : 'No',
                ]);
            }

            fputcsv($out, ['']);
            fputcsv($out, ['Generated On', now()->format('d M Y, h:i A')]);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug']);

            fclose($out);
        }, $filename);
    }

    /**
     * Export Course Report to CSV
     */
    protected function exportCourseReportCsv($course, $semester, $stats, $studentsAbove, $studentsBelow)
    {
        $filename = 'course_report_' . $course->code . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($course, $semester, $stats, $studentsAbove, $studentsBelow) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, ['Makerere University Business School']);
            fputcsv($out, ['Course Attendance Report']);
            fputcsv($out, ['']);
            fputcsv($out, ['Course', $course->name . ' (' . $course->code . ')']);
            fputcsv($out, ['Semester', $semester->year . ' ' . $semester->semester]);
            fputcsv($out, ['Generated On', now()->format('d M Y, h:i A')]);
            fputcsv($out, ['']);

            // Summary
            fputcsv($out, ['Summary Statistics']);
            fputcsv($out, ['Total Classes', $stats['total_classes']]);
            fputcsv($out, ['Total Students', $stats['total_students']]);
            fputcsv($out, ['Students Above 70%', $stats['students_above_70']]);
            fputcsv($out, ['Students Below 70%', $stats['students_below_70']]);
            fputcsv($out, ['Overall Attendance Rate', $stats['overall_rate'] . '%']);
            fputcsv($out, ['']);

            // Students Meeting Threshold
            fputcsv($out, ['STUDENTS MEETING 70% THRESHOLD (' . count($studentsAbove) . ')']);
            fputcsv($out, ['Student Name', 'Reg No', 'Group', 'Expected', 'Attended', 'Attendance %']);

            foreach ($studentsAbove as $item) {
                fputcsv($out, [
                    $item['student']->name,
                    $item['student']->reg_no ?? $item['student']->student_no,
                    $item['student']->group->name ?? 'N/A',
                    $item['expected'],
                    $item['attended'],
                    $item['percentage'] . '%',
                ]);
            }

            fputcsv($out, ['']);

            // Students Below Threshold
            fputcsv($out, ['STUDENTS BELOW 70% THRESHOLD (' . count($studentsBelow) . ') - AT RISK']);
            fputcsv($out, ['Student Name', 'Reg No', 'Group', 'Expected', 'Attended', 'Attendance %']);

            foreach ($studentsBelow as $item) {
                fputcsv($out, [
                    $item['student']->name,
                    $item['student']->reg_no ?? $item['student']->student_no,
                    $item['student']->group->name ?? 'N/A',
                    $item['expected'],
                    $item['attended'],
                    $item['percentage'] . '%',
                ]);
            }

            fputcsv($out, ['']);
            fputcsv($out, ['Makerere University Business School', 'attendance@mubs.ac.ug']);
            fputcsv($out, ['This report is system-generated. Unauthorized distribution is prohibited.']);

            fclose($out);
        }, $filename);
    }

    // API endpoints for cascading filters
    public function getFacultiesByCampus(Request $request)
    {
        $campusId = $request->input('campus_id');
        $faculties = Faculty::whereHas('departments.campus', fn($q) => $q->where('id', $campusId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($faculties);
    }

    public function getDepartmentsByFaculty(Request $request)
    {
        $facultyId = $request->input('faculty_id');
        $departments = Department::where('faculty_id', $facultyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($departments);
    }

    public function getProgramsByDepartment(Request $request)
    {
        $departmentId = $request->input('department_id');
        $programs = Program::where('department_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($programs);
    }

    public function getCoursesByProgram(Request $request)
    {
        $programId = $request->input('program_id');
        $yearOfStudy = $request->input('year_of_study');

        $query = Course::whereHas('programs', function($q) use ($programId, $yearOfStudy) {
            $q->where('programs.id', $programId);
            if ($yearOfStudy) {
                $q->where('course_program.year_of_study', $yearOfStudy);
            }
        })->orderBy('code');

        $courses = $query->get(['id', 'name', 'code']);

        return response()->json($courses);
    }

    public function getGroupsByProgram(Request $request)
    {
        $programId = $request->input('program_id');
        $yearOfStudy = $request->input('year_of_study');

        $query = Group::whereHas('students.enrollments.program', fn($q) => $q->where('id', $programId));

        if ($yearOfStudy) {
            $query->whereHas('students.enrollments', fn($q) => $q->where('year_of_study', $yearOfStudy));
        }

        $groups = $query->distinct()->orderBy('name')->get(['id', 'name']);

        return response()->json($groups);
    }
}
