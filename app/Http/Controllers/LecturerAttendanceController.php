<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Carbon\Carbon;

class LecturerAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $lecturer = optional(auth()->user())->lecturer;
        if (!$lecturer) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a lecturer to access this page.']);
        }

        $today = Carbon::today();
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $withRels = ['course', 'group'];
        if ($hasPivot) { $withRels[] = 'lecturers'; }
        $schedules = Schedule::with($withRels)
            ->whereDate('start_at', $today)
            ->where(function($q) use ($lecturer, $hasPivot){
                $q->where('lecturer_id', $lecturer->id);
                if ($hasPivot) {
                    $q->orWhereHas('lecturers', fn($qq) => $qq->where('lecturers.id', $lecturer->id));
                }
            })
            ->orderBy('start_at')
            ->paginate(10);

        return view('lecturer.attendance.index', compact('schedules'));
    }

    public function edit(Schedule $schedule)
    {
        $lecturer = optional(auth()->user())->lecturer;
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $isAssigned = $schedule->lecturer_id === ($lecturer->id ?? null);
        if ($hasPivot) {
            $isAssigned = $isAssigned || $schedule->lecturers->contains('id', $lecturer->id);
        }
        if (!$lecturer || !$isAssigned) {
            abort(403);
        }

        // Order students by canonical identity via related user
        $students = $schedule->group->students()
            ->whereHas('user')
            ->with('user')
            ->orderBy(
                \DB::raw('(select name from users where users.id = students.user_id)'),
                'asc'
            )
            ->get();
        $existing = Attendance::where('schedule_id', $schedule->id)->get()->keyBy('student_id');
        return view('lecturer.attendance.edit', compact('schedule', 'students', 'existing'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $lecturer = optional(auth()->user())->lecturer;
        $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');
        $isAssigned = $schedule->lecturer_id === ($lecturer->id ?? null);
        if ($hasPivot) {
            $isAssigned = $isAssigned || $schedule->lecturers->contains('id', $lecturer->id);
        }
        if (!$lecturer || !$isAssigned) {
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