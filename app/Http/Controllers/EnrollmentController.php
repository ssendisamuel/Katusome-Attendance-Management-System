<?php

namespace App\Http\Controllers;

use App\Models\AcademicSemester;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Show the enrollment form for the active semester
     */
    public function show()
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to enroll.']);
        }

        $activeSemester = AcademicSemester::where('is_active', true)->first();

        if (!$activeSemester) {
            return redirect()->route('student.dashboard')
                ->with('info', 'No active semester for enrollment at this time.');
        }

        // Check if already enrolled
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_semester_id', $activeSemester->id)
            ->first();

        // Get all programs and groups for selection
        $programs = \App\Models\Program::all();
        $groups = \App\Models\Group::all();

        return view('enrollment.show', compact('student', 'activeSemester', 'programs', 'groups', 'enrollment'));
    }

    /**
     * Store the enrollment
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $student = optional($user)->student;

        if (!$student) {
            return redirect()->route('login')->withErrors(['email' => 'You must be a student to enroll.']);
        }

        $activeSemester = AcademicSemester::where('is_active', true)->first();

        if (!$activeSemester) {
            return back()->withErrors(['semester' => 'No active semester for enrollment.']);
        }

        $data = $request->validate([
            'year_of_study' => ['required', 'integer', 'min:1', 'max:4'],
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        // Create or Update enrollment
        StudentEnrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_semester_id' => $activeSemester->id,
            ],
            [
                'year_of_study' => $data['year_of_study'],
                'program_id' => $data['program_id'],
                'group_id' => $data['group_id'],
                'enrolled_at' => now(), // Update timestamp
            ]
        );

        // Also update the student's main record to reflect current program/group
        $student->update([
            'program_id' => $data['program_id'],
            'group_id' => $data['group_id'],
            'year_of_study' => $data['year_of_study'],
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', "Successfully enrolled in {$activeSemester->display_name}!");
    }
}
