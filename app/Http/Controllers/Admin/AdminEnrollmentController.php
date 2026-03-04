<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSemester;
use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;

class AdminEnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentEnrollment::with(['student.user', 'academicSemester', 'program.faculty.campuses', 'group', 'campus']);

        // Filters
        if ($request->filled('academic_semester_id')) {
            $query->where('academic_semester_id', $request->academic_semester_id);
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('faculty_id')) {
            $query->whereHas('program', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }
        if ($request->filled('campus_id')) {
            $query->whereHas('program.faculty.campuses', function ($q) use ($request) {
                $q->where('campuses.id', $request->campus_id);
            });
        }
        if ($request->filled('year_of_study')) {
            $query->where('year_of_study', $request->year_of_study);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_no', 'like', "%{$search}%")
                  ->orWhere('reg_no', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $enrollments = $query->latest('enrolled_at')->paginate(25)->withQueryString();

        // Data for filters & modal
        $semesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
        $programs = Program::with('faculty.campuses')->orderBy('code')->get();
        $groups = Group::orderBy('name')->get();

        return view('admin.enrollments.index', compact(
            'enrollments', 'semesters', 'campuses', 'faculties', 'programs', 'groups'
        ));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id'           => 'required|exists:students,id',
            'academic_semester_id' => 'required|exists:academic_semesters,id',
            'program_id'           => 'required|exists:programs,id',
            'group_id'             => 'required|exists:groups,id',
            'year_of_study'        => 'required|integer|min:1|max:5',
            'campus_id'            => 'required|exists:campuses,id',
        ]);

        $enrollment = StudentEnrollment::updateOrCreate(
            [
                'student_id'           => $validated['student_id'],
                'academic_semester_id' => $validated['academic_semester_id'],
            ],
            [
                'program_id'    => $validated['program_id'],
                'group_id'      => $validated['group_id'],
                'year_of_study' => $validated['year_of_study'],
                'campus_id'     => $validated['campus_id'],
                'enrolled_at'   => now(),
            ]
        );

        // Update student main record to keep it in sync
        $enrollment->student->update([
            'program_id'    => $validated['program_id'],
            'group_id'      => $validated['group_id'],
            'year_of_study' => $validated['year_of_study'],
        ]);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment added successfully.');
    }

    public function searchStudents(Request $request)
    {
        $term = $request->input('term');
        if (!$term) {
            return response()->json([]);
        }

        $students = \App\Models\Student::with('user')
            ->where(function($q) use ($term) {
                $q->where('student_no', 'like', "%{$term}%")
                  ->orWhere('reg_no', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%") // Search direct name if user relation is missing/lagging
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                  });
            })
            ->take(50)
            ->get();

        $results = $students->map(function ($s) {
            $name = $s->user ? $s->user->name : $s->name;
            return [
                'id' => $s->id,
                'name' => "{$name} ({$s->student_no})"
            ];
        });

        return response()->json($results);
    }

    public function update(Request $request, $id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);

        $validated = $request->validate([
            'academic_semester_id' => 'required|exists:academic_semesters,id',
            'program_id'           => 'required|exists:programs,id',
            'group_id'             => 'required|exists:groups,id',
            'year_of_study'        => 'required|integer|min:1|max:5',
            'campus_id'            => 'required|exists:campuses,id',
        ]);

        $enrollment->update($validated);

        // Update student main record to keep it in sync
        $enrollment->student->update([
            'program_id' => $validated['program_id'],
            'group_id' => $validated['group_id'],
            'year_of_study' => $validated['year_of_study'],
        ]);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment updated successfully.');
    }

    public function destroy($id)
    {
        $enrollment = StudentEnrollment::findOrFail($id);
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment deleted successfully.');
    }
}
