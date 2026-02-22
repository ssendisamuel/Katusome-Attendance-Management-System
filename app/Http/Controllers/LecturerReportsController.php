<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LecturerReportsController extends Controller
{
    protected function lecturerId(Request $request): ?int
    {
        $user = $request->user();
        return optional($user)->lecturer?->id;
    }

    protected function scopeAttendancesToLecturer(Request $request)
    {
        $lecId = $this->lecturerId($request);
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['schedule.course', 'schedule.group', 'schedule.lecturer', 'student.user'];
        if ($hasPivot) { $withRels[] = 'schedule.lecturers'; }

        $query = Attendance::with($withRels)
            ->whereHas('schedule', function($q) use ($lecId, $hasPivot) {
                // Match logic from LecturerAttendanceController
                $q->where('lecturer_id', $lecId);
                // Schedules where course is assigned to lecturer
                $q->orWhereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecId));
            });

        return [$query, $hasPivot];
    }

    public function dashboard(Request $request)
    {
        return redirect()->route('lecturer.reports.daily');
    }

    public function daily(Request $request)
    {
        [$query, $hasPivot] = $this->scopeAttendancesToLecturer($request);
        $date = $request->input('date', now()->toDateString());

        // Apply filters to the main query
        if ($request->filled('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $request->integer('course_id')));
        }
        if ($request->filled('group_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('group_id', $request->integer('group_id')));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $query->whereDate('marked_at', $date);

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term, $hasPivot) {
                $q->whereHas('student.user', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.course', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.group', fn($qq) => $qq->where('name', 'like', $term));
            });
        }

        // Clone query for stats BEFORE pagination
        $statsQuery = clone $query;
        // removing eager loads for count query optimization
        $statsQuery->getQuery()->eagerLoads = [];

        // We need breakdown by status.
        // Doing 3 separate counts or 1 select raw.
        // Since we might have filters applied, let's just fetch the statuses or do conditional counts.
        $stats = $statsQuery->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = $stats['present'] ?? 0;
        $absent = $stats['absent'] ?? 0;
        $late = $stats['late'] ?? 0;
        $expected = $present + $absent + $late;

        $attendances = $query->orderByDesc('marked_at')->paginate(20)->appends($request->query());

        // Dropdown sources limited to lecturer scope
        $lecId = $this->lecturerId($request);
        // Get courses/groups from schedules relevant to lecturer
        // (Simplified to just checking direct assignment + course assignment for dropdowns)
        $courseIds = Schedule::where(function($q) use ($lecId) {
             $q->where('lecturer_id', $lecId)
               ->orWhereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecId));
            })->pluck('course_id')->filter()->unique();

        $groupIds = Schedule::where(function($q) use ($lecId) {
             $q->where('lecturer_id', $lecId)
               ->orWhereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecId));
            })->pluck('group_id')->filter()->unique();

        $courses = Course::whereIn('id', $courseIds)->orderBy('name')->get();
        $groups = Group::whereIn('id', $groupIds)->orderBy('name')->get();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Daily Attendance',
                'columns' => ['Student', 'Course', 'Group', 'Status', 'Marked At'],
                'rows' => $attendances->map(function($a){
                    return [
                        optional($a->student->user)->name ?? optional($a->student)->name,
                        optional($a->schedule->course)->name,
                        optional($a->schedule->group)->name,
                        $a->status,
                        optional($a->marked_at)?->format('Y-m-d H:i'),
                    ];
                }),
                'summary' => compact('expected','present','absent','late'),
            ]);
        }

        return view('lecturer.reports.daily', compact('attendances','courses','groups','expected','present','absent','late','date'));
    }

    public function monthly(Request $request)
    {
        [$query, $hasPivot] = $this->scopeAttendancesToLecturer($request);
        $month = (int)($request->input('month') ?: now()->month);
        $year = (int)($request->input('year') ?: now()->year);
        $query->whereYear('marked_at', $year)->whereMonth('marked_at', $month);

        $rows = $query->get();
        $byDay = $rows->groupBy(fn($a) => $a->marked_at?->format('Y-m-d'));
        $trend = $byDay->map(function($list){
            return [
                'expected' => $list->count(),
                'present' => $list->where('status','present')->count(),
                'absent' => $list->where('status','absent')->count(),
                'late' => $list->where('status','late')->count(),
            ];
        });

        $summary = [
            'expected' => $rows->count(),
            'present' => $rows->where('status','present')->count(),
            'absent' => $rows->where('status','absent')->count(),
            'late' => $rows->where('status','late')->count(),
        ];

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Monthly Summary',
                'trend' => $trend,
                'summary' => $summary,
                'filters' => compact('month','year'),
            ]);
        }

        return view('lecturer.reports.monthly', compact('month','year','trend','summary'));
    }

    public function individual(Request $request)
    {
        [$query, $hasPivot] = $this->scopeAttendancesToLecturer($request);
        $studentId = $request->integer('student_id');

        // Fallback: If no ID but name is provided, try to find the student
        if (!$studentId && $request->filled('student_name')) {
            $term = trim($request->input('student_name'));
            $found = Student::whereHas('user', fn($q) => $q->where('name', 'like', "%{$term}%"))
                ->orWhere('student_no', 'like', "%{$term}%")
                ->first(); // Just pick the first match
            if ($found) {
                $studentId = $found->id;
                // Merge into request for pagination links to work naturally?
                // Better to just set it for query, pagination might lose it if validation fails but here we are just displaying.
                $request->merge(['student_id' => $studentId]);
            }
        }

        if ($studentId) {
            $query->where('student_id', $studentId);
        } else {
            // Force empty result that works with paginate
            $query->whereRaw('1 = 0');
        }

        // Clone query for stats
        $statsQuery = clone $query;
        $statsQuery->getQuery()->eagerLoads = [];
        $stats = $statsQuery->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'present' => $stats['present'] ?? 0,
            'absent' => $stats['absent'] ?? 0,
            'late' => $stats['late'] ?? 0,
        ];

        $attendances = $query->orderByDesc('marked_at')->paginate(20)->appends($request->query());

        $student = $studentId ? Student::with(['group','user'])->find($studentId) : null;

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Individual Attendance',
                'student' => $student ? [
                    'id' => $student->id,
                    'name' => optional($student->user)->name ?? $student->name,
                    'group' => optional($student->group)->name,
                ] : null,
                'rows' => $attendances->map(function($a){
                    return [
                        'date' => optional($a->marked_at)?->format('Y-m-d'),
                        'time' => optional($a->marked_at)?->format('H:i'),
                        'course' => optional($a->schedule->course)->name,
                        'status' => $a->status,
                    ];
                }),
                'summary' => $summary,
            ]);
        }

        return view('lecturer.reports.individual', compact('student','attendances','summary'));
    }

    public function studentsSearch(Request $request)
    {
        // Suggest students within lecturer's groups
        $lecId = $this->lecturerId($request);
        $groupIds = Schedule::where('lecturer_id', $lecId)->pluck('group_id')->filter()->unique()->values();
        $term = trim($request->input('q', ''));
        $limit = (int)$request->input('limit', 20);
        $query = Student::with('group','user')->whereIn('group_id', $groupIds);
        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function($q) use ($like) {
                $q->whereHas('user', fn($qq) => $qq->where('name', 'like', $like))
                  ->orWhere('student_no', 'like', $like)
                  ->orWhere('reg_no', 'like', $like);
            });
        }
        $students = $query->orderBy('id')->limit($limit)->get();
        return response()->json(
            $students->map(function($s){
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'group' => optional($s->group)->name,
                    'label' => $s->name . (optional($s->group)->name ? (' (' . optional($s->group)->name . ')') : ''),
                ];
            })
        );
    }

    public function exportDailyCsv(Request $request)
    {
        [$query] = $this->scopeAttendancesToLecturer($request);
        if ($request->filled('date')) {
            $query->whereDate('marked_at', $request->input('date'));
        } else {
            $query->whereDate('marked_at', now()->toDateString());
        }
        $rows = $query->orderByDesc('marked_at')->get();
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Student','Course','Group','Status','Marked At']);
        foreach ($rows as $a) {
            fputcsv($csv, [
                optional($a->student->user)->name ?? optional($a->student)->name,
                optional($a->schedule->course)->name,
                optional($a->schedule->group)->name,
                $a->status,
                optional($a->marked_at)?->format('Y-m-d H:i'),
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        $filename = 'lecturer-daily-' . now()->format('Ymd') . '.csv';
        return response($content)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function exportMonthlyCsv(Request $request)
    {
        [$query] = $this->scopeAttendancesToLecturer($request);
        $month = (int)($request->input('month') ?: now()->month);
        $year = (int)($request->input('year') ?: now()->year);
        $query->whereYear('marked_at', $year)->whereMonth('marked_at', $month);
        $rows = $query->orderBy('marked_at')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Date','Expected','Present','Absent','Late']);
        $byDay = $rows->groupBy(fn($a) => $a->marked_at?->format('Y-m-d'));
        foreach ($byDay as $date => $list) {
            fputcsv($csv, [
                $date,
                $list->count(),
                $list->where('status','present')->count(),
                $list->where('status','absent')->count(),
                $list->where('status','late')->count(),
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        $filename = 'lecturer-monthly-' . $year . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '.csv';
        return response($content)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function exportIndividualCsv(Request $request)
    {
        [$query] = $this->scopeAttendancesToLecturer($request);
        $studentId = $request->integer('student_id');
        if ($studentId) { $query->where('student_id', $studentId); }
        $rows = $query->orderByDesc('marked_at')->get();

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Student','Group','Course','Date','Time','Status']);
        foreach ($rows as $a) {
            fputcsv($csv, [
                optional($a->student->user)->name ?? optional($a->student)->name,
                optional($a->student->group)->name,
                optional($a->schedule->course)->name,
                optional($a->marked_at)?->format('Y-m-d'),
                optional($a->marked_at)?->format('H:i'),
                $a->status,
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        $filename = 'lecturer-individual-' . now()->format('Ymd') . '.csv';
        return response($content)->header('Content-Type', 'text/csv')->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
