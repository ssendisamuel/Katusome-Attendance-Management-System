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
use App\Http\Controllers\Admin\ProgramCourseController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScheduleSeriesController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\MailStatusController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\CourseLecturerController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\LecturerAttendanceController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\Admin\DashboardController;

use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Admin\AcademicSemesterController;

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
    // Course-Program Assignments
    Route::get('program-courses', [ProgramCourseController::class, 'index'])->name('program-courses.index');
    Route::get('program-courses/create', [ProgramCourseController::class, 'create'])->name('program-courses.create');
    Route::post('program-courses', [ProgramCourseController::class, 'store'])->name('program-courses.store');
    Route::get('program-courses/{program}/{course}/edit', [ProgramCourseController::class, 'edit'])->name('program-courses.edit');
    Route::put('program-courses/{program}/{course}', [ProgramCourseController::class, 'update'])->name('program-courses.update');
    Route::delete('program-courses/{program}/{course}', [ProgramCourseController::class, 'destroy'])->name('program-courses.destroy');
        // Assign lecturers to courses
    Route::get('course-lecturers', [CourseLecturerController::class, 'index'])->name('course-lecturers.index');
    Route::get('course-lecturers/{course}/edit', [CourseLecturerController::class, 'edit'])->name('course-lecturers.edit');
    Route::put('course-lecturers/{course}', [CourseLecturerController::class, 'update'])->name('course-lecturers.update');
    // Students bulk upload
    Route::get('students/import', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::get('students/import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
    Route::post('students/import', [StudentController::class, 'importProcess'])->name('students.import.process');
    Route::resource('students', StudentController::class);
    Route::resource('lecturers', LecturerController::class)->except(['show']);
    // Lecturers search for Select2
    Route::get('lecturers/search', [LecturerController::class, 'search'])->name('lecturers.search');
    // Schedules
    Route::delete('schedules/bulk-destroy', [ScheduleController::class, 'bulkDestroy'])
        ->name('schedules.bulk-destroy');
    Route::patch('schedules/bulk-update', [ScheduleController::class, 'bulkUpdate'])
        ->name('schedules.bulk-update');
    Route::resource('schedules', ScheduleController::class)->except(['show']);
    Route::post('schedules/{schedule}/status', [ScheduleController::class, 'updateStatus'])->name('schedules.status');

    // AJAX for Series creation: Get program details (code and courses)
    Route::get('series/program-details/{program}', [ScheduleSeriesController::class, 'getProgramDetails'])->name('series.program-details');

    // Series
    Route::delete('series/bulk-destroy', [ScheduleSeriesController::class, 'bulkDestroy'])
        ->name('series.bulk-destroy');
    Route::resource('series', ScheduleSeriesController::class)->except(['show']);
    // Generate schedules from a series
    Route::post('series/{series}/generate-schedules', [ScheduleSeriesController::class, 'generateSchedules'])
        ->name('series.generate-schedules');
    // Bulk generate schedules for all current-term series
    Route::post('series/generate-all', [ScheduleSeriesController::class, 'generateAll'])
        ->name('series.generate-all');
    Route::post('attendance/bulk-action', [AttendanceController::class, 'bulkAction'])->name('attendance.bulk-action');
    Route::resource('attendance', AttendanceController::class)->only(['index', 'destroy', 'create', 'store', 'update']);
    Route::get('attendance/students', [AttendanceController::class, 'students'])->name('attendance.students');

    // Academic Semesters
    Route::resource('academic-semesters', AcademicSemesterController::class)->except(['show']);
    Route::post('academic-semesters/{academicSemester}/activate', [AcademicSemesterController::class, 'activate'])
        ->name('academic-semesters.activate');
    Route::post('academic-semesters/{academicSemester}/deactivate', [AcademicSemesterController::class, 'deactivate'])
        ->name('academic-semesters.deactivate');

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
    // Admin User Management
    Route::resource('admins', \App\Http\Controllers\Admin\AdminUserController::class);

    // Course Leaders Management
    Route::get('course-leaders/students/search', [\App\Http\Controllers\Admin\CourseLeaderController::class, 'searchStudents'])->name('course-leaders.students.search');
    Route::resource('course-leaders', \App\Http\Controllers\Admin\CourseLeaderController::class)->only(['index', 'store', 'destroy']);

    // Admin Reports
    Route::get('reports/course', [ReportsController::class, 'course'])->name('reports.course');
    Route::get('reports/group', [ReportsController::class, 'group'])->name('reports.group');
    Route::get('reports/program', [ReportsController::class, 'program'])->name('reports.program');
    Route::get('reports/session/{schedule}', [ReportsController::class, 'session'])->name('reports.session');

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

    // Settings - Venues
    Route::resource('settings/venues', \App\Http\Controllers\Admin\VenueController::class)->names('settings.venues');

    // Admin dashboard view
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // System Status & Settings
    Route::get('system-status', [\App\Http\Controllers\Admin\SystemStatusController::class, 'index'])->name('system-status.index');
    Route::match(['get', 'post'], 'system-status/run-auto-clock-out', [\App\Http\Controllers\Admin\SystemStatusController::class, 'runAutoClockOut'])->name('system-status.run-auto-clock-out');
    Route::match(['get', 'post'], 'system-status/run-mark-absent', [\App\Http\Controllers\Admin\SystemStatusController::class, 'runMarkAbsent'])->name('system-status.run-mark-absent');

    // No queued email retry route; delivery is direct via SMTP
});

// Student check-in routes
Route::middleware(['auth', 'can:student'])->group(function () {
    // Student dashboard
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    // Student dashboard courses JSON for DataTable
    Route::get('/dashboard/courses', [StudentDashboardController::class, 'coursesJson'])->name('student.dashboard.courses');

    // Student My Courses Page
    Route::get('/student/courses', [StudentDashboardController::class, 'courses'])->name('student.courses');
    Route::post('/student/courses/retake', [StudentDashboardController::class, 'storeRetakeCourse'])->name('student.courses.retake');
    Route::delete('/student/courses/retake/{id}', [StudentDashboardController::class, 'destroyRetake'])->name('student.courses.retake.destroy');
    Route::post('/student/courses/drop/{course_id}', [StudentDashboardController::class, 'dropCourse'])->name('student.courses.drop');

    // AJAX for dropdowns
    Route::get('/student/api/years', [StudentDashboardController::class, 'getProgramYears'])->name('student.api.years');
    Route::get('/student/api/courses', [StudentDashboardController::class, 'getProgramCourses'])->name('student.api.courses');
    Route::get('/student/api/groups', [StudentDashboardController::class, 'getProgramGroups'])->name('student.api.groups');

    // Student attendance (today-only dedicated page)
    Route::get('/attendance', [StudentAttendanceController::class, 'today'])->name('student.attendance.today');

    // Existing generic check-in selection
    Route::get('/checkin', [StudentAttendanceController::class, 'create'])->name('attendance.checkin.create');
    // New: schedule-specific check-in
    Route::get('/checkin/{schedule}', [StudentAttendanceController::class, 'show'])
        ->name('attendance.checkin.show');
    Route::post('/checkin', [StudentAttendanceController::class, 'store'])->name('attendance.checkin.store');
    // Clock-out routes
    Route::get('/attendance/{attendance}/clock-out', [StudentAttendanceController::class, 'showClockOut'])
        ->name('attendance.clockout.show');
    Route::post('/attendance/{attendance}/clock-out', [StudentAttendanceController::class, 'clockOut'])
        ->name('attendance.clockout');

    // New: Attendance summary page for a specific recorded attendance
    Route::get('/attendance/summary/{attendance}', [StudentAttendanceController::class, 'summary'])
        ->name('attendance.summary');

    // Email Route for single record
    Route::post('/attendance/email/{attendance}', [StudentAttendanceController::class, 'emailRecord'])
        ->name('attendance.email');

    // Student Reports
    Route::get('/reports', [\App\Http\Controllers\Student\ReportController::class, 'index'])->name('student.reports.index');
    Route::post('/reports/email', [\App\Http\Controllers\Student\ReportController::class, 'emailReport'])->name('student.reports.email');

    // Course Leader Dashboard
    Route::prefix('course-leader')->name('student.course-leader.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Student\CourseLeaderController::class, 'index'])->name('dashboard');
        Route::post('/schedules/{schedule}/status', [\App\Http\Controllers\Student\CourseLeaderController::class, 'updateStatus'])->name('schedules.status');
        Route::post('/schedules/{schedule}/venue', [\App\Http\Controllers\Student\CourseLeaderController::class, 'updateVenue'])->name('schedules.venue');
        Route::post('/schedules/{schedule}/mode', [\App\Http\Controllers\Student\CourseLeaderController::class, 'updateMode'])->name('schedules.mode');
        Route::post('/schedules/{schedule}/actuals', [\App\Http\Controllers\Student\CourseLeaderController::class, 'logActuals'])->name('schedules.actuals');
    });
});

// Student enrollment routes (separate from student routes to avoid middleware redirect loop)
Route::middleware(['auth'])->group(function () {
    Route::get('/enrollment', [EnrollmentController::class, 'show'])->name('enrollment.show');
    Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');
});

// Lecturer marking routes
Route::middleware(['auth', 'can:lecturer'])->group(function () {
    Route::get('/lecturer/dashboard', [\App\Http\Controllers\Lecturer\DashboardController::class, 'index'])->name('lecturer.dashboard');

    Route::prefix('lecturer/attendance')->name('lecturer.attendance.')->group(function () {
        Route::get('/', [LecturerAttendanceController::class, 'index'])->name('index');
        Route::get('/today', [LecturerAttendanceController::class, 'today'])->name('today');
        Route::get('/create', [LecturerAttendanceController::class, 'create'])->name('create');
        Route::post('/', [LecturerAttendanceController::class, 'store'])->name('store');
        Route::get('/students', [LecturerAttendanceController::class, 'students'])->name('students');
        Route::get('/{schedule}/mark', [LecturerAttendanceController::class, 'edit'])->name('edit');
        Route::post('/{schedule}/mark', [LecturerAttendanceController::class, 'update'])->name('update');
    });

    // Lecturer reports
    Route::get('/lecturer/reports', [\App\Http\Controllers\LecturerReportsController::class, 'dashboard'])->name('lecturer.reports.dashboard');
    Route::get('/lecturer/reports/daily', [\App\Http\Controllers\LecturerReportsController::class, 'daily'])->name('lecturer.reports.daily');
    Route::get('/lecturer/reports/monthly', [\App\Http\Controllers\LecturerReportsController::class, 'monthly'])->name('lecturer.reports.monthly');
    Route::get('/lecturer/reports/individual', [\App\Http\Controllers\LecturerReportsController::class, 'individual'])->name('lecturer.reports.individual');
    // Student suggestions for individual report
    Route::get('/lecturer/reports/students/search', [\App\Http\Controllers\LecturerReportsController::class, 'studentsSearch'])->name('lecturer.reports.students.search');
    // CSV exports
    Route::get('/lecturer/reports/daily/export/csv', [\App\Http\Controllers\LecturerReportsController::class, 'exportDailyCsv'])->name('lecturer.reports.daily.export.csv');
    Route::get('/lecturer/reports/monthly/export/csv', [\App\Http\Controllers\LecturerReportsController::class, 'exportMonthlyCsv'])->name('lecturer.reports.monthly.export.csv');
    Route::get('/lecturer/reports/individual/export/csv', [\App\Http\Controllers\LecturerReportsController::class, 'exportIndividualCsv'])->name('lecturer.reports.individual.export.csv');

    // Lecturer Schedule Management (New)
    Route::resource('/lecturer/schedules', \App\Http\Controllers\Lecturer\ScheduleController::class)
        ->names('lecturer.schedules')
        ->except(['show']); // index, create, store, edit, update, destroy
    Route::get('/lecturer/schedules/{schedule}', [\App\Http\Controllers\Lecturer\ScheduleController::class, 'show'])->name('lecturer.schedules.show');
    Route::post('/lecturer/schedules/{schedule}/status', [\App\Http\Controllers\Lecturer\ScheduleController::class, 'updateStatus'])->name('lecturer.schedules.status');

    // Lecturer Schedule Series Management
    Route::resource('/lecturer/series', \App\Http\Controllers\Lecturer\ScheduleSeriesController::class)
        ->names('lecturer.series')
        ->except(['show']); // index, create, store, edit, update, destroy

    Route::post('/lecturer/series/{series}/generate-schedules', [\App\Http\Controllers\Lecturer\ScheduleSeriesController::class, 'generateSchedules'])
        ->name('lecturer.series.generate-schedules');
});

// Change password (for authenticated users)
Route::middleware(['auth'])->group(function () {
    // Mail status polling endpoints (authenticated)
    Route::get('/mail/status/welcome', [MailStatusController::class, 'welcome'])->name('mail.status.welcome');
    // Attendance confirmation mail status by attendance id
    Route::get('/mail/status/attendance', [MailStatusController::class, 'attendance'])->name('mail.status.attendance');
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
