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
        $programId = $request->input('program_id');
        $query = Program::with(['courses'])
            ->when($programId, function ($q) use ($programId) {
                return $q->where('id', $programId);
            });

        // If filtering by program, we show courses for that program.
        // But the structure might be better if we list the Pivot entries directly?
        // Actually, listing "Assignments" makes sense.

        // Let's list Programs and their course counts, or flatten the list.
        // Flattening is better for "Manage Assessments" view.

        $programs = Program::orderBy('name')->get();

        // If specific program selected, show its courses
        $selectedProgram = null;
        if ($programId) {
            $selectedProgram = Program::with(['courses' => function($q) use ($request) {
                // Apply filters if present
                if ($request->filled('year_of_study')) {
                    $q->wherePivot('year_of_study', $request->year_of_study);
                }
                if ($request->filled('semester_offered')) {
                    $q->wherePivot('semester_offered', $request->semester_offered);
                }
                if ($request->filled('course_type')) {
                    $q->wherePivot('course_type', $request->course_type);
                }
                $q->orderByPivot('year_of_study')->orderByPivot('semester_offered');
            }])->find($programId);
        }

        return view('admin.program-courses.index', compact('programs', 'selectedProgram'));
    }

    public function create(Request $request)
    {
        $programs = Program::orderBy('name')->get();
        $courses = Course::orderBy('code')->get();
        $selectedProgramId = $request->input('program_id');

        return view('admin.program-courses.create', compact('programs', 'courses', 'selectedProgramId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'course_id' => ['required', 'exists:courses,id',
                Rule::unique('course_program')->where(function ($query) use ($request) {
                    return $query->where('program_id', $request->program_id)
                                 ->where('course_id', $request->course_id);
                })
            ],
            'year_of_study' => ['required', 'integer', 'min:1', 'max:10'],
            'semester_offered' => ['required', 'in:Semester 1,Semester 2,Both'],
            'credit_units' => ['required', 'integer', 'min:1', 'max:20'],
            'course_type' => ['required', 'in:Core,Elective,Audit'],
        ], [
            'course_id.unique' => 'This course is already assigned to the selected program.'
        ]);

        $program = Program::findOrFail($validated['program_id']);
        $program->courses()->attach($validated['course_id'], [
            'year_of_study' => $validated['year_of_study'],
            'semester_offered' => $validated['semester_offered'],
            'credit_units' => $validated['credit_units'],
            'course_type' => $validated['course_type'],
        ]);

        return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
            ->with('success', 'Course assigned to program successfully.');
    }

    public function edit(Program $program, Course $course)
    {
        // Pivot data is accessed via the relationship
        $course = $program->courses()->where('courses.id', $course->id)->firstOrFail();

        return view('admin.program-courses.edit', compact('program', 'course'));
    }

    public function update(Request $request, Program $program, Course $course)
    {
        $validated = $request->validate([
            'year_of_study' => ['required', 'integer', 'min:1', 'max:10'],
            'semester_offered' => ['required', 'in:Semester 1,Semester 2,Both'],
            'credit_units' => ['required', 'integer', 'min:1', 'max:20'],
            'course_type' => ['required', 'in:Core,Elective,Audit'],
        ]);

        $program->courses()->updateExistingPivot($course->id, $validated);

        return redirect()->route('admin.program-courses.index', ['program_id' => $program->id])
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Program $program, Course $course)
    {
        $program->courses()->detach($course->id);
        return redirect()->back()->with('success', 'Course removed from program.');
    }
}
