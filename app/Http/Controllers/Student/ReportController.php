<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\AcademicSemester;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to access this page.']);
        }

        $query = Attendance::with(['schedule.course', 'schedule.group'])
            ->where('student_id', $student->id);

        // Filters
        if ($semesterId = $request->input('semester_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('academic_semester_id', $semesterId));
        }

        if ($courseId = $request->input('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $courseId));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Year of Study Filter (via Pivot on Enrollment or just Schedule -> Course -> Program Pivot?)
        // Schedule -> Course -> Programs -> Pivot (year_of_study)
        // Hard to filter strictly by "Year of Study" on just Attendance records without a Join.
        // But we can filter schedules that belong to a course offered in that year.
        if ($year = $request->input('year_of_study')) {
             // Basic approach: Filter schedules where the course is offered in this year for the student's program
             // But student might take a Year 1 course in Year 2.
             // If we want "Record from when I was in Year 1", we strictly need Enrollment history which is complex.
             // Let's assume "Course Level" for now, or just filter by the Course's target year.
             // Simpler: Filter by `schedule.course.programs` pivot?
             // Let's assume the user just wants to filter by the "Year of Study" field in the StudentEnrollment that was active?
             // Too complex for now.
             // Let's stick to: "Show me records for courses that are nominally Year X courses".
             $query->whereHas('schedule.course.programs', function($q) use ($year) {
                 $q->where('course_program.year_of_study', $year);
             });
        }

        // Date Range (Default to Today if neither start nor end is provided)
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate && !$endDate) {
            // Default to Today
            $query->whereDate('marked_at', Carbon::today());
        } else {
             if ($startDate) {
                 $query->whereDate('marked_at', '>=', $startDate);
             }
             if ($endDate) {
                 $query->whereDate('marked_at', '<=', $endDate);
             }
        }

        $records = $query->orderByDesc('marked_at')->paginate(20);

        // Data for filters
        $courses = Course::whereHas('schedules', function($q) use ($student) {
            $q->whereHas('attendanceRecords', fn($sq) => $sq->where('student_id', $student->id));
        })->orderBy('name')->get();

        $semesters = AcademicSemester::orderByDesc('start_date')->get();
        // Years 1-4
        $years = [1, 2, 3, 4];

        return view('student.reports.index', [
            'records' => $records,
            'allCourses' => $courses,
            'semesters' => $semesters,
            'years' => $years
        ]);
    }public function emailReport(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) abort(404);

        $query = Attendance::with(['schedule.course'])
            ->where('student_id', $student->id);

        // Apply same filters
        if ($courseId = $request->input('course_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('course_id', $courseId));
        }
        if ($semesterId = $request->input('semester_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('academic_semester_id', $semesterId));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($date = $request->input('date')) {
            $query->whereDate('marked_at', $date);
        }

        $records = $query->orderByDesc('marked_at')->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'No records found to email.');
        }

        try {
            Mail::to($request->user()->email)->send(new \App\Mail\StudentReportMail($student, $records));
            return back()->with('success', 'Report sent to ' . $request->user()->email);
        } catch (\Exception $e) {
            Log::error('Student report email failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send email. Please try again later.');
        }
    }
}
