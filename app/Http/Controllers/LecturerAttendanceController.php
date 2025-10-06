<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LecturerAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $lecturer = optional(Auth::user())->lecturer;
        if (!$lecturer) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a lecturer to access this page.']);
        }

        $today = Carbon::today();
        $schedules = Schedule::with(['course', 'group'])
            ->where('lecturer_id', $lecturer->id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->paginate(10);

        return view('lecturer.attendance.index', compact('schedules'));
    }

    public function edit(Schedule $schedule)
    {
        $lecturer = optional(Auth::user())->lecturer;
        if (!$lecturer || $schedule->lecturer_id !== $lecturer->id) {
            abort(403);
        }

        $students = $schedule->group->students()->orderBy('name')->get();
        $existing = Attendance::where('schedule_id', $schedule->id)->get()->keyBy('student_id');
        return view('lecturer.attendance.edit', compact('schedule', 'students', 'existing'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $lecturer = optional(Auth::user())->lecturer;
        if (!$lecturer || $schedule->lecturer_id !== $lecturer->id) {
            abort(403);
        }

        $data = $request->validate([
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', 'in:present,absent,late'],
        ]);

        $now = Carbon::now();
        foreach ($data['statuses'] as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'student_id' => (int) $studentId,
                ],
                [
                    'status' => $status,
                    'marked_at' => $now,
                ]
            );
        }

        return redirect()->route('lecturer.attendance.index')->with('success', 'Attendance updated.');
    }
}