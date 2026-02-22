<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Course;
use App\Models\Program;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Entity Counts
        $studentsCount = Student::count();
        $coursesCount = Course::count();
        $programsCount = Program::count();
        $groupsCount = Group::count();
        $lecturersCount = Lecturer::count();

        // 2. Today's Overview
        $todaysClasses = Schedule::whereDate('start_at', $today)->count();

        $presentToday = Attendance::whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $absentToday = Attendance::whereDate('marked_at', $today)
            ->where('status', 'absent')
            ->count();
        $lateToday = Attendance::whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();

        $attendanceTotalToday = $presentToday + $absentToday + $lateToday;
        $unmarkedToday = 0; // Placeholder

        // 3. Rates
        $attendanceRateToday = $attendanceTotalToday > 0
            ? (round(($presentToday / max($attendanceTotalToday, 1)) * 100)) . '%'
            : '0%';

        $overallTotal = Attendance::count();
        $overallPresent = Attendance::where('status', 'present')->count();
        $attendanceRateOverall = $overallTotal > 0
            ? (round(($overallPresent / max($overallTotal, 1)) * 100)) . '%'
            : '0%';

        // 4. Pending Attendance
        $pendingAttendance = Schedule::whereDate('start_at', $today)
            ->whereDoesntHave('attendanceRecords', function ($q) use ($today) {
                $q->whereDate('marked_at', $today);
            })
            ->count();

        // 5. Chart Data: Last 7 Days Attendance Trend
        // We want two series: Present vs Absent counts for the last 7 days
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();

        $chartData = Attendance::select(
                DB::raw('DATE(marked_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count")
            )
            ->whereBetween('marked_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartDates = [];
        $seriesPresent = [];
        $seriesAbsent = [];
        $seriesLate = [];

        for ($i = 0; $i <= 6; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $chartDates[] = $startDate->copy()->addDays($i)->format('M d');

            $record = $chartData->get($date);
            $seriesPresent[] = $record ? (int)$record->present_count : 0;
            $seriesAbsent[] = $record ? (int)$record->absent_count : 0;
            $seriesLate[] = $record ? (int)$record->late_count : 0;
        }

        // 6. Recent Classes (last 5 schedules that started today or earlier)
        $recentClasses = Schedule::with(['course', 'lecturer', 'group', 'attendanceRecords'])
            ->where('start_at', '<=', Carbon::now())
            ->orderByDesc('start_at')
            ->take(5)
            ->get();

        // 7. At-Risk Students (Bottom 5 by attendance rate)
        // Two-step process to avoid SQL strict mode GROUP BY issues with students.*
        $atRiskStats = DB::table('attendances')
            ->select('student_id')
            ->selectRaw('count(*) as total_records')
            ->selectRaw("sum(case when status = 'present' then 1 else 0 end) as present_records")
            ->groupBy('student_id')
            ->having('total_records', '>=', 1)
            ->orderByRaw("(sum(case when status = 'present' then 1 else 0 end) / count(*)) ASC")
            ->limit(5)
            ->get();

        $studentIds = $atRiskStats->pluck('student_id');
        $students = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $atRiskStudents = $atRiskStats->map(function ($stat) use ($students) {
            $student = $students->get($stat->student_id);
            if (!$student) return null;

            // Attach stats to the student object manually for the view
            $rate = $stat->total_records > 0
                ? round(($stat->present_records / $stat->total_records) * 100)
                : 0;

            $student->total_records = $stat->total_records;
            $student->present_records = $stat->present_records;
            $student->attendance_rate = $rate;

            return $student;
        })->filter();



        return view('content.dashboards.admin', compact(
            'studentsCount',
            'coursesCount',
            'programsCount',
            'groupsCount',
            'lecturersCount',
            'todaysClasses',
            'attendanceRateToday',
            'attendanceRateOverall',
            'pendingAttendance',
            'presentToday',
            'absentToday',
            'lateToday',
            'unmarkedToday',
            'chartDates',
            'seriesPresent',
            'seriesAbsent',
            'seriesLate',
            'recentClasses',
            'recentClasses',
            'atRiskStudents'
        ));
    }

    public function runAutoClockOut()
    {
        try {
            // Run the job immediately (sync) or push to queue
            // For manual trigger, running sync is often better for immediate feedback,
            // unless it's too heavy.
            // Since we updated logic to handle ALL schedules, it might be heavy.
            // Let's dispatch sync for now to give user 'Done' feedback.
            \App\Jobs\AutoClockOutJob::dispatchSync();

            return redirect()->back()->with('success', 'Auto Clock-Out Job ran successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to run job: ' . $e->getMessage());
        }
    }
}
