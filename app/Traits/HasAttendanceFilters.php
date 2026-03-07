<?php

namespace App\Traits;

use App\Models\Campus;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Program;
use App\Models\Group;
use App\Models\Course;
use Illuminate\Http\Request;

trait HasAttendanceFilters
{
    /**
     * Apply hierarchical filters to a query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param string $studentRelation The relationship path to student (e.g., 'student', 'schedule.group.students')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyHierarchicalFilters($query, Request $request, $studentRelation = 'student')
    {
        // Campus filter
        if ($campusId = $request->input('campus_id')) {
            $query->whereHas("$studentRelation.enrollment", function($q) use ($campusId) {
                $q->where('campus_id', $campusId)
                  ->where('is_active', true);
            });
        }

        // Faculty filter
        if ($facultyId = $request->input('faculty_id')) {
            $query->whereHas("$studentRelation.enrollment.program.department.faculty", function($q) use ($facultyId) {
                $q->where('id', $facultyId);
            });
        }

        // Department filter
        if ($departmentId = $request->input('department_id')) {
            $query->whereHas("$studentRelation.enrollment.program.department", function($q) use ($departmentId) {
                $q->where('id', $departmentId);
            });
        }

        // Program filter
        if ($programId = $request->input('program_id')) {
            $query->whereHas("$studentRelation.enrollment.program", function($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        // Year of Study filter
        if ($year = $request->input('year_of_study')) {
            $query->whereHas("$studentRelation.enrollment", function($q) use ($year) {
                $q->where('year_of_study', $year)
                  ->where('is_active', true);
            });
        }

        // Group filter
        if ($groupId = $request->input('group_id')) {
            $query->whereHas("$studentRelation.enrollment", function($q) use ($groupId) {
                $q->where('group_id', $groupId)
                  ->where('is_active', true);
            });
        }

        return $query;
    }

    /**
     * Get filter data for dropdowns
     *
     * @return array
     */
    protected function getFilterData()
    {
        return [
            'campuses' => Campus::where('is_active', true)->orderBy('name')->get(),
            'faculties' => Faculty::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->with('faculty')->orderBy('name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'years' => [1, 2, 3, 4, 5],
            'groups' => Group::orderBy('name')->get(),
            'courses' => Course::orderBy('name')->get(),
        ];
    }

    /**
     * Calculate expected attendance for a student in a date range
     *
     * @param int $studentId
     * @param int $groupId
     * @param int $semesterId
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return int
     */
    protected function calculateExpectedAttendance($studentId, $groupId, $semesterId, $startDate, $endDate)
    {
        // Get schedules for student's primary group
        $groupSchedules = \App\Models\Schedule::where('group_id', $groupId)
            ->where('academic_semester_id', $semesterId)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->where('is_cancelled', false)
            ->count();

        // Get retake/extra course schedules if StudentCourseRegistration exists
        $retakeSchedules = 0;
        if (class_exists('\App\Models\StudentCourseRegistration')) {
            $retakeSchedules = \App\Models\Schedule::whereHas('course.studentRegistrations', function($q) use ($studentId, $semesterId) {
                    $q->where('student_id', $studentId)
                      ->where('academic_semester_id', $semesterId)
                      ->whereIn('registration_type', ['retake', 'extra']);
                })
                ->whereBetween('start_at', [$startDate, $endDate])
                ->where('is_cancelled', false)
                ->count();
        }

        return $groupSchedules + $retakeSchedules;
    }

    /**
     * Calculate implicit absences
     *
     * @param int $expected Total expected attendance records
     * @param int $recorded Total recorded attendance (present + late + absent + excused)
     * @return int
     */
    protected function calculateImplicitAbsences($expected, $recorded)
    {
        return max(0, $expected - $recorded);
    }

    /**
     * Calculate attendance percentage
     *
     * @param int $attended Count of present + late records
     * @param int $expected Total expected attendance
     * @return float
     */
    protected function calculateAttendancePercentage($attended, $expected)
    {
        if ($expected <= 0) {
            return 0.0;
        }

        return round(($attended / $expected) * 100, 2);
    }
}
