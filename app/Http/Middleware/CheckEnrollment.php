<?php

namespace App\Http\Middleware;

use App\Models\AcademicSemester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEnrollment
{
    /**
     * Handle an incoming request.
     * Redirects students to enrollment page if active semester exists and they're not enrolled
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Only apply to students
        if (!$user || !$user->student || $user->role !== 'student') {
            return $next($request);
        }

        $student = $user->student;
        $activeSemester = AcademicSemester::where('is_active', true)->first();

        // If no active semester, proceed normally
        if (!$activeSemester) {
            return $next($request);
        }

        // Check if student is enrolled in active semester
        $isEnrolled = $student->isEnrolledInSemester($activeSemester->id);

        // If user must change password, allow them to proceed to password change routes (handled by ForcePasswordChange middleware)
        // This prevents redirect loop: ForcePassword -> CheckEnrollment -> ForcePassword
        if ($user->must_change_password || $request->is('password/*') || $request->routeIs('password.*')) {
            return $next($request);
        }

        // If not enrolled and not currently on enrollment page, redirect
        if (!$isEnrolled && !$request->is('enrollment')) {
            return redirect()->route('enrollment.show')
                ->with('info', "Please enroll in {$activeSemester->display_name} to continue.");
        }

        return $next($request);
    }
}
