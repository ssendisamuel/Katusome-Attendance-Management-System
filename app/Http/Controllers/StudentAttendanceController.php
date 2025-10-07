<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\AttendanceConfirmationMail;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Illuminate\Support\Facades\Storage;
use App\Models\LocationSetting;

class StudentAttendanceController extends Controller
{
    public function today(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access attendance.']);
        }

        $today = Carbon::today();
        $schedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

        $attendanceBySchedule = Attendance::whereIn('schedule_id', $schedules->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        return view('attendance.student_today', [
            'student' => $student,
            'schedules' => $schedules,
            'attendanceBySchedule' => $attendanceBySchedule,
        ]);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access check-in.']);
        }

        $today = Carbon::today();
        $schedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

        $setting = LocationSetting::current();
        return view('attendance.checkin', compact('schedules', 'student', 'setting'));
    }

    public function show(Request $request, Schedule $schedule)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access check-in.']);
        }

        // Ensure schedule is for student's group and today
        $today = Carbon::today();
        if ($schedule->group_id !== $student->group_id || !$schedule->start_at->isSameDay($today)) {
            abort(403);
        }

        // Existing attendance for this schedule
        $existing = Attendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->orderByDesc('marked_at')
            ->first();

        $setting = LocationSetting::current();
        return view('attendance.checkin_show', compact('schedule', 'student', 'existing', 'setting'));
    }

    /**
     * Show a summary page for a specific attendance record
     */
    public function summary(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access attendance.']);
        }

        // Ensure the attendance belongs to the logged-in student
        if ($attendance->student_id !== $student->id) {
            abort(403);
        }

        $schedule = Schedule::with(['course', 'lecturer', 'lecturers'])->findOrFail($attendance->schedule_id);
        $setting = LocationSetting::current();
        return view('attendance.summary', compact('attendance', 'schedule', 'student', 'setting'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to mark attendance.']);
        }

        $data = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'selfie' => ['nullable', 'image', 'max:5120'], // Webcam capture as file blob
        ]);

        $schedule = Schedule::findOrFail($data['schedule_id']);
        if ($schedule->group_id !== $student->group_id) {
            return back()->withErrors(['schedule_id' => 'This schedule is not for your group.']);
        }

        $now = Carbon::now();
        // Enforce class time window (only during class time)
        if ($now->lt(Carbon::parse($schedule->start_at)) || $now->gt(Carbon::parse($schedule->end_at))) {
            return back()->withErrors(['schedule_id' => 'Attendance can only be recorded during class time.']);
        }

        // Geofence: Admin-configured campus location
        $setting = LocationSetting::current();
        $campusLat = $setting?->latitude ?? 0.332931;
        $campusLng = $setting?->longitude ?? 32.621927;
        $lat = (float)$data['lat'];
        $lng = (float)$data['lng'];
        $distanceMeters = $this->haversineDistanceMeters($campusLat, $campusLng, $lat, $lng);
        $radiusMeters = $setting?->radius_meters ?? 150;
        if ($distanceMeters > $radiusMeters) {
            $rounded = (int) round($distanceMeters);
            return back()->withErrors(['location' => 'Attendance can only be recorded from within MUBS premises. Outside MUBS premises (' . $rounded . 'm).']);
        }

        // Derive status: late if 30+ minutes after start, otherwise present
        $status = $now->greaterThan(Carbon::parse($schedule->start_at)->addMinutes(30)) ? 'late' : 'present';

        $path = null;
        if ($request->hasFile('selfie')) {
            $path = $request->file('selfie')->store('selfies', 'public');
        }

        $prior = Attendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->first();

        $attendance = Attendance::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'marked_at' => $now,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'selfie_path' => $path,
            ]
        );

        // Queue a confirmation email (high volume-friendly)
        $shouldNotify = !$prior
            || optional($prior->marked_at)->ne($now)
            || $prior->status !== $status
            || $prior->selfie_path !== $path;
        if ($shouldNotify && optional($student->user)->email) {
            Mail::to($student->user->email)->queue(
                new AttendanceConfirmationMail($student, $schedule, $attendance)
            );
        }

        $courseName = optional($schedule->course)->name;
        $successMsg = 'Attendance recorded successfully at ' . $now->format('h:i A')
            . ($courseName ? (' for ' . $courseName) : '') . '.';

        // If this is an AJAX request (fetch/XHR), return JSON so the client
        // can show a toast and then redirect without consuming the flash.
        if ($request->expectsJson() || $request->ajax()) {
            // Persist a flash for the next full page load (dashboard)
            session()->flash('success', $successMsg);
            return response()->json([
                'message' => $successMsg,
                'redirect' => route('student.dashboard'),
                'attendance_id' => $attendance->id,
            ]);
        }

        // Default: redirect to dashboard with success flash for full-page post
        return redirect()->route('student.dashboard')->with('success', $successMsg);
    }

    private function haversineDistanceMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}