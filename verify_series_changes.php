<?php

use App\Models\ScheduleSeries;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\AcademicSemester;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

echo "Starting Verification...\n";

// 1. Setup Data
$course = Course::first() ?? Course::factory()->create();
$group = Group::first() ?? Group::factory()->create();
$lecturer = Lecturer::first() ?? Lecturer::factory()->create();
$semester = AcademicSemester::where('is_active', true)->first() ?? AcademicSemester::first();

if (!$course || !$group || !$semester) {
    echo "Error: Missing base data (Course, Group, or Semester).\n";
    exit(1);
}

// 2. Verify Series Creation & Schedule Generation with requires_clock_out
echo "\nTest 1: Series Creation & Schedule Generation...\n";
$seriesData = [
    'name' => 'Test Series ' . rand(1000, 9999),
    'course_id' => $course->id,
    'group_id' => $group->id,
    'lecturer_id' => $lecturer->id,
    'academic_semester_id' => $semester->id,
    'start_date' => Carbon::today(),
    'end_date' => Carbon::today()->addDays(7),
    'start_time' => '08:00',
    'end_time' => '10:00',
    'days_of_week' => [strtolower(Carbon::today()->format('D'))], // Today
    'is_recurring' => true,
    'is_online' => true,
    'requires_clock_out' => true, // TESTING THIS
];

$controller = new \App\Http\Controllers\Admin\ScheduleSeriesController();
$request = Request::create(route('admin.series.store'), 'POST', $seriesData);
$response = $controller->store($request);

$series = ScheduleSeries::where('name', $seriesData['name'])->first();

if ($series && $series->requires_clock_out) {
    echo "PASS: Series created with requires_clock_out = true.\n";
} else {
    echo "FAIL: Series requires_clock_out is " . ($series->requires_clock_out ?? 'null') . "\n";
}

// Generate Schedules
$genRequest = Request::create(route('admin.series.generate', $series), 'POST', ['overwrite' => true]);
$controller->generateSchedules($genRequest, $series);

$schedule = Schedule::where('series_id', $series->id)->first();
if ($schedule && $schedule->requires_clock_out) {
    echo "PASS: Generated Schedule inherited requires_clock_out = true.\n";
} else {
    echo "FAIL: Schedule requires_clock_out is " . ($schedule->requires_clock_out ?? 'null') . "\n";
}

// 3. Verify Schedule Update Bug (Online Toggle)
echo "\nTest 2: Schedule Update Bug (Online Toggle)...\n";
$schedController = new \App\Http\Controllers\Admin\ScheduleController();

// Create a schedule that is ONLINE
$testSched = Schedule::create([
    'course_id' => $course->id,
    'group_id' => $group->id,
    'start_at' => Carbon::now()->addHours(1),
    'end_at' => Carbon::now()->addHours(3),
    'is_online' => true,
    'requires_clock_out' => true,
]);

// Update request WITHOUT 'is_online' (simulating unchecked checkbox)
$updateData = [
    'course_id' => $course->id,
    'group_id' => $group->id,
    'start_at' => $testSched->start_at,
    'end_at' => $testSched->end_at,
    // 'is_online' is MISSING
];
$updateReq = Request::create(route('admin.schedules.update', $testSched), 'PUT', $updateData);
$schedController->update($updateReq, $testSched);

$testSched->refresh();
if ($testSched->is_online === false) {
    echo "PASS: Schedule is_online updated to false when missing from request.\n";
} else {
    echo "FAIL: Schedule is_online remained true.\n";
}


// 4. Verify Auto-Resolve "Incomplete" Status
echo "\nTest 3: Auto-Resolve 'Incomplete' Status...\n";

// Create a past schedule that required clock out
$pastSched = Schedule::create([
    'course_id' => $course->id,
    'group_id' => $group->id,
    'start_at' => Carbon::now()->subHours(2),
    'end_at' => Carbon::now()->subHours(1), // Ended 1 hour ago
    'requires_clock_out' => true,
]);

// Create an attendance record (Checked In, No Clock Out)
$student = Student::first() ?? Student::factory()->create();
$attendance = Attendance::create([
    'schedule_id' => $pastSched->id,
    'student_id' => $student->id,
    'status' => 'present',
    'clock_in_time' => $pastSched->start_at,
    'marked_at' => $pastSched->start_at,
]);

// Run Command
Illuminate\Support\Facades\Artisan::call('attendance:auto-resolve');
$output = Illuminate\Support\Facades\Artisan::output();
echo "Command Output: " . trim($output) . "\n";

$attendance->refresh();

if ($attendance->status === 'incomplete' && $attendance->is_auto_clocked_out) {
    echo "PASS: Attendance status updated to 'incomplete' and is_auto_clocked_out = true.\n";
} else {
    echo "FAIL: Attendance status is '{$attendance->status}', is_auto_clocked_out is " . ($attendance->is_auto_clocked_out ? 'true' : 'false') . "\n";
}

echo "\nVerification Complete.\n";
