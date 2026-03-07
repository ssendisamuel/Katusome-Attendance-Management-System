# Phase 1: Core Report Improvements - Implementation Plan

## Critical Issues Found in Current Implementation

### 1. Daily Attendance Report

**Issues:**

- Expected calculation is wrong: counts schedules instead of (students × schedules)
- Percentage calculation doesn't account for implicit absences
- Missing hierarchical filters (Campus, Faculty, Department, Program, Year, Group)
- Summary stats don't match the filtered data

**Fixes Needed:**

- Calculate expected as: Students in filtered groups × Schedules held
- Include implicit absences in calculations
- Add hierarchical cascading filters
- Fix summary to reflect filtered data

### 2. Real-Time Schedule Attendance (NEW)

**Status:** Doesn't exist yet
**Need to Create:**

- New route: `/admin/reports/schedule/{id}`
- Show live attendance for a specific class session
- Expected students from group enrollment
- Real-time metrics (checked in, not checked in, late count)
- Auto-refresh capability
- Export to PDF with student list and photos

### 3. Monthly Attendance Report

**Issues:**

- Expected calculation uses group schedule count but doesn't handle:
  - Students with retakes/extras in different groups
  - Implicit absences properly
- Missing hierarchical filters
- Doesn't show students below 70% threshold prominently

**Fixes Needed:**

- Calculate expected per student based on their actual enrolled courses
- Handle retakes and extra courses
- Add hierarchical filters
- Highlight at-risk students (< 70%)

### 4. Course Attendance Report

**Issues:**

- Rate calculation at top level is inconsistent
- Student stats don't properly calculate expected for students with partial attendance
- Missing 70% threshold view
- No clear indication of who meets/doesn't meet threshold

**Fixes Needed:**

- Fix expected calculation per student
- Add prominent 70% threshold section
- Show students above/below threshold separately
- Add filters for Campus, Faculty, Department, Program, Year

## Implementation Order

### Step 1: Add Hierarchical Filter Support

Create a trait or helper class for consistent filter implementation across all reports.

### Step 2: Fix Daily Attendance Report

- Add all hierarchical filters
- Fix expected calculation
- Fix percentage calculation with implicit absences
- Update view with better UI

### Step 3: Create Real-Time Schedule Attendance

- New controller method
- New route
- New view with auto-refresh
- Export to PDF

### Step 4: Fix Monthly Attendance Report

- Add hierarchical filters
- Fix expected calculation per student
- Highlight at-risk students
- Improve UI

### Step 5: Fix Course Attendance Report

- Add hierarchical filters
- Fix calculations
- Add 70% threshold view
- Separate students above/below threshold

## Code Structure

### Filters Trait

```php
trait HasAttendanceFilters
{
    protected function applyHierarchicalFilters($query, Request $request, $studentRelation = 'student')
    {
        // Campus filter
        if ($campusId = $request->input('campus_id')) {
            $query->whereHas("$studentRelation.enrollment", function($q) use ($campusId) {
                $q->where('campus_id', $campusId);
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
                $q->where('year_of_study', $year);
            });
        }

        // Group filter
        if ($groupId = $request->input('group_id')) {
            $query->whereHas("$studentRelation.enrollment", function($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        return $query;
    }

    protected function getFilterData()
    {
        return [
            'campuses' => \App\Models\Campus::where('is_active', true)->orderBy('name')->get(),
            'faculties' => \App\Models\Faculty::where('is_active', true)->orderBy('name')->get(),
            'departments' => \App\Models\Department::where('is_active', true)->orderBy('name')->get(),
            'programs' => \App\Models\Program::orderBy('name')->get(),
            'years' => [1, 2, 3, 4, 5],
            'groups' => \App\Models\Group::orderBy('name')->get(),
        ];
    }
}
```

### Accurate Expected Calculation

```php
protected function calculateExpectedAttendance($studentId, $groupId, $semesterId, $startDate, $endDate)
{
    // Get schedules for student's primary group
    $groupSchedules = Schedule::where('group_id', $groupId)
        ->where('academic_semester_id', $semesterId)
        ->whereBetween('start_at', [$startDate, $endDate])
        ->where('is_cancelled', false)
        ->count();

    // Get retake/extra course schedules
    $retakeSchedules = Schedule::whereHas('course.studentRegistrations', function($q) use ($studentId, $semesterId) {
            $q->where('student_id', $studentId)
              ->where('academic_semester_id', $semesterId)
              ->whereIn('registration_type', ['retake', 'extra']);
        })
        ->whereBetween('start_at', [$startDate, $endDate])
        ->where('is_cancelled', false)
        ->count();

    return $groupSchedules + $retakeSchedules;
}
```

### Implicit Absence Calculation

```php
protected function calculateImplicitAbsences($studentId, $expected, $recorded)
{
    // Implicit absences = Expected - Total Recorded
    // Where Total Recorded = Present + Late + Absent + Excused
    return max(0, $expected - $recorded);
}
```

## Testing Checklist

- [ ] Daily report shows correct expected count
- [ ] Daily report percentage includes implicit absences
- [ ] All hierarchical filters work correctly
- [ ] Filters cascade properly (Faculty → Department → Program)
- [ ] Real-time schedule report auto-refreshes
- [ ] Real-time schedule shows correct expected students
- [ ] Monthly report handles retakes/extras correctly
- [ ] Monthly report highlights at-risk students
- [ ] Course report shows 70% threshold clearly
- [ ] Course report separates students above/below threshold
- [ ] All exports include correct data
- [ ] Performance is acceptable with large datasets

## Next Steps After Phase 1

Phase 2 will include:

- Students at Risk report (comprehensive risk assessment)
- Year of Study report (comparison across years)
- Activity Log report (student timeline)
- Improved Devices/Location audit report

Phase 3 will include:

- PDF exports with charts
- Excel exports with formatting
- Caching layer
- Query optimization
- Real-time dashboards
