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
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\LecturerAttendanceController;
use App\Http\Controllers\StudentDashboardController;

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleController;

// Main Page Route -> Redirect to dashboards
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        if ($user->role === 'student') {
            return redirect()->route('student.dashboard');
        }
        if ($user->role === 'lecturer') {
            return redirect()->route('lecturer.attendance.index');
        }
    }
    return redirect()->route('login');
});
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

// Google OAuth routes
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('oauth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('oauth.google.callback');
Route::match(['get','post'], '/auth/google/complete-profile', [GoogleController::class, 'completeProfile'])
    ->name('oauth.google.complete-profile');

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

    // Reports
    Route::get('reports', [ReportsController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('reports/daily', [ReportsController::class, 'daily'])->name('reports.daily');
    Route::get('reports/monthly', [ReportsController::class, 'monthly'])->name('reports.monthly');
    Route::get('reports/individual', [ReportsController::class, 'individual'])->name('reports.individual');
    // Student search suggestions for individual report
    Route::get('reports/students/search', [StudentController::class, 'search'])
        ->name('reports.students.search');
    Route::get('reports/absenteeism', [ReportsController::class, 'absenteeism'])->name('reports.absenteeism');
    Route::get('reports/devices', [ReportsController::class, 'devices'])->name('reports.devices');

    // Export endpoints
    Route::get('reports/daily.csv', [ReportsController::class, 'exportDailyCsv'])->name('reports.daily.csv');
    Route::get('reports/monthly.csv', [ReportsController::class, 'exportMonthlyCsv'])->name('reports.monthly.csv');
    Route::get('reports/individual.csv', [ReportsController::class, 'exportIndividualCsv'])->name('reports.individual.csv');
    Route::get('reports/absenteeism.csv', [ReportsController::class, 'exportAbsenteeismCsv'])->name('reports.absenteeism.csv');
    Route::get('reports/devices.csv', [ReportsController::class, 'exportDevicesCsv'])->name('reports.devices.csv');

    // JSON export endpoints for full dataset
    Route::get('reports/daily.json', [ReportsController::class, 'dailyJson'])->name('reports.daily.json');
    Route::get('reports/monthly.json', [ReportsController::class, 'monthlyJson'])->name('reports.monthly.json');
    Route::get('reports/individual.json', [ReportsController::class, 'individualJson'])->name('reports.individual.json');
    Route::get('reports/absenteeism.json', [ReportsController::class, 'absenteeismJson'])->name('reports.absenteeism.json');
    Route::get('reports/devices.json', [ReportsController::class, 'devicesJson'])->name('reports.devices.json');

    // Settings - Location
    Route::get('settings/location', [SettingsController::class, 'locationEdit'])->name('settings.location.edit');
    Route::put('settings/location', [SettingsController::class, 'locationUpdate'])->name('settings.location.update');

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

        // Failed welcome emails
        $failedWelcomeEmails = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->where('payload', 'like', '%WelcomeUserMail%')
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
            'unmarkedToday',
            'failedWelcomeEmails'
        ));
    })->name('dashboard');

    // Retry failed welcome emails
    Route::post('email/failed-retry', function () {
        $uuids = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->where('payload', 'like', '%WelcomeUserMail%')
            ->pluck('uuid')
            ->all();

        if (count($uuids) > 0) {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => $uuids]);
            return back()->with('success', 'Retrying failed welcome emails');
        }

        return back()->with('error', 'No failed welcome emails to retry');
    })->name('email.failed.retry');
});

// Student check-in routes
Route::middleware(['auth', 'can:student'])->group(function () {
    // Student dashboard
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    // Student dashboard courses JSON for DataTable
    Route::get('/dashboard/courses', [StudentDashboardController::class, 'coursesJson'])->name('student.dashboard.courses');

    // Student attendance (today-only dedicated page)
    Route::get('/attendance', [StudentAttendanceController::class, 'today'])->name('student.attendance.today');

    // Existing generic check-in selection
    Route::get('/checkin', [StudentAttendanceController::class, 'create'])->name('attendance.checkin.create');
    // New: schedule-specific check-in
    Route::get('/checkin/{schedule}', [StudentAttendanceController::class, 'show'])
        ->name('attendance.checkin.show');
    Route::post('/checkin', [StudentAttendanceController::class, 'store'])->name('attendance.checkin.store');

    // New: Attendance summary page for a specific recorded attendance
    Route::get('/attendance/summary/{attendance}', [StudentAttendanceController::class, 'summary'])
        ->name('attendance.summary');
});

// Lecturer marking routes
Route::middleware(['auth', 'can:lecturer'])->group(function () {
    Route::get('/lecturer/attendance', [LecturerAttendanceController::class, 'index'])->name('lecturer.attendance.index');
    Route::get('/lecturer/attendance/{schedule}/mark', [LecturerAttendanceController::class, 'edit'])->name('lecturer.attendance.edit');
    Route::post('/lecturer/attendance/{schedule}/mark', [LecturerAttendanceController::class, 'update'])->name('lecturer.attendance.update');
});

// Change password (for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/account/change-password', [ChangePasswordController::class, 'edit'])->name('password.change.edit');
    Route::post('/account/change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    // Profile routes
    Route::get('/account/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/account/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Password reset routes
Route::get('/password/reset', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/password/email', [PasswordResetController::class, 'email'])->name('password.email');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'update'])->name('password.update');

// Dev: Preview attendance confirmation email
Route::get('/dev/preview-email/attendance', function () {
    $student = \App\Models\Student::with('user')->first();
    $schedule = $student
        ? \App\Models\Schedule::with('course')->where('group_id', $student->group_id)->orderByDesc('start_at')->first()
        : \App\Models\Schedule::with('course')->orderByDesc('start_at')->first();
    $attendance = ($student && $schedule)
        ? \App\Models\Attendance::firstOrCreate([
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
        ], [
            'status' => 'present',
            'marked_at' => \Carbon\Carbon::now(),
        ])
        : new \App\Models\Attendance(['status' => 'present', 'marked_at' => \Carbon\Carbon::now()]);

    $mailable = new \App\Mail\AttendanceConfirmationMail(
        $student ?? new \App\Models\Student(),
        $schedule ?? new \App\Models\Schedule(),
        $attendance
    );
    return $mailable->render();
});

