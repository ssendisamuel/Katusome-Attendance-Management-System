<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramCourseController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::with('faculty')->orderBy('name')->get();
        $selectedProgram = null;

        if ($request->filled('program_id')) {
            $selectedProgram = Program::with(['faculty', 'courses' => function ($q) {
                $q->orderByPivot('year_of_study')->orderByPivot('semester_offered')->orderBy('code');
            }])->find($request->integer('program_id'));
        }

        // Structure courses by year → semester for the selected program
        $structure = [];
        if ($selectedProgram) {
            // First, pre-fill the structure based on the program's duration
            $durationYears = $selectedProgram->duration_years ?? 4; // Default to 4 if not set
            for ($year = 1; $year <= $durationYears; $year++) {
                $structure[$year] = [
                    'Semester 1' => [],
                    'Semester 2' => [],
                ];
            }

            // Then, populate with actual assigned courses
            foreach ($selectedProgram->courses as $course) {
                $year = $course->pivot->year_of_study;
                $sem = $course->pivot->semester_offered;

                // Only add to the structure if the year is within reasonable bounds
                // (in case a course was assigned to a year that is now outside the duration)
                if (!isset($structure[$year])) {
                     $structure[$year] = [
                         'Semester 1' => [],
                         'Semester 2' => [],
                     ];
                }

                // Fallback for unexpected semester names
                if (!isset($structure[$year][$sem])) {
                    $structure[$year][$sem] = [];
                }

                $structure[$year][$sem][] = $course;
            }
            ksort($structure); // Sort by year
        }

        $courses = Course::orderBy('code')->get();

        return view('admin.program-courses.index', compact('programs', 'selectedProgram', 'structure', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'course_id' => ['required', 'array', 'min:1'],
            'course_id.*' => ['exists:courses,id'],
            'year_of_study' => ['required', 'integer', 'min:1', 'max:7'],
            'semester_offered' => ['required', 'in:Semester 1,Semester 2'],
            'course_type' => ['required', 'in:Core,Elective,Audit'],
        ]);

        $program = Program::findOrFail($validated['program_id']);

        $assigned = 0;
        $skipped = 0;

        foreach ($validated['course_id'] as $cId) {
            $course = Course::findOrFail($cId);

            // Check if course is already attached to this program anywhere
            $existingPivot = $program->courses()->where('courses.id', $cId)->first();

            if ($existingPivot) {
                // Course is already in the program.
                // Check if it's already exactly the same assignment to avoid redundant updates
                if (
                    $existingPivot->pivot->year_of_study == $validated['year_of_study'] &&
                    $existingPivot->pivot->semester_offered == $validated['semester_offered'] &&
                    $existingPivot->pivot->course_type == $validated['course_type']
                ) {
                    $skipped++;
                    continue;
                }

                // Update existing assignment to new year/semester
                $program->courses()->updateExistingPivot($cId, [
                    'year_of_study' => $validated['year_of_study'],
                    'semester_offered' => $validated['semester_offered'],
                    'course_type' => $validated['course_type'],
                ]);
            } else {
                // Attach new course
                $program->courses()->attach($cId, [
                    'year_of_study' => $validated['year_of_study'],
                    'semester_offered' => $validated['semester_offered'],
                    'credit_units' => $course->credit_units ?? 4,
                    'course_type' => $validated['course_type'],
                ]);
            }
            $assigned++;
        }

        $msg = "Assigned {$assigned} course(s).";
        if ($skipped > 0) {
            $msg .= " Skipped {$skipped} course(s) already assigned to this year and semester.";
            return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
                ->with($assigned > 0 ? 'success' : 'warning', $msg);
        }

        return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
            ->with('success', $msg);
    }

    public function update(Request $request, Program $program, Course $course)
    {
        $validated = $request->validate([
            'year_of_study' => ['required', 'integer', 'min:1', 'max:7'],
            'semester_offered' => ['required', 'in:Semester 1,Semester 2'],
            'course_type' => ['required', 'in:Core,Elective,Audit'],
        ]);

        $program->courses()->updateExistingPivot($course->id, $validated);

        return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Program $program, Course $course)
    {
        $program->courses()->detach($course->id);
        return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
            ->with('success', 'Course removed from programme.');
    }
}
