<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\ScheduleSeries;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['course', 'group', 'lecturer', 'series']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('lecturer_id')) {
            $query->where('lecturer_id', $request->integer('lecturer_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('start_at', $request->input('date'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('course', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('group', fn($qq) => $qq->where('name', 'like', $term))
                  // Search lecturer by canonical identity via related user
                  ->orWhereHas('lecturer.user', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('series', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhere('location', 'like', $term);
            });
        }

        $schedules = $query->orderByDesc('start_at')->paginate(15)->appends($request->query());

        $courses = Course::all();
        // Dropdown options: narrow based on selected course if provided
        if ($request->filled('course_id')) {
            $groupIds = Schedule::where('course_id', $request->integer('course_id'))
                ->pluck('group_id')->filter()->unique()->values();
            $lecturerIds = Schedule::where('course_id', $request->integer('course_id'))
                ->pluck('lecturer_id')->filter()->unique()->values();
            $groups = Group::whereIn('id', $groupIds)->get();
            $lecturers = Lecturer::whereIn('id', $lecturerIds)->get();
        } else {
            $groups = Group::all();
            $lecturers = Lecturer::all();
        }
        if ($request->ajax()) {
            if ($request->input('fragment') === 'filters') {
                return view('admin.schedules.partials.filters', compact('courses', 'groups', 'lecturers'));
            }
            return view('admin.schedules.partials.table', compact('schedules'));
        }
        return view('admin.schedules.index', compact('schedules', 'courses', 'groups', 'lecturers'));
    }

    public function create()
    {
        $courses = Course::all();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        $series = ScheduleSeries::all();
        return view('admin.schedules.create', compact('courses', 'groups', 'lecturers', 'series'));
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
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        Schedule::create($data);
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule created');
    }

    public function edit(Schedule $schedule)
    {
        $courses = Course::all();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        $series = ScheduleSeries::all();
        return view('admin.schedules.edit', compact('schedule', 'courses', 'groups', 'lecturers', 'series'));
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
            'series_id' => ['nullable', 'exists:schedule_series,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);

        $schedule->update($data);
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule updated');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Schedule deleted');
    }
}