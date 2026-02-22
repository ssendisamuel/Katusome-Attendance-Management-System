<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->lecturer) {
            abort(403, 'User is not a lecturer');
        }
        $lecturerId = $user->lecturer->id;

        // Fetch schedules where this lecturer is the main lecturer OR attached via pivot, OR course is assigned
        // The requirement is "manage schedules ... for the courses assigned to them"
        // So we strictly look at courses assigned to the lecturer.
        $assignedCourseIds = $user->lecturer->courses()->pluck('courses.id');

        $query = Schedule::with(['course', 'group', 'lecturers'])
            ->whereIn('course_id', $assignedCourseIds);

        // Filters matching Admin
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('start_at', $request->input('date'));
        }

        $schedules = $query->orderByDesc('start_at')->paginate(15)->appends($request->query());

        // For filters
        $courses = $user->lecturer->courses;
        $groups = \App\Models\Group::all(); // Alternatively filter groups relevant to these courses

        return view('lecturer.schedules.index', compact('schedules', 'courses', 'groups'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        $courses = $user->lecturer->courses;
        $groups = \App\Models\Group::all();
        $semesters = \App\Models\AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        // Allow selecting Series if we implement it
        $series = $user->lecturer->scheduleSeries;

        return view('lecturer.schedules.create', compact('courses', 'groups', 'semesters', 'series'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        $assignedCourseIds = $user->lecturer->courses()->pluck('courses.id')->toArray();

        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id',  function ($attribute, $value, $fail) use ($assignedCourseIds) {
                if (!in_array($value, $assignedCourseIds)) {
                    $fail('You are not assigned to this course.');
                }
            }],
            'group_id' => ['required', 'exists:groups,id'],
            'series_id' => ['nullable', 'exists:schedule_series,id'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'requires_clock_out' => ['nullable', 'boolean'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'is_online' => ['nullable', 'boolean'],
            'access_code' => ['nullable', 'string', 'max:10'],
        ]);

        // Auto-assign self as lecturer
        $data['lecturer_id'] = $user->lecturer->id;

        if (empty($data['academic_semester_id'])) {
            $active = \App\Models\AcademicSemester::where('is_active', true)->value('id');
            if ($active) $data['academic_semester_id'] = $active;
        }

        $schedule = Schedule::create($data); // This sets lecturer_id due to merge or direct assignment

        // Sync pivot if exists? Since we force lecturer_id, maybe pivot is secondary.
        // For simplicity, let's just ensure the schedule is linked to THIS lecturer.

        return redirect()->route('lecturer.schedules.index')->with('success', 'Schedule created');
    }

    public function show(Schedule $schedule)
    {
        $user = Auth::user();
        $lecturerId = $user->lecturer->id;

        $isAssigned = $schedule->course->lecturers->contains('id', $lecturerId)
                      || $schedule->lecturer_id == $lecturerId;

        if (!$isAssigned) abort(403);

        $schedule->load(['course', 'group', 'attendanceRecords.student']);
        return view('lecturer.schedules.show', compact('schedule'));
    }

    public function edit(Schedule $schedule)
    {
        $user = Auth::user();
        $lecturerId = $user->lecturer->id;

        $isAssigned = $schedule->course->lecturers->contains('id', $lecturerId)
                      || $schedule->lecturer_id == $lecturerId;
        if (!$isAssigned) abort(403);

        $courses = $user->lecturer->courses;
        $groups = \App\Models\Group::all();
        $semesters = \App\Models\AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        $series = $user->lecturer->scheduleSeries;

        return view('lecturer.schedules.edit', compact('schedule', 'courses', 'groups', 'semesters', 'series'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $user = Auth::user();
        $lecturerId = $user->lecturer->id;

        $isAssigned = $schedule->course->lecturers->contains('id', $lecturerId)
                      || $schedule->lecturer_id == $lecturerId;
        if (!$isAssigned) abort(403);

        $assignedCourseIds = $user->lecturer->courses()->pluck('courses.id')->toArray();

        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id', function ($attribute, $value, $fail) use ($assignedCourseIds) {
                if (!in_array($value, $assignedCourseIds)) {
                     $fail('You are not assigned to this course.');
                }
            }],
            'group_id' => ['required', 'exists:groups,id'],
            'series_id' => ['nullable', 'exists:schedule_series,id'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'requires_clock_out' => ['nullable', 'boolean'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'is_online' => ['nullable', 'boolean'],
            'access_code' => ['nullable', 'string', 'max:10'],
        ]);

        $schedule->update($data);
        return redirect()->route('lecturer.schedules.index')->with('success', 'Schedule updated');
    }

    public function destroy(Schedule $schedule)
    {
        $user = Auth::user();
        $lecturerId = $user->lecturer->id;

        $isAssigned = $schedule->course->lecturers->contains('id', $lecturerId)
                      || $schedule->lecturer_id == $lecturerId;
        if (!$isAssigned) abort(403, 'Unauthorized to delete this schedule');

        $schedule->delete();
        return redirect()->route('lecturer.schedules.index')->with('success', 'Schedule deleted');
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        $user = Auth::user();
        $lecturerId = $user->lecturer->id;

        $isAssigned = $schedule->course->lecturers->contains('id', $lecturerId)
                      || $schedule->lecturer_id == $lecturerId;
        if (!$isAssigned) abort(403);

        $validated = $request->validate([
            'attendance_status' => 'required|in:scheduled,open,late,closed,cancelled',
            'late_at_minutes' => 'nullable|integer|min:1',
            'requires_clock_out' => 'nullable|boolean',
        ]);

        $status = $validated['attendance_status'];
        $updateData = ['attendance_status' => $status];

        if ($request->has('requires_clock_out')) {
            $updateData['requires_clock_out'] = $request->boolean('requires_clock_out');
        }

        if ($status === 'cancelled') {
             $schedule->update([
                 'is_cancelled' => true,
                 'attendance_status' => 'closed',
             ]);
             return redirect()->back()->with('success', 'Schedule marked as Not Taught.');
        } else {
             $updateData['is_cancelled'] = false;
        }

        if ($status === 'open') {
             if (!$schedule->attendance_open_at) {
                 $updateData['attendance_open_at'] = now();
             }
             if ($request->filled('late_at_minutes')) {
                 $updateData['late_at'] = now()->addMinutes($request->integer('late_at_minutes'));
             }
        } elseif ($status === 'late') {
             $updateData['late_at'] = now();
        }

        $schedule->update($updateData);

        return redirect()->back()->with('success', 'Attendance status updated to ' . ucfirst($status));
    }
}
