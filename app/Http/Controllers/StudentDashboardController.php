<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access the dashboard.']);
        }

        $today = Carbon::today();
        $schedulesToday = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

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

        // Courses for student's group
        $courseIds = Schedule::where('group_id', $student->group_id)
            ->pluck('course_id')->filter()->unique()->values();

        $allSchedules = Schedule::where('group_id', $student->group_id)
            ->whereIn('course_id', $courseIds)
            ->orderBy('start_at')
            ->get();

        $attendanceBySchedule = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get()
            ->groupBy('schedule_id');

        $courseMetrics = [];
        $totalMinutes = 0;
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
                    if ($sch->start_at && $sch->end_at) {
                        $courseMinutes += $sch->start_at->diffInMinutes($sch->end_at);
                    }
                }
            }
            $totalMinutes += $courseMinutes;

            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;
            $courseName = optional($courseSchedules->first()?->course)->name ?? '—';
            $courseMetrics[] = [
                'course_id' => $courseId,
                'course_name' => $courseName,
                'attended_count' => $attendedCount,
                'taught_count' => $totalSessions,
                'progress_percent' => $percent,
                'time_iso' => self::minutesToIsoDuration($courseMinutes),
                'time_minutes' => $courseMinutes,
            ];
        }

        // Sort for chart by percent desc
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
                    'time' => self::minutesToHuman($m['time_minutes']),
                ];
            })
            ->all();

        // Weekly attended classes (present/late) within current week
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weeklyAttendances = Attendance::where('student_id', $student->id)
            ->whereBetween('marked_at', [$weekStart, $weekEnd])
            ->whereIn('status', ['present', 'late'])
            ->orderBy('marked_at')
            ->get();

        $weeklyScheduleMap = Schedule::with('course')
            ->whereIn('id', $weeklyAttendances->pluck('schedule_id'))
            ->get()
            ->keyBy('id');

        $weeklyAttended = $weeklyAttendances->map(function ($att) use ($weeklyScheduleMap) {
            $sch = $weeklyScheduleMap->get($att->schedule_id);
            $courseName = optional(optional($sch)->course)->name ?? '—';
            return [
                'name' => $courseName,
                'date' => $att->marked_at ? $att->marked_at->format('D, M j') : '—',
                'time' => $att->marked_at ? $att->marked_at->format('h:i A') : '—',
            ];
        })->values()->all();

        $weekLabel = $weekStart->format('M j') . ' – ' . $weekEnd->format('M j');

        // Monthly summary: attended, missed, upcoming
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $attendedMonth = Attendance::where('student_id', $student->id)
            ->whereBetween('marked_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['present', 'late'])
            ->count();
        $missedMonth = Attendance::where('student_id', $student->id)
            ->whereBetween('marked_at', [$monthStart, $monthEnd])
            ->where('status', 'absent')
            ->count();
        $upcomingMonth = Schedule::where('group_id', $student->group_id)
            ->whereBetween('start_at', [Carbon::now(), $monthEnd])
            ->count();

        $metrics = [
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'timeSpentTotalHours' => (int) floor($totalMinutes / 60),
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
            'schedules' => $schedulesToday,
            'attendanceBySchedule' => $attendanceByScheduleToday,
            'metrics' => $metrics,
        ]);
    }

    public function coursesJson()
    {
        $user = Auth::user();
        $student = optional($user)->student;
        if (!$student) {
            return response()->json(['data' => []]);
        }

        $courseIds = Schedule::where('group_id', $student->group_id)
            ->pluck('course_id')->filter()->unique()->values();

        $allSchedules = Schedule::where('group_id', $student->group_id)
            ->whereIn('course_id', $courseIds)
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
                    if ($sch->start_at && $sch->end_at) {
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
}