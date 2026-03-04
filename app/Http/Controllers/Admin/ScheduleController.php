<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\ScheduleSeries;
use App\Models\AcademicSemester;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auto-Close Past Schedules
        // Update status to 'closed' if end_at is in the past and status is not 'closed' or 'cancelled'
        // This ensures the view reflects the reality even if no cron job ran.
        Schedule::where('end_at', '<', now())
            ->whereNotIn('attendance_status', ['closed', 'cancelled'])
            ->update(['attendance_status' => 'closed']);

        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['course', 'course.lecturers', 'group', 'lecturer', 'series', 'academicSemester', 'course.programs', 'venue'];
        if ($hasPivot) { $withRels[] = 'lecturers'; }
        $query = Schedule::with($withRels);

        // Filters
        if ($request->filled('academic_semester_id')) {
            $query->where('academic_semester_id', $request->integer('academic_semester_id'));
        }

        if ($request->filled('program_id')) {
            // Filter via Course -> Programs relationship
            $progId = $request->integer('program_id');
            $query->whereHas('course.programs', function($q) use ($progId) {
                $q->where('programs.id', $progId);
            });
        }

        if ($request->filled('year_of_study')) {
            // Filter via course_program pivot table's year_of_study
            $yos = $request->integer('year_of_study');
            $query->whereHas('course.programs', function($q) use ($yos) {
                $q->where('course_program.year_of_study', $yos);
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('lecturer_id')) {
            $lecId = $request->integer('lecturer_id');
            $query->where(function($q) use ($lecId, $hasPivot) {
                $q->where('lecturer_id', $lecId);
                if ($hasPivot) {
                    $q->orWhereHas('lecturers', fn($qq) => $qq->where('lecturers.id', $lecId));
                }
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('start_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_at', '<=', $request->input('date_to'));
        }

        // Search
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term, $hasPivot) {
                $q->whereHas('course', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('group', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('lecturer.user', fn($qq) => $qq->where('name', 'like', $term));
                if ($hasPivot) {
                    $q->orWhereHas('lecturers.user', fn($qq) => $qq->where('name', 'like', $term));
                }
                $q->orWhereHas('series', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhere('location', 'like', $term);
            });
        }

        // Filter by attendance status (marked vs pending)
        if ($request->filled('attendance_filter')) {
            $filter = $request->input('attendance_filter');
            if ($filter === 'pending') {
                $query->whereDoesntHave('attendanceRecords');
            } elseif ($filter === 'marked') {
                $query->whereHas('attendanceRecords');
            }
        }

        $perPage = $request->input('per_page', 15);

        // Sorting: Current Date first, then by Start Date descending (Most recent to oldest)
        $query->orderByRaw("DATE(start_at) = CURDATE() DESC")
              ->orderByDesc('start_at');

        $schedules = $query->paginate($perPage)->appends($request->query());

        // Data for Filters (Cascading)

        // 1. Programs (Always all)
        $programs = \App\Models\Program::orderBy('name')->get();

        // 2. Courses (Filtered by Program, Year)
        $courseQuery = Course::orderBy('name');
        if ($request->filled('program_id')) {
            $pId = $request->integer('program_id');
            $courseQuery->whereHas('programs', fn($q) => $q->where('programs.id', $pId));
        }
        if ($request->filled('year_of_study')) {
            $yos = $request->integer('year_of_study');
            $courseQuery->whereHas('programs', fn($q) => $q->where('course_program.year_of_study', $yos));
        }
        $courses = $courseQuery->get();

        // 3. Groups (Filtered by Program, then by Course if selected)
        $groupQuery = Group::orderBy('name');
        if ($request->filled('course_id')) {
            // If a course is selected, find which programs it belongs to and filter groups by those programs
            $courseProgIds = \Illuminate\Support\Facades\DB::table('course_program')
                ->where('course_id', $request->integer('course_id'))
                ->pluck('program_id')
                ->toArray();
            if (!empty($courseProgIds)) {
                $groupQuery->where(function($q) use ($courseProgIds) {
                    $q->whereIn('program_id', $courseProgIds)
                      ->orWhereNull('program_id');
                });
            }
        } elseif ($request->filled('program_id')) {
            $groupQuery->where(function($q) use ($request) {
                $q->where('program_id', $request->integer('program_id'))
                  ->orWhereNull('program_id');
            });
        }
        $groups = $groupQuery->get();

        // 4. Lecturers (Filtered by Course via course_lecturer pivot)
        $lecturerQuery = Lecturer::query();
        if ($request->filled('course_id')) {
            $cId = $request->integer('course_id');
            $lecturerQuery->whereHas('courses', fn($q) => $q->where('courses.id', $cId));
        }
        $lecturers = $lecturerQuery->get();

        $semesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();

        if ($request->ajax()) {
            if ($request->input('fragment') === 'filters') {
                return view('admin.schedules.partials.filters', compact('schedules', 'semesters', 'programs', 'courses', 'groups', 'lecturers'));
            }
            return view('admin.schedules.partials.table', compact('schedules'));
        }
        return view('admin.schedules.index', compact('schedules', 'semesters', 'programs', 'courses', 'groups', 'lecturers'));
    }

    public function create()
    {
        // Eager-load lecturers assigned to courses for preselection in the form
        $courses = Course::with('lecturers')->get();
        $programs = \App\Models\Program::orderBy('name')->get();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        $series = ScheduleSeries::all();
        $semesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        $activeSemester = AcademicSemester::where('is_active', true)->first();
        return view('admin.schedules.create', compact('courses', 'programs', 'groups', 'lecturers', 'series', 'semesters', 'activeSemester'));
    }

    public function store(Request $request)
    {
        // Normalize optional lecturer selection
        if (!$request->filled('lecturer_id')) {
            $request->merge(['lecturer_id' => null]);
        }
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'series_id' => ['nullable', 'exists:schedule_series,id'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'requires_clock_out' => ['nullable', 'boolean'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'is_online' => ['nullable', 'boolean'],
            'access_code' => ['nullable', 'string', 'max:10'],
        ]);

        if (empty($data['academic_semester_id'])) {
            $active = \App\Models\AcademicSemester::where('is_active', true)->value('id');
            if ($active) {
                $data['academic_semester_id'] = $active;
            }
        }

        // If no lecturer selected, try to auto-assign from teaching load
        if (empty($data['lecturer_id'])) {
            $assignment = \Illuminate\Support\Facades\DB::table('course_lecturer')
                ->where('course_id', $data['course_id'])
                ->first();
            if ($assignment) {
                $data['lecturer_id'] = $assignment->lecturer_id;
            }
        }

        // Auto-fill location text from venue for backward compatibility
        if (!empty($data['venue_id'])) {
            $venue = \App\Models\Venue::find($data['venue_id']);
            if ($venue) {
                $data['location'] = $venue->fullName();
            }
        }

        $schedule = Schedule::create($data);

        // Sync many-to-many lecturers if pivot exists
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        if ($hasPivot && $request->filled('lecturer_ids')) {
            $schedule->lecturers()->sync(array_values($request->input('lecturer_ids')));
        }
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule created');
    }

    public function edit(Schedule $schedule)
    {
        // Eager-load lecturers assigned to courses for preselection in the form
        $courses = Course::with('lecturers')->get();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        $series = ScheduleSeries::all();
        $semesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        $programs = \App\Models\Program::orderBy('name')->get();
        return view('admin.schedules.edit', compact('schedule', 'courses', 'programs', 'groups', 'lecturers', 'series', 'semesters'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        // Normalize optional lecturer selection
        if (!$request->filled('lecturer_id')) {
            $request->merge(['lecturer_id' => null]);
        }
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'lecturer_ids' => ['nullable', 'array'],
            'lecturer_ids.*' => ['integer', 'exists:lecturers,id'],
            'series_id' => ['nullable', 'exists:schedule_series,id'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'requires_clock_out' => ['nullable', 'boolean'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'is_online' => ['nullable', 'boolean'],
            'access_code' => ['nullable', 'string', 'max:10'],
        ]);

        // Fix: Checkboxes don't send 'false' when unchecked, so force it if missing
        $data['is_online'] = $request->has('is_online');
        $data['requires_clock_out'] = $request->has('requires_clock_out');

        // Auto-fill location text from venue for backward compatibility
        if (!empty($data['venue_id'])) {
            $venue = \App\Models\Venue::find($data['venue_id']);
            if ($venue) {
                $data['location'] = $venue->fullName();
            }
        }

        $schedule->update($data);
        // Sync many-to-many lecturers if provided and pivot exists
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        if ($request->has('lecturer_ids') && $hasPivot) {
            $schedule->lecturers()->sync($request->input('lecturer_ids') ?: []);
        }
        // Fallback: if pivot doesn't exist, set or clear single lecturer_id based on selection
        if (!$hasPivot) {
            $firstLecturerId = collect($request->input('lecturer_ids', []))->first();
            $schedule->lecturer_id = $firstLecturerId ? (int) $firstLecturerId : null;
            $schedule->save();
        }
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule updated');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule deleted');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:schedules,id',
        ]);

        Schedule::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('admin.schedules.index')->with('success', count($validated['ids']) . ' Schedules deleted successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:schedules,id',
            'field' => 'required|in:is_online,requires_clock_out,attendance_status,is_cancelled',
            'value' => 'required',
        ]);

        $field = $validated['field'];
        $value = $validated['value'];

        // Handle "cancelled" specially: it uses is_cancelled boolean, not the enum
        if ($field === 'attendance_status' && $value === 'cancelled') {
            $count = Schedule::whereIn('id', $validated['ids'])
                ->update(['is_cancelled' => true]);
            return redirect()->route('admin.schedules.index')
                ->with('success', "$count schedules marked as Cancelled");
        }

        // Validate value based on field
        if ($field === 'attendance_status') {
            if (!in_array($value, ['scheduled', 'open', 'late', 'closed'])) {
                return back()->with('error', 'Invalid status value.');
            }
            // Also un-cancel when setting a valid status
            Schedule::whereIn('id', $validated['ids'])->update(['is_cancelled' => false]);
        } else {
            if (!in_array($value, ['0', '1'])) {
                return back()->with('error', 'Invalid toggle value.');
            }
            $value = (bool) $value;
        }

        $count = Schedule::whereIn('id', $validated['ids'])
            ->update([$field => $value]);

        $label = str_replace('_', ' ', ucfirst($field));
        $state = is_bool($value) ? ($value ? 'ON' : 'OFF') : ucfirst($value);

        return redirect()->route('admin.schedules.index')
            ->with('success', "$count schedules updated: $label → $state");
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'attendance_status' => 'required|in:scheduled,open,late,closed,cancelled',
            'late_at_minutes' => 'nullable|integer|min:1',
        ]);

        $status = $validated['attendance_status'];

        if ($status === 'cancelled') {
             $schedule->update([
                 'is_cancelled' => true,
                 'attendance_status' => 'closed', // Close it if cancelled
             ]);
             return redirect()->back()->with('success', 'Schedule marked as Not Taught (Cancelled).');
        } else {
             // If setting any other status, ensure is_cancelled is false (uncancel if reopened)
             $updateData = [
                 'attendance_status' => $status,
                 'is_cancelled' => false
             ];
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
