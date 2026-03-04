<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\AcademicSemester;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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

        // Get student's current enrollment
        $enrollment = $student->currentEnrollment();

        // If no enrollment, show basic empty view
        if (!$enrollment) {
            return view('attendance.student_today', [
                'student' => $student,
                'schedules' => collect([]),
                'attendanceBySchedule' => collect([]),
                'metrics' => [
                     'presentToday' => 0, 'lateToday' => 0, 'timeSpentTotalHours' => 0,
                     'attendanceTrack' => [], 'attendanceTrackTable' => [],
                     'weeklyLabel' => '', 'weeklyAttended' => [],
                     'monthlySummary' => ['attended' => 0, 'missed' => 0, 'upcoming' => 0],
                ],
                'noEnrollment' => true,
            ]);
        }

        $semesterId = $enrollment->academic_semester_id;

        // Extract semester number (e.g. "Semester 2" -> "2")
        $activeSemester = \App\Models\AcademicSemester::find($semesterId);
        $semesterNumber = null;
        if ($activeSemester && preg_match('/(\d+)/', $activeSemester->semester, $matches)) {
             $semesterNumber = $matches[1];
        }
        $validSemesterValues = array_filter([$activeSemester ? $activeSemester->semester : null, $semesterNumber]);

        // Get program code for teaching load lookup
        $program = \App\Models\Program::find($enrollment->program_id);
        $programCode = $program ? $program->code : '';
        $semNum = $semesterNumber ?? '2';

        // Get course IDs from teaching load for this student's program + year + semester
        $enrolledCourseIds = \Illuminate\Support\Facades\DB::table('course_lecturer')
            ->where('program_code', $programCode)
            ->where('year_of_study', $enrollment->year_of_study)
            ->where('academic_year', $activeSemester->year)
            ->where('semester', $semNum)
            ->distinct()
            ->pluck('course_id');

        // 1. Get schedules for the student's CURRENT group, filtered by enrolled courses
        $groupSchedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $semesterId)
            ->whereIn('course_id', $enrolledCourseIds)
            ->get();

        // 1b. Get schedules for RETAKE/EXTRA courses registered this semester
        $retakeRegistrations = $student->extraCourses()
             ->wherePivot('academic_semester_id', $semesterId)
             ->withPivot('target_group_id')
             ->get();

        $retakeSchedules = collect([]);

        foreach ($retakeRegistrations as $registration) {
             $query = Schedule::with(['course', 'lecturer'])
                 ->where('course_id', $registration->id)
                 ->where('academic_semester_id', $semesterId);

             if ($registration->pivot->target_group_id) {
                 $query->where('group_id', $registration->pivot->target_group_id);
             }
             $retakeSchedules = $retakeSchedules->merge($query->get());
        }

        // 2. Get schedules the student has ACTUALLY ATTENDED (even if outside group)
        $attendedScheduleIds = Attendance::where('student_id', $student->id)
            ->whereHas('schedule', function ($q) use ($semesterId) {
                $q->where('academic_semester_id', $semesterId);
            })
            ->pluck('schedule_id');

        $attendedSchedules = Schedule::with(['course', 'lecturer'])
            ->whereIn('id', $attendedScheduleIds)
            ->get();

        // 3. Merge and unique
        $allSchedules = $groupSchedules->merge($attendedSchedules)->merge($retakeSchedules)->unique('id')->sortBy('start_at');

        // Today's Schedules
        $today = Carbon::today();
        $schedulesToday = $allSchedules->filter(function ($s) use ($today) {
            return $s->start_at->isSameDay($today);
        })->values();

        $attendanceByScheduleToday = Attendance::whereIn('schedule_id', $schedulesToday->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        // Metrics Calculation
        // Present/Late today
        $presentToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $lateToday = Attendance::where('student_id', $student->id)
            ->whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();

        // Course Metrics
        $groupedByCourse = $allSchedules->groupBy('course_id');
        $courseMetrics = [];
        $totalMinutesAll = 0;

        $allAttendance = Attendance::where('student_id', $student->id)
            ->whereIn('schedule_id', $allSchedules->pluck('id'))
            ->get();

        $now = now();

        foreach ($groupedByCourse as $courseId => $courseSchedules) {
            $courseName = $courseSchedules->first()->course->name ?? '—';
            $validSchedules = $courseSchedules->where('is_cancelled', false);
            $totalSessions = $validSchedules->count();
            $cancelledCount = $courseSchedules->where('is_cancelled', true)->count();

            $attendedCount = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->count();

            // Time Logic
            $courseMinutes = 0;
            $totalPossibleMinutes = 0;
            foreach ($validSchedules as $sch) {
                if ($sch->start_at && $sch->end_at) {
                    $totalPossibleMinutes += $sch->start_at->diffInMinutes($sch->end_at);
                }
            }
            $courseAttendance = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'));
            foreach ($courseAttendance as $att) {
                if ($att->clock_out_time) {
                    $courseMinutes += $att->marked_at->diffInMinutes($att->clock_out_time);
                } elseif ($att->schedule->end_at && $att->marked_at) {
                     $endTime = now()->lt($att->schedule->end_at) ? now() : $att->schedule->end_at;
                     $courseMinutes += $att->marked_at->diffInMinutes($endTime);
                } elseif ($att->schedule->start_at && $att->schedule->end_at) {
                    $courseMinutes += $att->schedule->start_at->diffInMinutes($att->schedule->end_at);
                }
            }
            $totalMinutesAll += $courseMinutes;
            $percent = $totalSessions > 0 ? (int) round(($attendedCount / max($totalSessions, 1)) * 100) : 0;

            // Missed logic
            $attendedIds = $allAttendance->whereIn('schedule_id', $courseSchedules->pluck('id'))
                ->whereIn('status', ['present', 'late'])
                ->pluck('schedule_id');
            $missedCount = $validSchedules->filter(function($s) use ($now, $attendedIds) {
                 return $s->end_at->lt($now) && !$attendedIds->contains($s->id);
            })->count();

            $courseMetrics[] = [
                'course_name' => $courseName,
                'progress_percent' => $percent,
                'attended_count' => $attendedCount,
                'taught_count' => $totalSessions,
                'missed_count' => $missedCount,
                'cancelled' => $cancelledCount,
                'time_minutes' => $courseMinutes,
                'total_possible_minutes' => $totalPossibleMinutes,
            ];
        }

        $attendanceTrackTable = collect($courseMetrics)
            ->sortByDesc('progress_percent')
            ->values()
            ->map(function ($m) {
                return [
                    'name' => $m['course_name'],
                    'progress' => $m['progress_percent'],
                    'attended' => $m['attended_count'],
                    'taught' => $m['taught_count'],
                    'missed' => $m['missed_count'],
                    'cancelled' => $m['cancelled'],
                    'time' => (intdiv($m['time_minutes'], 60) > 0 ? intdiv($m['time_minutes'], 60).'h ' : '') . ($m['time_minutes'] % 60) . 'm / ' . (intdiv($m['total_possible_minutes'], 60) > 0 ? intdiv($m['total_possible_minutes'], 60).'h ' : '') . ($m['total_possible_minutes'] % 60) . 'm',
                ];
            })
            ->all();

        // Weekly/Monthly Summaries
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $weeklyAttendances = $allAttendance->whereBetween('marked_at', [$weekStart, $weekEnd])
            ->whereIn('status', ['present', 'late'])
            ->sortBy('marked_at');
        $weeklyAttended = $weeklyAttendances->map(function ($att) {
            return [
                'name' => optional($att->schedule->course)->name ?? '—',
                'date' => $att->marked_at->format('D, M j'),
                'time' => $att->marked_at->format('h:i A'),
            ];
        })->values()->all();

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $attendedMonth = $allAttendance->whereBetween('marked_at', [$monthStart, $monthEnd])->whereIn('status', ['present', 'late'])->count();
        $upcomingMonth = $allSchedules->filter(fn($s) => $s->end_at > now() && $s->end_at <= $monthEnd && !$s->is_cancelled)->count();
        // Missed in month logic
        $schedulesInMonth = $allSchedules->filter(fn($s) => $s->start_at >= $monthStart && $s->end_at < now() && !$s->is_cancelled);
        $attendedScheduleIdsMonth = $allAttendance->whereBetween('marked_at', [$monthStart, now()])->whereIn('status', ['present', 'late'])->pluck('schedule_id');
        $missedMonth = $schedulesInMonth->whereNotIn('id', $attendedScheduleIdsMonth)->count();

        $metrics = [
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'timeSpentTotalHours' => (int) round($totalMinutesAll / 60),
            'attendanceTrackTable' => $attendanceTrackTable,
            'weeklyLabel' => $weekStart->format('M j') . ' – ' . $weekEnd->format('M j'),
            'weeklyAttended' => $weeklyAttended,
            'monthlySummary' => ['attended' => $attendedMonth, 'missed' => $missedMonth, 'upcoming' => $upcomingMonth],
        ];

        return view('attendance.student_today', [
            'student' => $student,
            'enrollment' => $enrollment,
            'schedules' => $schedulesToday,
            'attendanceBySchedule' => $attendanceByScheduleToday,
            'metrics' => $metrics,
        ]);
    }
    public function create(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access check-in.']);
        }

        // Get enrollment for semester filtering
        $enrollment = $student->currentEnrollment();
        if (!$enrollment) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Please enroll in the active semester first.');
        }

        $today = Carbon::today();
        $schedules = Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->where('academic_semester_id', $enrollment->academic_semester_id)
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

        // Verify enrollment for semester check
        $enrollment = $student->currentEnrollment();
        if (!$enrollment || $schedule->academic_semester_id !== $enrollment->academic_semester_id) {
            abort(403, 'Schedule not available for your current enrollment.');
        }

        // Ensure schedule is for student's group OR authorized retake
        $isGroupMatch = ($schedule->group_id === $student->group_id);
        $isRetakeAuthorized = false;

        if (!$isGroupMatch) {
             $isRetakeAuthorized = $student->retakeCourses()
                ->where('course_id', $schedule->course_id)
                ->where('academic_semester_id', $schedule->academic_semester_id)
                ->exists();
        }

        $today = Carbon::today();
        if ((!$isGroupMatch && !$isRetakeAuthorized) || !$schedule->start_at->isSameDay($today)) {
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
            'lat' => ['nullable', 'numeric'], // Made nullable for online
            'lng' => ['nullable', 'numeric'], // Made nullable for online
            'accuracy' => ['nullable', 'numeric'],
            'distance_meters' => ['nullable', 'integer'],
            'selfie' => ['nullable', 'image', 'max:5120'],
            'access_code' => ['nullable', 'string'], // New field
        ]);

        $schedule = Schedule::findOrFail($data['schedule_id']);

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
            return back()->withErrors(['schedule_id' => 'This schedule is not for your group and you are not registered for this course.']);
        }

        $now = Carbon::now();

        // 1. Check Manual Attendance Status
        $attStatus = $schedule->attendance_status ?? 'scheduled'; // default to scheduled if null

        if ($attStatus === 'closed') {
             return back()->withErrors(['schedule_id' => 'Attendance for this session is currently disabled/closed.']);
        }

        // 2. Enforce Time Window (Only if NOT manually opened/late)
        // If the lecturer explicitly marked it Open or Late, we allow check-in even outside standard hours (within reason? maybe same day).
        if ($attStatus === 'scheduled') {
            if ($now->lt(Carbon::parse($schedule->start_at)) || $now->gt(Carbon::parse($schedule->end_at))) {
                return back()->withErrors(['schedule_id' => 'Attendance can only be recorded during class time.']);
            }
        }


        // 3. Location / Online Check — use venue-specific coords if available
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
            // ONLINE CLASS: Verify Access Code
            if (!$schedule->access_code) {
                 // Should not happen if created correctly, but if no code set, maybe allow?
                 // Let's enforce it if it exists.
            } else {
                 if ($request->input('access_code') !== $schedule->access_code) {
                     return back()->withErrors(['access_code' => 'Invalid Access Code. Please ask your lecturer for the correct code.']);
                 }
            }
            // Trust the user provided lat/lng or null
            $lat = $data['lat'] ?? null;
            $lng = $data['lng'] ?? null;

        } else {
            // PHYSICAL CLASS: Enforce Geofence
            if (empty($data['lat']) || empty($data['lng'])) {
                return back()->withErrors(['location' => 'Location is required for physical classes.']);
            }

            $lat = (float)$data['lat'];
            $lng = (float)$data['lng'];
            $distanceMeters = $this->haversineDistanceMeters($campusLat, $campusLng, $lat, $lng);
            $radiusMeters = $coords['radius_meters'];

            if ($distanceMeters > $radiusMeters) {
                $rounded = (int) round($distanceMeters);
                $venueName = $schedule->venue ? $schedule->venue->fullName() : null;
                $locationLabel = $venueName ?? 'MUBS premises';
                return back()->withErrors(['location' => 'You are not within ' . $locationLabel . ' (' . $rounded . 'm away). Attendance can only be recorded from the assigned venue.']);
            }
        }

        // Derive status
        $status = 'present';
        if ($attStatus === 'late') {
            $status = 'late';
        } elseif ($schedule->late_at && $now->greaterThan($schedule->late_at)) {
             $status = 'late';
        } elseif ($attStatus === 'scheduled' && $now->greaterThan(Carbon::parse($schedule->start_at)->addMinutes(60))) {
             // Fallback default logic for scheduled sessions
             $status = 'late';
        }

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
                'accuracy' => $data['accuracy'] ?? null,
                'distance_meters' => isset($data['distance_meters'])
                    ? (int) $data['distance_meters']
                    : (int) round($distanceMeters),
                'selfie_path' => $path,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'platform' => 'web',
            ]
        );

        // Send confirmation email on every check-in attempt and record status for UI polling
        $emailSent = false;
        if (optional($student->user)->email) {
            try {
                // Force SMTP mailer to avoid local sendmail issues
                Mail::mailer('smtp')->to($student->user->email)->send(new AttendanceConfirmationMail($student, $schedule, $attendance));
                $emailSent = true;
                Log::info('Attendance confirmation mail sent', [
                    'student_id' => $student->id,
                    'schedule_id' => $schedule->id,
                    'attendance_id' => $attendance->id,
                ]);
                Cache::put('mail:attendance:' . $attendance->id, 'sent', now()->addMinutes(30));
            } catch (\Throwable $e) {
                Log::warning('Sending attendance mail failed', [
                    'error' => $e->getMessage(),
                    'student_id' => $student->id,
                    'schedule_id' => $schedule->id,
                    'attendance_id' => $attendance->id,
                ]);
                Cache::put('mail:attendance:' . $attendance->id, 'failed', now()->addMinutes(30));
            }
        }

        $courseName = optional($schedule->course)->name;
        $successMsg = 'Attendance recorded successfully at ' . $now->format('h:i A')
            . ($courseName ? (' for ' . $courseName) : '') . '.';

        $infoMsg = $emailSent ? 'Confirmation email sent' : 'Confirmation email could not be sent';

        // If this is an AJAX request (fetch/XHR), return JSON so the client
        // can show a toast and then redirect without consuming the flash.
        if ($request->expectsJson() || $request->ajax()) {
            // Persist a flash for the next full page load (dashboard)
            session()->flash('success', $successMsg);
            session()->flash('info', $infoMsg);
            return response()->json([
                'message' => $successMsg,
                'redirect' => route('student.dashboard'),
                'attendance_id' => $attendance->id,
                'email_status' => $emailSent ? 'sent' : 'failed',
                'info' => $infoMsg,
            ]);
        }

        // Default: redirect to dashboard with success flash for full-page post
        return redirect()->route('student.dashboard')
            ->with('success', $successMsg)
            ->with('info', $infoMsg);
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

    public function showClockOut(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student || $attendance->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        if ($attendance->clock_out_time) {
            return redirect()->route('student.dashboard')->with('info', 'You have already clocked out.');
        }

        $schedule = $attendance->schedule;
        $setting = LocationSetting::current();

        return view('attendance.clockout', compact('attendance', 'schedule', 'student', 'setting'));
    }

    public function clockOut(Request $request, Attendance $attendance)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student || $attendance->student_id !== $student->id) {
            abort(403, 'Unauthorized');
        }

        if ($attendance->clock_out_time) {
            return redirect()->route('student.dashboard')->with('info', 'You have already clocked out.');
        }

        $schedule = $attendance->schedule;
        $isOnline = $schedule->is_online;

        $rules = [
            'selfie' => ['required', 'image', 'max:5120'],
        ];

        if ($isOnline) {
             $rules['lat'] = ['nullable', 'numeric'];
             $rules['lng'] = ['nullable', 'numeric'];
        } else {
             $rules['lat'] = ['required', 'numeric'];
             $rules['lng'] = ['required', 'numeric'];
        }

        $data = $request->validate($rules);

        // Geofence Check (Physical Only) — use venue-specific coords
        if (!$isOnline) {
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
            $lat = (float)$data['lat'];
            $lng = (float)$data['lng'];
            $distanceMeters = $this->haversineDistanceMeters($coords['latitude'], $coords['longitude'], $lat, $lng);

            if ($distanceMeters > $coords['radius_meters']) {
                $rounded = (int) round($distanceMeters);
                $venueName = $schedule->venue ? $schedule->venue->fullName() : null;
                $locationLabel = $venueName ?? 'MUBS premises';
                return back()->withErrors(['location' => 'You are not within ' . $locationLabel . ' (' . $rounded . 'm away). Clock-out can only be recorded from the assigned venue.']);
            }
        }

        $path = $request->file('selfie')->store('selfies', 'public');

        $attendance->update([
            'clock_out_time' => now(),
            'clock_out_lat' => $data['lat'] ?? null,
            'clock_out_lng' => $data['lng'] ?? null,
            'clock_out_selfie_path' => $path,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'platform' => 'web',
        ]);

         // if ($user->email) {
        //     try {
        //         Mail::to($user->email)->send(new \App\Mail\AttendanceConfirmationMail($student, $schedule, $attendance));
        //     } catch (\Exception $e) {
        //         Log::error('Attendance email failed: ' . $e->getMessage());
        //     }
        // }// }

        return redirect()->route('student.dashboard')->with('success', 'Clocked out successfully.');
    }

    public function emailRecord(Request $request, Attendance $attendance)
    {
        $user = $request->user();
        if ($user->role !== 'student' || $attendance->student_id !== $user->student->id) {
            abort(403);
        }

        if ($user->email) {
            try {
                // Determine which mail to send based on status/clock-out
                if ($attendance->clock_out_time) {
                     Mail::to($user->email)->send(new \App\Mail\AttendanceSummaryMail($user->student, $attendance->schedule, $attendance));
                } else {
                     Mail::to($user->email)->send(new \App\Mail\AttendanceConfirmationMail($user->student, $attendance->schedule, $attendance));
                }
                return back()->with('success', 'Attendance record emailed to ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Manual attendance email failed: ' . $e->getMessage());
                return back()->with('error', 'Failed to send email. Please try again.');
            }
        }
        return back()->with('error', 'No email address found for your account.');
    }
}
