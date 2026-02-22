<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\LocationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Mail\AttendanceConfirmationMail;

class StudentAttendanceController extends Controller
{
    public function today(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
             return response()->json(['message' => 'Student record not found.'], 404);
        }

        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return response()->json([
                'schedules' => [],
                'attendance' => [],
                'message' => 'Not enrolled in current semester.'
            ]);
        }

        $today = Carbon::today();
        $schedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $enrollment->academic_semester_id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();

        // Get schedules for RETAKE/EXTRA courses registered this semester
        $retakeRegistrations = $student->extraCourses()
             ->wherePivot('academic_semester_id', $enrollment->academic_semester_id)
             ->withPivot('target_group_id')
             ->get();

        foreach ($retakeRegistrations as $registration) {
             $query = Schedule::with(['course', 'lecturer'])
                 ->where('course_id', $registration->id)
                 ->where('academic_semester_id', $enrollment->academic_semester_id)
                 ->whereDate('start_at', $today);

             if ($registration->pivot->target_group_id) {
                 $query->where('group_id', $registration->pivot->target_group_id);
             }
             $schedules = $schedules->merge($query->get());
        }

        $schedules = $schedules->unique('id')->sortBy('start_at')->values();

        $enrollment->load(['program', 'academicSemester', 'group']);

        $attendanceBySchedule = Attendance::whereIn('schedule_id', $schedules->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        // Metrics
        $presentToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $lateToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();

        return response()->json([
            'schedules' => $schedules,
            'attendance' => $attendanceBySchedule,
            'enrollment' => $enrollment,
            'metrics' => [
                'presentToday' => $presentToday,
                'lateToday' => $lateToday,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'distance_meters' => 'nullable|numeric',
            'access_code' => 'nullable|string',
            'selfie' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $schedule = Schedule::findOrFail($request->schedule_id);

        // Authorization: Check Group Match OR Retake Authorization
        $isGroupMatch = ($schedule->group_id === $student->group_id);
        $isRetakeAuthorized = false;

        if (!$isGroupMatch) {
            // Check if student is registered for this course as a retake/extra in the current semester
            // We assume the schedule's semester is the one we check registration for
            $isRetakeAuthorized = $student->retakeCourses()
                ->where('course_id', $schedule->course_id)
                ->where('academic_semester_id', $schedule->academic_semester_id)
                ->exists();
        }

        if (!$isGroupMatch && !$isRetakeAuthorized) {
             return response()->json(['message' => 'This schedule is not for your group and you are not registered for this course.'], 403);
        }

        $now = Carbon::now();
        $attStatus = $schedule->attendance_status ?? 'scheduled';

        if ($attStatus === 'closed') {
             return response()->json(['message' => 'Attendance is closed.'], 403);
        }

        // Time Window Logic
        if ($attStatus === 'scheduled') {
            if ($now->lt(Carbon::parse($schedule->start_at)) || $now->gt(Carbon::parse($schedule->end_at))) {
                 return response()->json(['message' => 'Attendance can only be recorded during class time.'], 403);
            }
        }

        // Geofence / Online Check — use venue-specific coords if available
        $coords = null;
        if ($schedule->venue_id && $schedule->venue) {
            $coords = $schedule->venue->getLocationCoordinates();
        }
        if (!$coords) {
            $setting = LocationSetting::current();
            $coords = [
                'latitude' => $setting?->latitude ?? 0.332931,
                'longitude' => $setting?->longitude ?? 32.621927,
                'radius_meters' => $setting?->radius_meters ?? 150,
            ];
        }
        $campusLat = $coords['latitude'];
        $campusLng = $coords['longitude'];
        $distanceMeters = 0;

        if ($schedule->is_online) {
             if ($schedule->access_code && $request->access_code !== $schedule->access_code) {
                 return response()->json(['message' => 'Invalid Access Code.'], 403);
             }
             $lat = $request->lat ?? null;
             $lng = $request->lng ?? null;
        } else {
            if (!$request->lat || !$request->lng) {
                 return response()->json(['message' => 'Location is required.'], 400);
            }
            $lat = (float)$request->lat;
            $lng = (float)$request->lng;
            $distanceMeters = $this->haversineDistanceMeters($campusLat, $campusLng, $lat, $lng);
            $radiusMeters = $coords['radius_meters'];

            if ($distanceMeters > $radiusMeters) {
                $rounded = round($distanceMeters);
                $venueName = $schedule->venue ? $schedule->venue->fullName() : null;
                $locationLabel = $venueName ? $venueName : 'MUBS premises';
                return response()->json(['message' => 'You are not within '.$locationLabel.' ('.$rounded.'m away). You cannot take attendance here.'], 403);
            }
        }

        // Status Logic
        $status = 'present';
        if ($attStatus === 'late' || ($schedule->late_at && $now->greaterThan($schedule->late_at))) {
            $status = 'late';
        } elseif ($attStatus === 'scheduled' && $now->greaterThan(Carbon::parse($schedule->start_at)->addMinutes(30))) {
            $status = 'late';
        }

        // Store selfie if uploaded
        $path = null;
        if ($request->hasFile('selfie')) {
            $path = $request->file('selfie')->store('selfies', 'public');
        }

        $attendance = Attendance::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'marked_at' => $now,
                'clock_in_time' => $now, // Set clock_in_time
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $request->accuracy,
                'distance_meters' => (int) $distanceMeters,
                'selfie_path' => $path,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'platform' => 'Mobile_app',
            ]
        );

        // Send Email (Simplified for API response speed, maybe queue it?)
        // For now, same sync logic
        if ($user->email) {
            try {
                 Mail::mailer('smtp')->to($user->email)->send(new AttendanceConfirmationMail($student, $schedule, $attendance));
            } catch (\Exception $e) {
                // Ignore email failure for API response
            }
        }

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'status' => $status,
            'attendance' => $attendance
        ]);
    }

    public function clockOut(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'selfie' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $schedule = Schedule::findOrFail($request->schedule_id);
        $now = Carbon::now();

        // 1. Check if student has clocked in (has an attendance record)
        $attendance = Attendance::where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'You have not clocked in for this class.'], 400);
        }

        if ($attendance->clock_out_time) {
            return response()->json(['message' => 'You have already clocked out.'], 400);
        }

        // 2. Geofence Check — use venue-specific coords if available
        if (!$schedule->is_online) {
             if (!$request->lat || !$request->lng) {
                 return response()->json(['message' => 'Location is required.'], 400);
             }
             $coords = null;
             if ($schedule->venue_id && $schedule->venue) {
                 $coords = $schedule->venue->getLocationCoordinates();
             }
             if (!$coords) {
                 $setting = LocationSetting::current();
                 $coords = [
                     'latitude' => $setting?->latitude ?? 0.332931,
                     'longitude' => $setting?->longitude ?? 32.621927,
                     'radius_meters' => $setting?->radius_meters ?? 150,
                 ];
             }
             $lat = (float)$request->lat;
             $lng = (float)$request->lng;

             $distanceMeters = $this->haversineDistanceMeters($coords['latitude'], $coords['longitude'], $lat, $lng);

             if ($distanceMeters > $coords['radius_meters']) {
                 $rounded = round($distanceMeters);
                 $venueName = $schedule->venue ? $schedule->venue->fullName() : null;
                 $locationLabel = $venueName ? $venueName : 'MUBS premises';
                 return response()->json(['message' => 'You are not within '.$locationLabel.' ('.$rounded.'m away). You must be in class to clock out.'], 403);
             }
        } else {
             $lat = $request->lat ?? null;
             $lng = $request->lng ?? null;
        }

        // Store selfie if uploaded
        $path = null;
        if ($request->hasFile('selfie')) {
            $path = $request->file('selfie')->store('selfies', 'public');
        }

        // 3. Update Clock Out
        $attendance->update([
            'clock_out_time' => $now,
            'clock_out_lat' => $lat,
            'clock_out_lng' => $lng,
            'clock_out_selfie_path' => $path,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'platform' => 'Mobile_app',
        ]);

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new \App\Mail\AttendanceSummaryMail($student, $schedule, $attendance));
            } catch (\Exception $e) {
                // Log error but don't fail response
                Log::error('API Attendance email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Clocked out successfully.',
            'clock_out_time' => $now->toDateTimeString(),
        ]);
    }

    private function haversineDistanceMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
