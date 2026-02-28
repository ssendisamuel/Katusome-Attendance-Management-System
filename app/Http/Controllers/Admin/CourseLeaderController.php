<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLeader;
use App\Models\Student;
use App\Models\Program;
use App\Models\Group;
use App\Models\AcademicSemester;
use App\Models\CourseLeaderLog;
use Illuminate\Http\Request;

class CourseLeaderController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseLeader::with(['student.user', 'program', 'group']);

        if ($request->filled('filter_semester_id')) {
            // Filter leaders who have schedules in the selected semester
            $semId = $request->integer('filter_semester_id');
            $query->whereHas('student.enrollments', function($q) use ($semId) {
                $q->where('academic_semester_id', $semId);
            });
        }
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }
        if ($request->filled('year_of_study')) {
            $query->where('year_of_study', $request->integer('year_of_study'));
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function($q) use ($term) {
                $q->whereHas('student.user', function($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                       ->orWhere('email', 'like', $term);
                })->orWhereHas('student', function($q2) use ($term) {
                    $q2->where('student_no', 'like', $term)
                       ->orWhere('reg_no', 'like', $term);
                });
            });
        }

        $leaders = $query->paginate(15)->appends($request->query());

        $programs = Program::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $semesters = AcademicSemester::orderByDesc('start_date')->get();

        // Fetch logs
        $logsQuery = CourseLeaderLog::with(['student.user', 'schedule.course.programs', 'schedule.group', 'schedule.academicSemester'])
            ->latest();

        if ($request->filled('log_semester_id')) {
            $logsQuery->whereHas('schedule', function($q) use ($request) {
                $q->where('academic_semester_id', $request->integer('log_semester_id'));
            });
        }
        if ($request->filled('log_program_id')) {
            $logsQuery->whereHas('schedule', function($q) use ($request) {
                $q->whereHas('course.programs', function($q2) use ($request) {
                    $q2->where('programs.id', $request->integer('log_program_id'));
                });
            });
        }
        if ($request->filled('log_group_id')) {
            $logsQuery->whereHas('schedule', function($q) use ($request) {
                $q->where('group_id', $request->integer('log_group_id'));
            });
        }
        if ($request->filled('log_action')) {
            $logsQuery->where('action', $request->string('log_action'));
        }
        if ($request->filled('log_date_from')) {
            $logsQuery->whereDate('created_at', '>=', $request->log_date_from);
        }
        if ($request->filled('log_date_to')) {
            $logsQuery->whereDate('created_at', '<=', $request->log_date_to);
        }

        $logs = $logsQuery->paginate(15, ['*'], 'logs_page')->appends($request->query());

        return view('admin.course-leaders.index', compact('leaders', 'programs', 'groups', 'semesters', 'logs'));
    }

    public function store(Request $request)
    {
        if ($request->has('student_ids') && is_array($request->student_ids)) {
            $request->merge([
                'student_ids' => array_filter($request->student_ids)
            ]);
        }

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['exists:students,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_of_study' => ['required', 'integer', 'min:1', 'max:5'],
            'group_id' => ['required', 'exists:groups,id'],
        ]);

        $assigned = 0;
        $skipped = 0;

        foreach ($data['student_ids'] as $studentId) {
            $exists = CourseLeader::where([
                'student_id' => $studentId,
                'program_id' => $data['program_id'],
                'year_of_study' => $data['year_of_study'],
                'group_id' => $data['group_id']
            ])->exists();

            if ($exists) {
                $skipped++;
            } else {
                CourseLeader::create([
                    'student_id' => $studentId,
                    'program_id' => $data['program_id'],
                    'year_of_study' => $data['year_of_study'],
                    'group_id' => $data['group_id']
                ]);
                $assigned++;
            }
        }

        $msg = "Successfully assigned $assigned course leader(s).";
        if ($skipped > 0) {
            $msg .= " $skipped student(s) were already assigned to this cohort.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(CourseLeader $courseLeader)
    {
        $courseLeader->delete();
        return back()->with('success', 'Course Leader removed.');
    }

    public function searchStudents(Request $request)
    {
        $programId = $request->integer('program_id');
        $yearOfStudy = $request->integer('year_of_study');
        $groupId = $request->integer('group_id');
        $semesterId = $request->integer('academic_semester_id');
        $term = $request->string('term');

        if (!$programId || !$yearOfStudy || !$groupId) {
            return response()->json([]); // Require cohort details before searching
        }

        $query = Student::whereHas('enrollments', function($q) use ($programId, $yearOfStudy, $groupId, $semesterId) {
            $q->where('program_id', $programId)
              ->where('year_of_study', $yearOfStudy)
              ->where('group_id', $groupId);

            if ($semesterId) {
                $q->where('academic_semester_id', $semesterId);
            }
        });

        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('student_no', 'like', "%{$term}%")
                  ->orWhereHas('user', function($q2) use ($term) {
                      $q2->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        $students = $query->with('user')->take(100)->get();

        $results = $students->map(function($student) {
            return [
                'id' => $student->id,
                'name' => ($student->user->name ?? $student->name) . ' (' . $student->student_no . ')'
            ];
        });

        return response()->json($results);
    }
}
