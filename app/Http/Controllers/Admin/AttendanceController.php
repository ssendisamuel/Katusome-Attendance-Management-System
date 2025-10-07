<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        return view('admin.attendance.index', compact('attendances', 'courses', 'groups', 'lecturers'));
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return redirect()->route('admin.attendance.index')->with('success', 'Attendance record deleted');
    }
}