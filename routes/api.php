<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentAttendanceController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::get('/programs', [\App\Http\Controllers\Api\ApiController::class, 'getPrograms']);
Route::get('/groups', [\App\Http\Controllers\Api\ApiController::class, 'getAllGroups']);
Route::get('/programs/{program}/groups', [\App\Http\Controllers\Api\ApiController::class, 'getGroups']);
Route::get('/semester/active', [\App\Http\Controllers\Api\ApiController::class, 'getActiveSemester']);

// Password Reset Email
Route::post('/password/email', function (Request $request) {
    $request->validate(['email' => 'required|email']);
    $status = \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));
    if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
        return response()->json(['message' => 'Reset link sent!']);
    }
    return response()->json(['message' => __($status)], 400);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('student');
    });

    // Student Routes
    Route::middleware('can:student')->group(function () {
        // Attendance
        Route::get('/student/schedules/today', [StudentAttendanceController::class, 'today']);
        Route::post('/student/attendance', [StudentAttendanceController::class, 'store']);
        Route::post('/student/clock-out', [StudentAttendanceController::class, 'clockOut']);

        // Dashboard & Courses
        Route::get('/student/dashboard', [\App\Http\Controllers\Api\StudentDashboardController::class, 'index']);
        Route::get('/student/courses', [\App\Http\Controllers\Api\StudentDashboardController::class, 'courses']);
        Route::get('/student/courses/retake-data', [\App\Http\Controllers\Api\StudentDashboardController::class, 'getRetakeData']);
        Route::post('/student/courses', [\App\Http\Controllers\Api\StudentDashboardController::class, 'storeRetake']);
        Route::delete('/student/courses/{id}', [\App\Http\Controllers\Api\StudentDashboardController::class, 'destroyRetake']);
        Route::get('/student/history', [\App\Http\Controllers\Api\StudentDashboardController::class, 'history']);

        // Profile
        Route::get('/student/profile', [\App\Http\Controllers\Api\StudentProfileController::class, 'index']);
        Route::post('/student/profile', [\App\Http\Controllers\Api\StudentProfileController::class, 'update']);
        Route::post('/student/enrollment', [\App\Http\Controllers\Api\StudentProfileController::class, 'updateEnrollment']);
        Route::post('/student/change-password', [\App\Http\Controllers\Api\StudentProfileController::class, 'changePassword']);
        Route::post('/student/profile/photo', [\App\Http\Controllers\Api\StudentProfileController::class, 'updatePhoto']);
    });
});
