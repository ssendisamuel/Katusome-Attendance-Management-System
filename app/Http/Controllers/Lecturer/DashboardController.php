<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->lecturer) {
            abort(403, 'User is not a lecturer');
        }
        $lecturerId = $user->lecturer->id;
        $today = Carbon::today();

        // Helper query to scope schedules to this lecturer
        $scheduleQuery = function() use ($lecturerId) {
            return Schedule::where(function($q) use ($lecturerId) {
                $q->whereHas('course.lecturers', function($sq) use ($lecturerId) {
                    $sq->where('lecturers.id', $lecturerId);
                })
                ->orWhere('lecturer_id', $lecturerId);
            });
        };

        // 1. Entity Counts
        // Courses assigned to lecturer
        $coursesCount = $user->lecturer->courses()->count();

        // Programs related to these courses
        $programsCount = $user->lecturer->courses()
            ->with('programs')
            ->get()
            ->pluck('programs')
            ->flatten()
            ->unique('id')
            ->count();

        // Groups that the lecturer interacts with (via Schedules)
        // This might be heavy if we check all history, let's limit to recent or active semester
        // For now, let's look at unique groups in upcoming or recent schedules
        $groupsCount = $scheduleQuery()->distinct('group_id')->count('group_id');

        // Students: This is tricky. Students in groups that the lecturer teaches?
        // Let's count students in the groups associated with the lecturer's schedules
        // This is an approximation. A more accurate way would be students enrolled in courses this lecturer teaches.
        // But for dashboard speed, let's stick to unique students in attendance records for this lecturer? NO, that misses students who haven't attended.
        // Let's count students in groups that have schedules with this lecturer.
        $groupIds = $scheduleQuery()->distinct('group_id')->pluck('group_id');
        $studentsCount = Student::whereIn('group_id', $groupIds)->count();


        // 2. Today's Overview
        $todaysClasses = $scheduleQuery()->whereDate('start_at', $today)->count();

        // Attendance stats for today (scoped to lecturer's schedules)
        $todaysScheduleIds = $scheduleQuery()->whereDate('start_at', $today)->pluck('id');

        $presentToday = Attendance::whereIn('schedule_id', $todaysScheduleIds)
            ->where('status', 'present')
            ->count();
        $absentToday = Attendance::whereIn('schedule_id', $todaysScheduleIds)
            ->where('status', 'absent')
            ->count();
        $lateToday = Attendance::whereIn('schedule_id', $todaysScheduleIds)
            ->where('status', 'late')
            ->count();

        $attendanceTotalToday = $presentToday + $absentToday + $lateToday;

        // Rate Today
        $attendanceRateToday = $attendanceTotalToday > 0
            ? (round(($presentToday / max($attendanceTotalToday, 1)) * 100)) . '%'
            : '0%';

        // Overall Attendance Rate (across all time for this lecturer)
        // This can be heavy. Let's optimize or limit time range later if needed.
        $overallTotal = Attendance::whereHas('schedule', function($q) use ($lecturerId) {
            $q->where(function($sq) use ($lecturerId) {
                $sq->whereHas('course.lecturers', fn($ssq) => $ssq->where('lecturers.id', $lecturerId))
                   ->orWhere('lecturer_id', $lecturerId);
            });
        })->count();

        $overallPresent = Attendance::whereHas('schedule', function($q) use ($lecturerId) {
            $q->where(function($sq) use ($lecturerId) {
                $sq->whereHas('course.lecturers', fn($ssq) => $ssq->where('lecturers.id', $lecturerId))
                   ->orWhere('lecturer_id', $lecturerId);
            });
        })->where('status', 'present')->count();

        $attendanceRateOverall = $overallTotal > 0
            ? (round(($overallPresent / max($overallTotal, 1)) * 100)) . '%'
            : '0%';

        // Pending Attendance (Classes passed but not marked)
        $pendingAttendance = $scheduleQuery()
            ->where('start_at', '<', now())
            ->whereDoesntHave('attendanceRecords')
            ->count();


        // 5. Chart Data: Last 7 Days
        $startDate = Carbon::today()->subDays(6);
        $endDate = Carbon::today();

        // We need to filter attendance by schedules belonging to this lecturer
        $chartData = Attendance::select(
                DB::raw('DATE(marked_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count")
            )
            ->whereHas('schedule', function($q) use ($lecturerId) {
                $q->where(function($sq) use ($lecturerId) {
                    $sq->whereHas('course.lecturers', fn($ssq) => $ssq->where('lecturers.id', $lecturerId))
                       ->orWhere('lecturer_id', $lecturerId);
                });
            })
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

        // 6. Recent Classes
        $recentClasses = $scheduleQuery()
            ->with(['course', 'group', 'attendanceRecords'])
            ->where('start_at', '<=', Carbon::now())
            ->orderByDesc('start_at')
            ->take(5)
            ->get();

        // 7. My Courses list (for quick access)
        $myCourses = $user->lecturer->courses()->take(5)->get();

        return view('lecturer.dashboard', compact(
            'studentsCount',
            'coursesCount',
            'programsCount',
            'groupsCount',
            'todaysClasses',
            'attendanceRateToday',
            'attendanceRateOverall',
            'pendingAttendance',
            'presentToday',
            'absentToday',
            'lateToday',
            'chartDates',
            'seriesPresent',
            'seriesAbsent',
            'seriesLate',
            'recentClasses',
            'myCourses'
        ));
    }
}
