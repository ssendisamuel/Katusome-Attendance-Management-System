<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\Page2;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScheduleSeriesController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\LecturerAttendanceController;

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\PasswordResetController;

// Main Page Route
Route::get('/', [HomePage::class, 'index'])->name('pages-home');
Route::get('/page-2', [Page2::class, 'index'])->name('pages-page-2');

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

// authentication
// Use simplified auth routes
Route::get('/login', [LoginBasic::class, 'index'])->name('login');
Route::get('/register', [RegisterBasic::class, 'index'])->name('register');
// Registration handling
Route::post('/register', [RegistrationController::class, 'store'])->name('register.post');

// Authentication handling
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin routes (require auth and admin gate)
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('programs', ProgramController::class)->except(['show']);
    Route::resource('groups', GroupController::class)->except(['show']);
    // Groups by program for cascading selects
    Route::get('programs/{program}/groups', [GroupController::class, 'byProgram'])->name('programs.groups');
    Route::resource('courses', CourseController::class)->except(['show']);
    Route::resource('students', StudentController::class)->except(['show']);
    // Students bulk upload
    Route::get('students/import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::get('students/import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
    Route::post('students/import', [StudentController::class, 'importProcess'])->name('students.import.process');
    Route::resource('lecturers', LecturerController::class)->except(['show']);
    Route::resource('schedules', ScheduleController::class)->except(['show']);
    Route::resource('series', ScheduleSeriesController::class)->except(['show']);
    // Generate schedules from a series
    Route::post('series/{series}/generate-schedules', [ScheduleSeriesController::class, 'generateSchedules'])
        ->name('series.generate-schedules');
    // Bulk generate schedules for all current-term series
    Route::post('series/generate-all', [ScheduleSeriesController::class, 'generateAll'])
        ->name('series.generate-all');
    Route::resource('attendance', AttendanceController::class)->only(['index', 'destroy']);

    // Admin dashboard view
    Route::get('dashboard', function () {
        $today = \Carbon\Carbon::today();

        // Entity counts
        $studentsCount = \App\Models\Student::count();
        $coursesCount = \App\Models\Course::count();
        $programsCount = \App\Models\Program::count();
        $groupsCount = \App\Models\Group::count();
        $lecturersCount = \App\Models\Lecturer::count();

        // Today classes and attendance
        $todaysClasses = \App\Models\Schedule::whereDate('start_at', $today)->count();

        $presentToday = \App\Models\Attendance::whereDate('marked_at', $today)
            ->where('status', 'present')
            ->count();
        $absentToday = \App\Models\Attendance::whereDate('marked_at', $today)
            ->where('status', 'absent')
            ->count();
        $lateToday = \App\Models\Attendance::whereDate('marked_at', $today)
            ->where('status', 'late')
            ->count();
        $attendanceTotalToday = $presentToday + $absentToday + $lateToday;
        $unmarkedToday = 0; // Placeholder: depends on expected attendance per class

        // Attendance rates
        $attendanceRateToday = $attendanceTotalToday > 0
            ? (round(($presentToday / max($attendanceTotalToday, 1)) * 100)) . '%'
            : '0%';
        $overallTotal = \App\Models\Attendance::count();
        $overallPresent = \App\Models\Attendance::where('status', 'present')->count();
        $attendanceRateOverall = $overallTotal > 0
            ? (round(($overallPresent / max($overallTotal, 1)) * 100)) . '%'
            : '0%';

        // Pending attendance: schedules today without any attendance records
        $pendingAttendance = \App\Models\Schedule::whereDate('start_at', $today)
            ->whereDoesntHave('attendanceRecords', function ($q) use ($today) {
                $q->whereDate('marked_at', $today);
            })
            ->count();

        return view('content.dashboards.admin', compact(
            'studentsCount',
            'coursesCount',
            'programsCount',
            'groupsCount',
            'lecturersCount',
            'todaysClasses',
            'attendanceRateToday',
            'attendanceRateOverall',
            'pendingAttendance',
            'presentToday',
            'absentToday',
            'lateToday',
            'unmarkedToday'
        ));
    })->name('dashboard');
});

// Student check-in routes
Route::middleware(['auth', 'can:student'])->group(function () {
    // Student dashboard
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $student = optional($user)->student;
        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access the dashboard.']);
        }

        $today = \Carbon\Carbon::today();
        $schedules = \App\Models\Schedule::with(['course', 'lecturer'])
            ->where('group_id', $student->group_id)
            ->whereDate('start_at', $today)
            ->orderBy('start_at')
            ->get();
        $attendanceBySchedule = \App\Models\Attendance::whereIn('schedule_id', $schedules->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('schedule_id');

        return view('content.dashboards.student', compact('student', 'schedules', 'attendanceBySchedule'));
    })->name('student.dashboard');

    // Existing generic check-in selection
    Route::get('/checkin', [StudentAttendanceController::class, 'create'])->name('attendance.checkin.create');
    // New: schedule-specific check-in
    Route::get('/checkin/{schedule}', [StudentAttendanceController::class, 'show'])
        ->name('attendance.checkin.show');
    Route::post('/checkin', [StudentAttendanceController::class, 'store'])->name('attendance.checkin.store');
});

// Lecturer marking routes
Route::middleware(['auth', 'can:lecturer'])->group(function () {
    Route::get('/lecturer/attendance', [LecturerAttendanceController::class, 'index'])->name('lecturer.attendance.index');
    Route::get('/lecturer/attendance/{schedule}/mark', [LecturerAttendanceController::class, 'edit'])->name('lecturer.attendance.edit');
    Route::post('/lecturer/attendance/{schedule}/mark', [LecturerAttendanceController::class, 'update'])->name('lecturer.attendance.update');
});

// Password reset routes
Route::get('/password/reset', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/password/email', [PasswordResetController::class, 'email'])->name('password.email');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'update'])->name('password.update');

