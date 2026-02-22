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

        if ($request->filled('academic_semester_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('academic_semester_id', $request->integer('academic_semester_id')));
        }
        if ($request->filled('program_id')) {
            // Filter by program via course
            $query->whereHas('schedule.course.programs', fn($q) => $q->where('programs.id', $request->integer('program_id')));
        }
        if ($request->filled('year_of_study')) {
            $year = $request->integer('year_of_study');
            // Filter by Student's Year of Study
            $query->whereHas('student', fn($q) => $q->where('year_of_study', $year));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
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
            // Include $hasPivot inside closure to avoid undefined variable errors
            $query->where(function ($q) use ($term, $hasPivot) {
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

        $perPage = $request->integer('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100, 200, 300, 500, 700])) {
            $perPage = 20;
        }

        $attendances = $query->orderByDesc('marked_at')->paginate($perPage)->appends($request->query());

        // Dropdown sources (dynamic)
        $programs = \App\Models\Program::orderBy('name')->get();
        $semesters = \App\Models\AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();

        $coursesQuery = \App\Models\Course::query();
        if ($request->filled('program_id')) {
             $coursesQuery->whereHas('programs', fn($q) => $q->where('programs.id', $request->integer('program_id')));
        }
        $courses = $coursesQuery->orderBy('name')->get();

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
                return view('admin.attendance.partials.filters', compact('courses', 'groups', 'lecturers', 'programs', 'semesters'));
            }
            return view('admin.attendance.partials.table', compact('attendances'));
        }
        // ... (JSON export block skipped for brevity, assumed safe to leave as is for now) ...
        return view('admin.attendance.index', compact('attendances', 'courses', 'groups', 'lecturers', 'programs', 'semesters'));
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
                'columns' => ['Student', 'Course', 'Group', 'Lecturer', 'Status', 'Marked At', 'Clock Out', 'Location', 'Device Info'],
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
                        $r->clock_out_time ? $r->clock_out_time->format('Y-m-d H:i') : ($r->is_auto_clocked_out ? 'Auto' : '—'),
                        $loc,
                        ($r->ip_address ?? '—') . ' (' . ($r->platform ? ucfirst($r->platform) : 'Web') . ')',
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
        return view('admin.attendance.index', compact('attendances', 'courses', 'groups', 'lecturers', 'programs', 'semesters'));
    }

    public function create(Request $request)
    {
        $date = $request->input('date') ?: now()->toDateString();
        $programs = \App\Models\Program::orderBy('name')->get();
        $groups = \App\Models\Group::orderBy('name')->get();

        // Filter courses by program AND year if selected
        $coursesQuery = \App\Models\Course::orderBy('name');

        if ($request->filled('program_id')) {
            $programId = $request->integer('program_id');
            // If year is also selected, filter by pivot column year_of_study
            if ($request->filled('year')) {
                $year = $request->integer('year');
                $coursesQuery->whereHas('programs', function($q) use ($programId, $year) {
                    $q->where('programs.id', $programId)
                      ->where('course_program.year_of_study', $year);
                });
            } else {
                // Just filter by program
                $coursesQuery->whereHas('programs', fn($q) => $q->where('programs.id', $programId));
            }
        }

        $courses = $coursesQuery->get();

        // Get years of study (1-5 typical range)
        $years = [1, 2, 3, 4, 5];

        $scheduleQuery = Schedule::with(['course', 'group', 'lecturer'])
            ->orderByDesc('start_at');
        if ($request->filled('course_id')) {
            $scheduleQuery->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $scheduleQuery->where('group_id', $request->integer('group_id'));
        }

        // Filter schedules by program/year via the course relationship
        if ($request->filled('program_id')) {
            $programId = $request->integer('program_id');
            if ($request->filled('year')) {
                $year = $request->integer('year');
                $scheduleQuery->whereHas('course.programs', function($q) use ($programId, $year) {
                    $q->where('programs.id', $programId)
                      ->where('course_program.year_of_study', $year);
                });
            } else {
                $scheduleQuery->whereHas('course.programs', fn($q) => $q->where('programs.id', $programId));
            }
        }

        if ($date) {
            $scheduleQuery->whereDate('start_at', $date);
        }
        $schedules = $scheduleQuery->limit(200)->get();

        return view('admin.attendance.create', compact('schedules', 'courses', 'groups', 'programs', 'years', 'date'));
    }

    public function store(Request $request)
    {
        // Support both old format (student_ids[] + single status) and new format (statuses[student_id] => status)
        if ($request->has('statuses')) {
            // New format: individual status per student
            $data = $request->validate([
                'schedule_id' => ['required', 'exists:schedules,id'],
                'statuses' => ['required', 'array', 'min:1'],
                'statuses.*' => ['required', 'in:present,late,absent'],
                'marked_at' => ['required', 'date'],
            ]);

            $created = 0;
            $updated = 0;
            foreach ($data['statuses'] as $studentId => $status) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'schedule_id' => $data['schedule_id'],
                        'student_id' => (int) $studentId,
                    ],
                    [
                        'status' => $status,
                        'marked_at' => Carbon::parse($data['marked_at']),
                    ]
                );
                $attendance->wasRecentlyCreated ? $created++ : $updated++;
            }
        } else {
            // Old format: same status for all selected students
            $data = $request->validate([
                'schedule_id' => ['required', 'exists:schedules,id'],
                'student_ids' => ['required', 'array', 'min:1'],
                'student_ids.*' => ['required', 'exists:students,id'],
                'status' => ['required', 'in:present,late,absent'],
                'marked_at' => ['required', 'date'],
                'lat' => ['nullable', 'numeric'],
                'lng' => ['nullable', 'numeric'],
            ]);

            $created = 0;
            $updated = 0;
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
                $attendance->wasRecentlyCreated ? $created++ : $updated++;
            }
        }

        $msg = 'Attendance saved: ' . $created . ' created' . ($updated ? (', ' . $updated . ' updated') : '') . '.';
        return redirect()->route('admin.attendance.index')->with('success', $msg);
    }

    // ... skipping store method ...

    /**
     * AJAX: Get students for a given schedule (by group)
     */
    public function students(Request $request)
    {
        $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
        ]);

        $schedule = Schedule::with(['group', 'course'])->findOrFail($request->schedule_id);

        // Logic fix: Ensure we get students who are explicitly in this group
        // If the group is "Year 3", we want students where group_id = Year 3's ID
        // The previous logic `whereHas('groups'...)` assumed a many-to-many which might not be the primary way students are assigned.
        // Student model shows `group()` belongsTo relationship.

        $groupId = $schedule->group_id;

        // Get students in this group
        $students = Student::where('group_id', $groupId)
            ->with('user')
            ->get()
            ->sortBy(fn($s) => optional($s->user)->name ?? $s->student_no)
            ->values();

        // Get existing attendance for this schedule
        $existing = Attendance::where('schedule_id', $schedule->id)
            ->get()
            ->keyBy('student_id');

        $result = $students->map(function ($student) use ($existing) {
            $existingRec = $existing->get($student->id);
            return [
                'id' => $student->id,
                'name' => optional($student->user)->name ?? 'Unknown',
                'student_no' => $student->student_no,
                'reg_no' => $student->reg_no ?? '—',
                'status' => $existingRec?->status ?? 'present', // default to present
                'has_existing' => $existingRec !== null,
            ];
        });

        return response()->json([
            'schedule' => [
                'id' => $schedule->id,
                'course' => optional($schedule->course)->name,
                'group' => optional($schedule->group)->name,
                'start_at' => $schedule->start_at?->format('Y-m-d H:i'),
            ],
            'students' => $result,
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:present,late,absent,excused'],
            'marked_at' => ['required', 'date'],
            'clock_out_time' => ['nullable', 'date'],
        ]);

        // If user manually sets clock out, we could arguably clear the auto-flag
        // But let's just update the fields requested.
        $attendance->update([
            'status' => $validated['status'],
            'marked_at' => Carbon::parse($validated['marked_at']),
            'clock_out_time' => $validated['clock_out_time'] ? Carbon::parse($validated['clock_out_time']) : null,
            // If manual edit happens, it's no longer purely 'auto' clocked out if they change the time?
            // For now, let's leave the flag as is unless explicitly requested to clear it.
        ]);

        return redirect()->route('admin.attendance.index')->with('success', 'Attendance updated successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return redirect()->route('admin.attendance.index')->with('success', 'Attendance record deleted');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'json'], // IDs are sent as JSON string
            'action' => ['required', 'string', 'in:delete,mark_present,mark_late,mark_absent,mark_excused'],
        ]);

        $ids = json_decode($validated['ids'], true);
        if (empty($ids)) {
            return back()->with('error', 'No records selected.');
        }

        $count = count($ids);

        if ($validated['action'] === 'delete') {
            Attendance::whereIn('id', $ids)->delete();
            $msg = "Deleted {$count} attendance records.";
        } elseif (str_starts_with($validated['action'], 'mark_')) {
            $status = str_replace('mark_', '', $validated['action']);
            Attendance::whereIn('id', $ids)->update([
                'status' => $status,
                // If we manually mark, should we reset auto-clock? usage implies manual override.
                'is_auto_clocked_out' => false,
            ]);
            $msg = "Updated status for {$count} records to " . ucfirst($status) . ".";
        }

        return redirect()->route('admin.attendance.index')->with('success', $msg);
    }
}
