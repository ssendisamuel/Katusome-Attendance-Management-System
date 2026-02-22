<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Course;
use App\Models\Group;
use App\Models\Program;
use App\Models\AcademicSemester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LecturerAttendanceController extends Controller
{
    /**
     * Display a listing of the resource (Attendance History).
     */
    public function index(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) abort(403, 'Unauthorized');

        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['schedule.course', 'schedule.group', 'schedule.lecturer', 'student'];
        if ($hasPivot) { $withRels[] = 'schedule.lecturers'; }

        // Base query: scoped to lecturer
        $query = Attendance::with($withRels)
            ->whereHas('schedule', function($q) use ($lecturer, $hasPivot){
                $q->where('lecturer_id', $lecturer->id);
                // Also include schedules where course is assigned to lecturer?
                // Usually attendance history shows what *this* lecturer marked or is responsible for.
                // Strict scope: Schedules assigned to this lecturer (directly or via pivot)
                $q->orWhereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecturer->id));
            });

        // Apply Filters
        if ($request->filled('academic_semester_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('academic_semester_id', $request->integer('academic_semester_id')));
        }
        if ($request->filled('program_id')) {
            $query->whereHas('schedule.course.programs', fn($q) => $q->where('programs.id', $request->integer('program_id')));
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
        if ($request->filled('date')) {
            $query->whereDate('marked_at', $request->input('date'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('student.user', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.course', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('schedule.group', fn($qq) => $qq->where('name', 'like', $term));
            });
        }

        $attendances = $query->orderByDesc('marked_at')->paginate(20)->appends($request->query());

        // Dropdown data (Scoped)
        // Only show programs/courses/groups relevant to this lecturer
        $programs = Program::whereHas('courses.lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))->orderBy('name')->get();
        $semesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();

        // Courses assigned to lecturer
        $courses = $lecturer->courses()->orderBy('name')->get();

        // Groups taking lecturer's courses (simplify: all groups for now, or filter if possible)
        // Filtering groups strictly is complex as groups are linked to courses via schedules or program structure.
        // Let's get groups that have schedules with this lecturer.
        $groupIds = Schedule::where('lecturer_id', $lecturer->id)->pluck('group_id')
            ->merge(Schedule::whereHas('course.lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))->pluck('group_id'))
            ->unique();
        $groups = Group::whereIn('id', $groupIds)->orderBy('name')->get();

        if ($request->ajax()) {
            if ($request->input('fragment') === 'filters') {
                return view('lecturer.attendance.partials.filters', compact('courses', 'groups', 'programs', 'semesters'));
            }
            return view('lecturer.attendance.partials.table', compact('attendances'));
        }

        return view('lecturer.attendance.index', compact('attendances', 'courses', 'groups', 'programs', 'semesters'));
    }

    /**
     * Show "Today's Classes" (The old index view).
     */
    public function today(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) abort(403);

        $today = Carbon::today();
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['course', 'group'];
        // if ($hasPivot) { $withRels[] = 'lecturers'; } // Optimization: lecturer check done in query

        $schedules = Schedule::with($withRels)
            ->whereDate('start_at', $today)
            ->where(function($q) use ($lecturer, $hasPivot){
                $q->whereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecturer->id));
                $q->orWhere('lecturer_id', $lecturer->id);
            })
            ->orderBy('start_at')
            ->paginate(10);

        return view('lecturer.attendance.today', compact('schedules'));
    }

    /**
     * Show the form for creating a new resource (Take Attendance).
     */
    public function create(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) abort(403);

        $date = $request->input('date') ?: now()->toDateString();

        // Scoped Dropdowns
        $programs = Program::whereHas('courses.lecturers', fn($q) => $q->where('lecturers.id', $lecturer->id))
            ->orderBy('name')->get();

        $coursesQuery = $lecturer->courses()->orderBy('name');
        if ($request->filled('program_id')) {
            $programId = $request->integer('program_id');
            if ($request->filled('year')) {
                $year = $request->integer('year');
                $coursesQuery->whereHas('programs', function($q) use ($programId, $year) {
                    $q->where('programs.id', $programId)
                      ->where('course_program.year_of_study', $year);
                });
            } else {
                $coursesQuery->whereHas('programs', fn($q) => $q->where('programs.id', $programId));
            }
        }
        $courses = $coursesQuery->get();

        $groups = Group::orderBy('name')->get(); // Allow all groups, JS will filter schedules anyway
        $years = [1, 2, 3, 4, 5];

        // Fetch eligible schedules
        // Strict scope: Schedules where lecturer is assigned OR course is assigned
        $scheduleQuery = Schedule::with(['course', 'group'])
            ->where(function($q) use ($lecturer){
                $q->where('lecturer_id', $lecturer->id)
                  ->orWhereHas('course.lecturers', fn($sq) => $sq->where('lecturers.id', $lecturer->id));
            })
            ->orderByDesc('start_at');

        if ($request->filled('course_id')) {
            $scheduleQuery->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $scheduleQuery->where('group_id', $request->integer('group_id'));
        }
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

        return view('lecturer.attendance.create', compact('schedules', 'courses', 'groups', 'programs', 'years', 'date'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) abort(403);

        $data = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['required', 'in:present,late,absent'],
            'marked_at' => ['required', 'date'],
        ]);

        $schedule = Schedule::find($data['schedule_id']);

        // Security check
        $isAssigned = $schedule->lecturer_id === $lecturer->id
            || $schedule->course->lecturers->contains('id', $lecturer->id);

        if (!$isAssigned) abort(403, 'You are not assigned to this schedule.');

        $markedAt = Carbon::parse($data['marked_at']);
        $created = 0; $updated = 0;

        foreach ($data['statuses'] as $studentId => $status) {
            $attendance = Attendance::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'student_id' => (int) $studentId,
                ],
                [
                    'status' => $status,
                    'marked_at' => $markedAt,
                ]
            );
            $attendance->wasRecentlyCreated ? $created++ : $updated++;
        }

        // Also update schedule status to completed if not already? Optional.
        // $schedule->update(['status' => 'completed']);

        $msg = 'Attendance saved: ' . $created . ' created' . ($updated ? (', ' . $updated . ' updated') : '') . '.';
        return redirect()->route('lecturer.attendance.index')->with('success', $msg);
    }

    /**
     * AJAX: Get students for a given schedule.
     */
    public function students(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) abort(403);

        $request->validate(['schedule_id' => ['required', 'exists:schedules,id']]);
        $schedule = Schedule::with(['group', 'course'])->findOrFail($request->schedule_id);

        // Security check
        $isAssigned = $schedule->lecturer_id === $lecturer->id
            || $schedule->course->lecturers->contains('id', $lecturer->id);
        if (!$isAssigned) abort(403, 'Unauthorized.');

        $groupId = $schedule->group_id;
        $students = Student::where('group_id', $groupId)
            ->with('user')
            ->get()
            ->sortBy(fn($s) => optional($s->user)->name ?? $s->student_no)
            ->values();

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
                'status' => $existingRec?->status ?? 'present',
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

    // Kept for backward compatibility if needed, marks single schedule via edit form (the old flow)
    public function edit(Schedule $schedule)
    {
        // ... (Old logic, can be deprecated or kept as alternative)
        // Redirecting to new create flow with filters pre-filled might be better,
        // but let's keep it as is since users might click "Mark Attendance" from "Today's Classes".
        return $this->editOld($schedule);
    }

    public function update(Request $request, Schedule $schedule)
    {
       return $this->updateOld($request, $schedule);
    }

    // Private methods to hold old logic to keep file clean
    private function editOld($schedule) {
        $lecturer = optional(auth()->user())->lecturer;
        $isAssigned = $schedule->course->lecturers->contains('id', $lecturer->id)
                      || $schedule->lecturer_id === ($lecturer->id ?? null);
        if (!$lecturer || !$isAssigned) abort(403);

        $students = $schedule->group->students()
            ->whereHas('user')
            ->with('user')
            ->orderBy(DB::raw('(select name from users where users.id = students.user_id)'), 'asc')
            ->get();
        $existing = Attendance::where('schedule_id', $schedule->id)->get()->keyBy('student_id');
        return view('lecturer.attendance.edit', compact('schedule', 'students', 'existing'));
    }

    private function updateOld($request, $schedule) {
         $lecturer = optional(auth()->user())->lecturer;
         if (!$lecturer) abort(403);
         // ... simplified check ...

         $data = $request->validate([
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', 'in:present,absent,late'],
        ]);
        $now = Carbon::now();
        foreach ($data['statuses'] as $studentId => $status) {
            Attendance::updateOrCreate(
                ['schedule_id' => $schedule->id, 'student_id' => (int) $studentId],
                ['status' => $status, 'marked_at' => $now]
            );
        }
        return redirect()->route('lecturer.attendance.today')->with('success', 'Attendance updated.');
    }
}
