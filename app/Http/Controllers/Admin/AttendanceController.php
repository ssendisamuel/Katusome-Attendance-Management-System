<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['schedule.course', 'schedule.group', 'schedule.lecturer', 'student'];
        if ($hasPivot) { $withRels[] = 'schedule.lecturers'; }
        $query = Attendance::with($withRels);

        if ($request->filled('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $request->integer('course_id')));
        }
        if ($request->filled('group_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('group_id', $request->integer('group_id')));
        }
        if ($request->filled('lecturer_id')) {
            $lecId = $request->integer('lecturer_id');
            $query->whereHas('schedule', function($q) use ($lecId, $hasPivot){
                $q->where('lecturer_id', $lecId);
                if ($hasPivot) {
                    $q->orWhereHas('lecturers', fn($qq) => $qq->where('lecturers.id', $lecId));
                }
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('marked_at', $request->input('date'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                // Search by canonical identity via related users
                $q->whereHas('student.user', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.course', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.group', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.lecturer.user', fn($qq) => $qq->where('name', 'like', $term));
                if ($hasPivot) {
                    $q->orWhereHas('schedule.lecturers.user', fn($qq) => $qq->where('name', 'like', $term));
                }
            });
        }

        $attendances = $query->orderByDesc('marked_at')->paginate(20)->appends($request->query());

        // Dropdown sources (dynamic)
        $courses = \App\Models\Course::all();
        if ($request->filled('course_id')) {
            $groupIds = \App\Models\Schedule::where('course_id', $request->integer('course_id'))
                ->pluck('group_id')->filter()->unique()->values();
            $directLecturerIds = \App\Models\Schedule::where('course_id', $request->integer('course_id'))
                ->pluck('lecturer_id')->filter();
            $lecturerIds = collect($directLecturerIds);
            if ($hasPivot) {
                $pivotLecturerIds = \Illuminate\Support\Facades\DB::table('lecturer_schedule')
                    ->join('schedules', 'lecturer_schedule.schedule_id', '=', 'schedules.id')
                    ->where('schedules.course_id', $request->integer('course_id'))
                    ->pluck('lecturer_schedule.lecturer_id');
                $lecturerIds = $lecturerIds->merge($pivotLecturerIds);
            }
            $lecturerIds = $lecturerIds->unique()->values();
            $groups = \App\Models\Group::whereIn('id', $groupIds)->get();
            $lecturers = \App\Models\Lecturer::whereIn('id', $lecturerIds)->get();
        } else {
            $groups = \App\Models\Group::all();
            $lecturers = \App\Models\Lecturer::all();
        }
        if ($request->ajax()) {
            if ($request->input('fragment') === 'filters') {
                return view('admin.attendance.partials.filters', compact('courses', 'groups', 'lecturers'));
            }
            return view('admin.attendance.partials.table', compact('attendances'));
        }
        if ($request->wantsJson() || $request->input('format') === 'json') {
            $rows = $query->orderBy('marked_at')->get();
            $present = $rows->where('status', 'present')->count();
            $late = $rows->where('status', 'late')->count();
            $absent = $rows->where('status', 'absent')->count();
            $total = $present + $late + $absent;
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
            $user = $request->user();
            $firstDate = optional($rows->first())->marked_at;
            $lastDate = optional($rows->last())->marked_at;
            $dateRange = ($firstDate && $lastDate)
                ? ($firstDate->format('d M Y') . ' – ' . $lastDate->format('d M Y'))
                : null;
            $courseName = $request->input('course_id') ? optional(\App\Models\Course::find($request->input('course_id')))->name : null;
            $groupName = $request->input('group_id') ? optional(\App\Models\Group::find($request->input('group_id')))->name : null;
            $lecturerName = $request->input('lecturer_id') ? optional(\App\Models\Lecturer::find($request->input('lecturer_id')))->name : null;
            return response()->json([
                'title' => 'Attendance Records',
                'columns' => ['Student', 'Course', 'Group', 'Lecturer', 'Status', 'Marked At', 'Location'],
                'rows' => $rows->map(function ($r) use ($hasPivot) {
                    $sch = $r->schedule;
                    $names = ($hasPivot && $sch && $sch->relationLoaded('lecturers') && $sch->lecturers && $sch->lecturers->count())
                        ? $sch->lecturers->pluck('name')->implode(', ')
                        : optional($sch->lecturer)->name;
                    $loc = ($r->lat && $r->lng) ? ($r->lat . ', ' . $r->lng) : '—';
                    return [
                        optional($r->student)->name,
                        optional($r->schedule->course)->name,
                        optional($r->schedule->group)->name,
                        $names ?: '—',
                        ucfirst($r->status),
                        optional($r->marked_at)?->format('Y-m-d H:i'),
                        $loc,
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
                    'date_range' => $dateRange,
                    'generated_on' => now('Africa/Kampala')->format('d M Y, h:i A') . ' (EAT)',
                    'generated_by' => $user ? ($user->name . ' (' . ($user->role ?? 'User') . ')') : 'System',
                    'email' => 'attendance@mubs.ac.ug',
                    'website' => 'https://katusome.ssendi.dev',
                    'export_note' => $user && ($user->role === 'Lecturer') ? 'Authorized Lecturer Export.' : 'Official Administrative Copy.'
                ],
                'summary' => [
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'rate' => $rate
                ]
            ]);
        }
        return view('admin.attendance.index', compact('attendances', 'courses', 'groups', 'lecturers'));
    }

    public function create(Request $request)
    {
        $date = $request->input('date') ?: now()->toDateString();
        $courses = \App\Models\Course::all();
        $groups = \App\Models\Group::all();

        $scheduleQuery = Schedule::with(['course', 'group', 'lecturer'])
            ->orderByDesc('start_at');
        if ($request->filled('course_id')) {
            $scheduleQuery->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $scheduleQuery->where('group_id', $request->integer('group_id'));
        }
        if ($date) {
            $scheduleQuery->whereDate('start_at', $date);
        }
        $schedules = $scheduleQuery->limit(200)->get();

        // Order students by related user's name (students.name was dropped)
        $students = Student::leftJoin('users', 'users.id', '=', 'students.user_id')
            ->select('students.*')
            ->orderBy('users.name')
            ->orderBy('students.reg_no')
            ->orderBy('students.student_no')
            ->limit(1000)
            ->get();

        return view('admin.attendance.create', compact('schedules', 'students', 'courses', 'groups', 'date'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'exists:students,id'],
            'status' => ['required', 'in:present,late,absent'],
            'marked_at' => ['required', 'date'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $created = 0; $updated = 0;
        foreach ($data['student_ids'] as $sid) {
            $attendance = Attendance::updateOrCreate(
                [
                    'schedule_id' => $data['schedule_id'],
                    'student_id' => $sid,
                ],
                [
                    'status' => $data['status'],
                    'marked_at' => Carbon::parse($data['marked_at']),
                    'lat' => $data['lat'] ?? null,
                    'lng' => $data['lng'] ?? null,
                    'selfie_path' => null,
                ]
            );
            // Heuristic: if just created vs updated
            $attendance->wasRecentlyCreated ? $created++ : $updated++;
        }

        $msg = 'Attendance saved: ' . $created . ' created' . ($updated ? (', ' . $updated . ' updated') : '') . '.';
        return redirect()->route('admin.attendance.index')->with('success', $msg);
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return redirect()->route('admin.attendance.index')->with('success', 'Attendance record deleted');
    }
}