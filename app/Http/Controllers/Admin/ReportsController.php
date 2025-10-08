<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function dashboard(Request $request)
    {
        return view('admin.reports.dashboard');
    }

    public function daily(Request $request)
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

        $attendances = $query->orderByDesc('marked_at')->paginate(25);

        // Summary
        $expected = Schedule::whereDate('start_at', $date)->count();
        $present = Attendance::whereDate('marked_at', $date)->where('status', 'present')->count();
        $absent = Attendance::whereDate('marked_at', $date)->where('status', 'absent')->count();
        $late = Attendance::whereDate('marked_at', $date)->where('status', 'late')->count();
        $percentage = ($present + $absent + $late) > 0 ? round(($present / max(($present + $absent + $late), 1)) * 100) : 0;

        return view('admin.reports.daily', [
            'attendances' => $attendances,
            'date' => $date,
            'expected' => $expected,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'percentage' => $percentage,
            'courses' => Course::all(),
            'groups' => Group::all(),
            'lecturers' => Lecturer::all(),
        ]);
    }

    public function monthly(Request $request)
    {
        $month = (int)($request->input('month', Carbon::today()->month));
        $year = (int)($request->input('year', Carbon::today()->year));

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $students = Student::with(['group', 'program'])->get();

        $summary = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $present = $records->where('status', 'present')->count();
            $absent = $records->where('status', 'absent')->count();
            $late = $records->where('status', 'late')->count();
            $totalDays = $records->count();
            $percentage = $totalDays > 0 ? round(($present / $totalDays) * 100) : 0;
            $summary[] = [
                'student' => $student,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'totalDays' => $totalDays,
                'percentage' => $percentage,
            ];
        }

        // Simple department/class aggregates
        $byGroup = collect($summary)->groupBy(fn($row) => optional($row['student']->group)->name)->map(function ($rows) {
            $expected = $rows->sum('totalDays');
            $present = $rows->sum('present');
            $absent = $rows->sum('absent');
            $rate = $expected > 0 ? round(($present / $expected) * 100) : 0;
            return [
                'expected' => $expected,
                'present' => $present,
                'absent' => $absent,
                'rate' => $rate,
            ];
        });

        return view('admin.reports.monthly', [
            'summary' => $summary,
            'byGroup' => $byGroup,
            'month' => $month,
            'year' => $year,
        ]);
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
        $expected = Schedule::whereDate('start_at', $date)->count();
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
        $rows = [];
        $presentTotal = 0; $absentTotal = 0; $lateTotal = 0; $expectedTotal = 0;
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $present = $records->where('status', 'present')->count();
            $absent = $records->where('status', 'absent')->count();
            $late = $records->where('status', 'late')->count();
            $totalDays = $records->count();
            $percentage = $totalDays > 0 ? round(($present / $totalDays) * 100) : 0;
            $presentTotal += $present; $absentTotal += $absent; $lateTotal += $late; $expectedTotal += $totalDays;
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
        $totalDays = $records->count();
        $rate = $totalDays > 0 ? round(($present / $totalDays) * 100, 1) : 0;
        $user = $request->user();
        $nowEat = Carbon::now('Africa/Kampala');
        $start = optional($records->first()->marked_at) ? Carbon::parse($records->first()->marked_at) : Carbon::today()->startOfMonth();
        $end = optional($records->last()->marked_at) ? Carbon::parse($records->last()->marked_at) : Carbon::today();
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
                'expected' => $totalDays,
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
        $records = Attendance::with(['student', 'schedule.course'])
            ->whereBetween('marked_at', [$start, $end])
            ->orderBy('marked_at')
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
                    $r->selfie_path,
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
            $records = Attendance::with(['schedule.course'])
                ->where('student_id', $student->id)
                ->orderByDesc('marked_at')
                ->paginate(25);
        }

        return view('admin.reports.individual', [
            'student' => $student,
            'records' => $records,
            'students' => Student::with('group')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function absenteeism(Request $request)
    {
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfWeek()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->endOfWeek()->toDateString()));
        $threshold = (int)($request->input('threshold', 3));

        $students = Student::with('group')->get();
        $patterns = [];
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->whereBetween('marked_at', [$start, $end])
                ->get();
            $late = $records->where('status', 'late')->count();
            $absent = $records->where('status', 'absent')->count();
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
        $start = Carbon::parse($request->input('start', Carbon::today()->startOfMonth()->toDateString()));
        $end = Carbon::parse($request->input('end', Carbon::today()->toDateString()));
        $records = Attendance::with(['student', 'schedule.course'])
            ->whereBetween('marked_at', [$start, $end])
            ->orderByDesc('marked_at')
            ->paginate(25);
        return view('admin.reports.devices', compact('records', 'start', 'end'));
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
}