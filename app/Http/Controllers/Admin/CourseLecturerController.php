<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSemester;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseLecturerController extends Controller
{
    public function index(Request $request)
    {
        $assignments = DB::table('course_lecturer')
            ->join('lecturers', 'course_lecturer.lecturer_id', '=', 'lecturers.id')
            ->join('users', 'lecturers.user_id', '=', 'users.id')
            ->join('courses', 'course_lecturer.course_id', '=', 'courses.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->leftJoin('faculties', 'departments.faculty_id', '=', 'faculties.id')
            ->leftJoin('campus_faculty', 'faculties.id', '=', 'campus_faculty.faculty_id')
            ->leftJoin('campuses', 'campus_faculty.campus_id', '=', 'campuses.id')
            ->select(
                'course_lecturer.id as assignment_id',
                'course_lecturer.academic_year',
                'course_lecturer.semester',
                'course_lecturer.program_code',
                'course_lecturer.study_group',
                'course_lecturer.year_of_study',
                'course_lecturer.hours_per_week',
                'users.name as lecturer_name',
                'lecturers.id as lecturer_id',
                'lecturers.title as lecturer_title',
                'lecturers.designation',
                'courses.id as course_id',
                'courses.code as course_code',
                'courses.name as course_name',
                'courses.credit_units',
                'departments.id as department_id',
                'departments.code as department_code',
                'departments.name as department_name',
                'faculties.id as faculty_id',
                'faculties.code as faculty_code',
                'faculties.name as faculty_name',
                'campuses.id as campus_id',
                'campuses.name as campus_name',
                'campuses.code as campus_code',
            )
            ->orderBy('users.name')
            ->orderBy('course_lecturer.program_code')
            ->get();

        // Active academic semester
        $activeSemester = AcademicSemester::where('is_active', true)->first();
        $academicSemesters = AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $programs = Program::with('faculty', 'department')->orderBy('code')->get();
        $courses = Course::with('programs.faculty', 'programs.department')->orderBy('code')->get();
        $lecturers = Lecturer::with(['user', 'department.faculty'])->get()->sortBy(fn($l) => optional($l->user)->name);
        $groups = Group::orderBy('name')->get();

        // Default academic year / semester from active
        $defaultYear = $activeSemester->year ?? date('Y') . '/' . (date('Y') + 1);
        $defaultSem = '2';
        if ($activeSemester) {
            $defaultSem = str_contains($activeSemester->semester, '1') ? '1' : '2';
        }

        // Mandatory hours constant
        $mandatoryHrsPerSem = 130;

        return view('admin.course_lecturers.index', compact(
            'assignments', 'activeSemester', 'academicSemesters',
            'campuses', 'faculties', 'departments', 'programs', 'courses', 'lecturers', 'groups',
            'defaultYear', 'defaultSem', 'mandatoryHrsPerSem'
        ));
    }

    /**
     * Bulk store: accepts arrays of lecturer_ids and course_ids.
     * Creates an assignment for every combination (cartesian product).
     */
    public function store(Request $request)
    {
        // Support both single (edit mode) and bulk (new assignment mode)
        $isBulk = $request->has('lecturer_ids');

        if ($isBulk) {
            $data = $request->validate([
                'lecturer_ids'   => ['required', 'array', 'min:1'],
                'lecturer_ids.*' => ['exists:lecturers,id'],
                'course_ids'     => ['required', 'array', 'min:1'],
                'course_ids.*'   => ['exists:courses,id'],
                'academic_year'  => ['required', 'string', 'max:20'],
                'semester'       => ['required', 'integer', 'in:1,2'],
                'program_code'   => ['nullable', 'string', 'max:20'],
                'study_group'    => ['nullable', 'string', 'max:10'],
                'year_of_study'  => ['nullable', 'integer', 'min:1', 'max:7'],
                'hours_per_week' => ['nullable', 'numeric', 'min:0', 'max:30'],
            ]);

            $created = 0;
            $skipped = 0;
            foreach ($data['lecturer_ids'] as $lecturerId) {
                foreach ($data['course_ids'] as $courseId) {
                    // Check for existing to avoid duplicates
                    $exists = DB::table('course_lecturer')
                        ->where('lecturer_id', $lecturerId)
                        ->where('course_id', $courseId)
                        ->where('academic_year', $data['academic_year'])
                        ->where('semester', $data['semester'])
                        ->where('program_code', $data['program_code'] ?? null)
                        ->where('study_group', $data['study_group'] ?? null)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    DB::table('course_lecturer')->insert([
                        'lecturer_id'    => $lecturerId,
                        'course_id'      => $courseId,
                        'academic_year'  => $data['academic_year'],
                        'semester'       => $data['semester'],
                        'program_code'   => $data['program_code'] ?? null,
                        'study_group'    => $data['study_group'] ?? null,
                        'year_of_study'  => $data['year_of_study'] ?? null,
                        'hours_per_week' => $data['hours_per_week'] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                    $created++;
                }
            }

            $msg = "{$created} teaching assignment(s) created.";
            if ($skipped) $msg .= " {$skipped} duplicate(s) skipped.";

            return redirect()->route('admin.course-lecturers.index')->with('success', $msg);
        }

        // Legacy single-assignment fallback (used by edit form)
        $data = $request->validate([
            'lecturer_id'    => ['required', 'exists:lecturers,id'],
            'course_id'      => ['required', 'exists:courses,id'],
            'academic_year'  => ['required', 'string', 'max:20'],
            'semester'       => ['required', 'integer', 'in:1,2'],
            'program_code'   => ['nullable', 'string', 'max:20'],
            'study_group'    => ['nullable', 'string', 'max:10'],
            'year_of_study'  => ['nullable', 'integer', 'min:1', 'max:7'],
            'hours_per_week' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ]);

        DB::table('course_lecturer')->insert([
            'lecturer_id'    => $data['lecturer_id'],
            'course_id'      => $data['course_id'],
            'academic_year'  => $data['academic_year'],
            'semester'       => $data['semester'],
            'program_code'   => $data['program_code'] ?? null,
            'study_group'    => $data['study_group'] ?? null,
            'year_of_study'  => $data['year_of_study'] ?? null,
            'hours_per_week' => $data['hours_per_week'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.course-lecturers.index')->with('success', 'Teaching assignment created.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'lecturer_id'    => ['required', 'exists:lecturers,id'],
            'course_id'      => ['required', 'exists:courses,id'],
            'academic_year'  => ['required', 'string', 'max:20'],
            'semester'       => ['required', 'integer', 'in:1,2'],
            'program_code'   => ['nullable', 'string', 'max:20'],
            'study_group'    => ['nullable', 'string', 'max:10'],
            'year_of_study'  => ['nullable', 'integer', 'min:1', 'max:7'],
            'hours_per_week' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ]);

        DB::table('course_lecturer')->where('id', $id)->update([
            'lecturer_id'    => $data['lecturer_id'],
            'course_id'      => $data['course_id'],
            'academic_year'  => $data['academic_year'],
            'semester'       => $data['semester'],
            'program_code'   => $data['program_code'] ?? null,
            'study_group'    => $data['study_group'] ?? null,
            'year_of_study'  => $data['year_of_study'] ?? null,
            'hours_per_week' => $data['hours_per_week'] ?? null,
            'updated_at'     => now(),
        ]);

        return redirect()->route('admin.course-lecturers.index')->with('success', 'Teaching assignment updated.');
    }

    public function destroy(Request $request, $id)
    {
        DB::table('course_lecturer')->where('id', $id)->delete();
        return redirect()->route('admin.course-lecturers.index')->with('success', 'Teaching assignment removed.');
    }
}
