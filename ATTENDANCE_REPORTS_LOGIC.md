# Attendance Reports System - Logic & Documentation

## Overview

This document explains the logic, calculations, and design principles for the attendance reporting system at MUBS.

## Core Data Model

### Attendance Status Types

- **present**: Student checked in on time
- **late**: Student checked in after the late threshold
- **absent**: Student did not check in (explicit or calculated)
- **excused**: Student absence was excused by lecturer/admin

### Key Relationships

```
Campus → Faculty → Department → Program → Group → Student
                                        ↓
                                    Courses (via course_program pivot)
                                        ↓
                                    Schedules (class sessions)
                                        ↓
                                    Attendance Records
```

### Enrollment Logic

- Students enroll per semester with: `year_of_study`, `program_id`, `group_id`, `campus_id`
- Courses are offered per program with specific `year_of_study` and `semester_offered`
- Students can register for retakes/extras outside their primary group

## Attendance Calculation Logic

### 1. Expected Attendance

**Formula**: Number of schedules held for a course/group in the period

**Logic**:

```php
// For a specific course and group
$expectedClasses = Schedule::where('course_id', $courseId)
    ->where('group_id', $groupId)
    ->where('academic_semester_id', $semesterId)
    ->whereBetween('start_at', [$startDate, $endDate])
    ->count();
```

**Important**: Only count schedules that have actually occurred (start_at <= now) or are marked as held.

### 2. Actual Attendance

**Formula**: Count of attendance records with status 'present' or 'late'

**Logic**:

```php
$actualAttendance = Attendance::where('student_id', $studentId)
    ->where('schedule_id', 'IN', $scheduleIds)
    ->whereIn('status', ['present', 'late'])
    ->count();
```

### 3. Attendance Percentage

**Formula**: `(Actual Attendance / Expected Classes) × 100`

**Edge Cases**:

- If expected = 0, percentage = 0% (no classes held yet)
- If actual > expected, cap at 100% (data integrity issue)
- Round to 2 decimal places

### 4. Implicit Absences

**Logic**: If a schedule has occurred but no attendance record exists for a student, they are implicitly absent.

**Calculation**:

```php
$implicitAbsent = $expectedClasses - $totalRecorded;
$totalAbsent = $explicitAbsent + $implicitAbsent;
```

Where:

- `$explicitAbsent` = Attendance records with status 'absent'
- `$totalRecorded` = All attendance records (present + late + absent + excused)

### 5. Time Spent in Class

**Formula**: Sum of (clock_out_time - marked_at) for all present/late records

**Logic**:

```php
$timeSpent = Attendance::where('student_id', $studentId)
    ->whereIn('status', ['present', 'late'])
    ->whereNotNull('clock_out_time')
    ->get()
    ->sum(function($record) {
        return $record->clock_out_time->diffInMinutes($record->marked_at);
    });
```

**Note**: Only count records with clock_out_time. If no clock-out, assume full session duration.

## Report Types & Logic

### 1. Daily Attendance Report (`/admin/reports/daily`)

**Purpose**: Show all attendance records for a specific date

**Filters**:

- Date (required, default: today)
- Campus, Faculty, Department, Program, Year of Study, Course, Group
- Status (present, late, absent, excused)

**Logic**:

```php
$attendances = Attendance::with(['student', 'schedule.course', 'schedule.group'])
    ->whereHas('schedule', function($q) use ($date) {
        $q->whereDate('start_at', $date);
    })
    ->when($campusId, function($q) use ($campusId) {
        $q->whereHas('student.enrollment', function($sq) use ($campusId) {
            $sq->where('campus_id', $campusId);
        });
    })
    // ... additional filters
    ->get();
```

**Metrics**:

- Total students expected (unique students in filtered groups)
- Total present, late, absent, excused
- Attendance rate: (present + late) / total expected × 100

**Export**: Excel with columns: Date, Student Name, Reg No, Course, Group, Status, Time, Location

---

### 2. Real-Time Schedule Attendance (`/admin/reports/schedule/{id}`)

**Purpose**: Show live attendance for a specific class session

**Filters**: None (schedule-specific)

**Logic**:

```php
$schedule = Schedule::with(['course', 'group', 'lecturer'])->findOrFail($id);
$expectedStudents = Student::whereHas('enrollment', function($q) use ($schedule) {
    $q->where('group_id', $schedule->group_id)
      ->where('academic_semester_id', $schedule->academic_semester_id);
})->get();

$attendances = Attendance::where('schedule_id', $id)->get();
$present = $attendances->whereIn('status', ['present', 'late'])->count();
$absent = $expectedStudents->count() - $attendances->count(); // Implicit absences
```

**Metrics**:

- Expected students (from group enrollment)
- Checked in (present + late)
- Not yet checked in (expected - checked in)
- Attendance rate: checked in / expected × 100
- Average check-in time
- Late arrivals count

**Real-time**: Auto-refresh every 30 seconds

**Export**: PDF with student list, check-in times, photos (optional)

---

### 3. Monthly Attendance Report (`/admin/reports/monthly`)

**Purpose**: Aggregate attendance by student for a month

**Filters**:

- Month & Year (required, default: current month)
- Campus, Faculty, Department, Program, Year of Study, Group

**Logic**:

```php
$students = Student::with(['enrollment', 'attendances'])
    ->whereHas('enrollment', function($q) use ($filters) {
        // Apply filters
    })
    ->get()
    ->map(function($student) use ($startDate, $endDate) {
        $schedules = Schedule::where('group_id', $student->enrollment->group_id)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->pluck('id');

        $attendances = $student->attendances()
            ->whereIn('schedule_id', $schedules)
            ->get();

        return [
            'student' => $student,
            'expected' => $schedules->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $schedules->count() - $attendances->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'percentage' => ($attendances->whereIn('status', ['present', 'late'])->count() / max($schedules->count(), 1)) * 100
        ];
    });
```

**Metrics**:

- Per student: Expected, Present, Late, Absent, Excused, Percentage
- Group averages
- Students below 70% threshold (at risk)

**Export**: Excel with student-level breakdown

---

### 4. Individual Student Report (`/admin/reports/individual`)

**Purpose**: Detailed attendance history for one student

**Filters**:

- Student (required, searchable)
- Date range (optional)
- Academic semester (optional)
- Course (optional)

**Logic**:

```php
$student = Student::with(['enrollment', 'attendances.schedule.course'])->findOrFail($id);

$attendances = $student->attendances()
    ->with(['schedule.course', 'schedule.group'])
    ->when($dateRange, function($q) use ($dateRange) {
        $q->whereHas('schedule', function($sq) use ($dateRange) {
            $sq->whereBetween('start_at', $dateRange);
        });
    })
    ->orderBy('marked_at', 'desc')
    ->get();

// Group by course
$byCourse = $attendances->groupBy('schedule.course_id')->map(function($records, $courseId) {
    $course = $records->first()->schedule->course;
    $expected = Schedule::where('course_id', $courseId)
        ->where('group_id', $records->first()->schedule->group_id)
        ->count();

    return [
        'course' => $course,
        'expected' => $expected,
        'attended' => $records->whereIn('status', ['present', 'late'])->count(),
        'percentage' => ($records->whereIn('status', ['present', 'late'])->count() / max($expected, 1)) * 100,
        'records' => $records
    ];
});
```

**Metrics**:

- Overall attendance percentage
- Per-course breakdown
- Attendance trend (graph)
- Time spent in class
- Late arrivals count
- Consecutive absences (risk indicator)

**Export**: PDF with detailed breakdown and graphs

---

### 5. Absenteeism Report (`/admin/reports/absenteeism`)

**Purpose**: Identify students at risk due to poor attendance

**Filters**:

- Date range (default: current month)
- Campus, Faculty, Department, Program, Year of Study, Group
- Threshold (default: < 70% attendance OR >= 3 absences)

**Logic**:

```php
$atRiskStudents = Student::with(['enrollment'])
    ->whereHas('enrollment', function($q) use ($filters) {
        // Apply filters
    })
    ->get()
    ->map(function($student) use ($startDate, $endDate) {
        $schedules = Schedule::where('group_id', $student->enrollment->group_id)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->pluck('id');

        $attendances = $student->attendances()
            ->whereIn('schedule_id', $schedules)
            ->get();

        $expected = $schedules->count();
        $attended = $attendances->whereIn('status', ['present', 'late'])->count();
        $absent = $expected - $attendances->count();
        $percentage = ($attended / max($expected, 1)) * 100;

        return [
            'student' => $student,
            'expected' => $expected,
            'attended' => $attended,
            'absent' => $absent,
            'percentage' => $percentage,
            'at_risk' => $percentage < 70 || $absent >= 3
        ];
    })
    ->filter(function($data) {
        return $data['at_risk'];
    })
    ->sortBy('percentage');
```

**Metrics**:

- Total at-risk students
- Average attendance percentage
- Most missed courses
- Consecutive absences

**Export**: Excel with student contact info for follow-up

---

### 6. Course Attendance Report (`/admin/reports/course`)

**Purpose**: Attendance statistics per course

**Filters**:

- Academic semester (required)
- Campus, Faculty, Department, Program, Year of Study, Course

**Logic**:

```php
$courses = Course::with(['schedules' => function($q) use ($semesterId) {
        $q->where('academic_semester_id', $semesterId);
    }])
    ->when($filters, function($q) use ($filters) {
        // Apply filters
    })
    ->get()
    ->map(function($course) {
        $schedules = $course->schedules;
        $attendances = Attendance::whereIn('schedule_id', $schedules->pluck('id'))->get();

        // Group by student to get unique students
        $studentStats = $attendances->groupBy('student_id')->map(function($records) use ($schedules) {
            $attended = $records->whereIn('status', ['present', 'late'])->count();
            $expected = $schedules->count();
            $percentage = ($attended / max($expected, 1)) * 100;

            return [
                'student_id' => $records->first()->student_id,
                'attended' => $attended,
                'expected' => $expected,
                'percentage' => $percentage,
                'meets_threshold' => $percentage >= 70
            ];
        });

        return [
            'course' => $course,
            'total_sessions' => $schedules->count(),
            'total_students' => $studentStats->count(),
            'students_above_70' => $studentStats->where('meets_threshold', true)->count(),
            'students_below_70' => $studentStats->where('meets_threshold', false)->count(),
            'average_attendance' => $studentStats->avg('percentage'),
            'student_details' => $studentStats
        ];
    });
```

**Metrics**:

- Total sessions held
- Total enrolled students
- Students meeting 70% threshold
- Students below 70% threshold
- Average attendance percentage
- Attendance trend over time

**Export**: Excel with course summary and student-level details

---

### 7. Group Attendance Report (`/admin/reports/group`)

**Purpose**: Attendance statistics per group

**Filters**:

- Academic semester (required)
- Campus, Faculty, Department, Program, Year of Study, Group

**Logic**: Similar to course report but grouped by group_id

**Metrics**:

- Per group: Total students, average attendance, courses covered
- Comparison across groups in same program
- Best/worst performing groups

**Export**: Excel with group comparisons

---

### 8. Program Attendance Report (`/admin/reports/program`)

**Purpose**: High-level attendance statistics per program

**Filters**:

- Academic semester (required)
- Campus, Faculty, Department, Program, Year of Study

**Logic**: Aggregate group reports by program

**Metrics**:

- Per program: Total students, average attendance, groups count
- Year-of-study breakdown
- Program-level trends

**Export**: PDF with executive summary

---

### 9. Year of Study Report (NEW)

**Purpose**: Compare attendance across years of study

**Filters**:

- Academic semester (required)
- Campus, Faculty, Department, Program

**Logic**:

```php
$yearStats = StudentEnrollment::with(['student.attendances'])
    ->where('academic_semester_id', $semesterId)
    ->when($filters, function($q) use ($filters) {
        // Apply filters
    })
    ->get()
    ->groupBy('year_of_study')
    ->map(function($enrollments, $year) {
        // Calculate stats per year
    });
```

**Metrics**:

- Per year: Total students, average attendance, at-risk count
- Year-over-year comparison
- Retention indicators

---

### 10. Students at Risk Report (NEW)

**Purpose**: Comprehensive risk assessment

**Filters**:

- Academic semester (required)
- Campus, Faculty, Department, Program, Year of Study
- Risk threshold (default: < 70%)
- Consecutive absences threshold (default: 3)

**Logic**:

```php
$atRiskStudents = Student::with(['enrollment', 'attendances'])
    ->whereHas('enrollment', function($q) use ($filters) {
        // Apply filters
    })
    ->get()
    ->map(function($student) {
        // Calculate risk factors
        $consecutiveAbsences = $this->calculateConsecutiveAbsences($student);
        $overallPercentage = $this->calculateOverallPercentage($student);
        $coursesBelow70 = $this->countCoursesBelow70($student);

        $riskScore = 0;
        if ($overallPercentage < 70) $riskScore += 3;
        if ($consecutiveAbsences >= 3) $riskScore += 2;
        if ($coursesBelow70 >= 2) $riskScore += 2;

        return [
            'student' => $student,
            'risk_score' => $riskScore,
            'risk_level' => $riskScore >= 5 ? 'High' : ($riskScore >= 3 ? 'Medium' : 'Low'),
            'factors' => [
                'overall_percentage' => $overallPercentage,
                'consecutive_absences' => $consecutiveAbsences,
                'courses_below_70' => $coursesBelow70
            ]
        ];
    })
    ->filter(function($data) {
        return $data['risk_score'] >= 3; // Medium or High risk
    })
    ->sortByDesc('risk_score');
```

**Metrics**:

- Risk level (High/Medium/Low)
- Risk factors breakdown
- Recommended interventions
- Contact information

**Export**: Excel with action plan template

---

### 11. Devices/Location Report (`/admin/reports/devices`)

**Purpose**: Track attendance verification data (location, selfies)

**Current Issues**: Unclear purpose, needs improvement

**Improved Purpose**: Audit trail for attendance integrity

**Filters**:

- Date range
- Campus, Faculty, Department
- Location accuracy threshold
- Missing data flags

**Logic**:

```php
$records = Attendance::with(['student', 'schedule'])
    ->whereBetween('marked_at', [$startDate, $endDate])
    ->get()
    ->map(function($record) {
        $distance = $this->calculateDistance($record->lat, $record->lng, $campusLat, $campusLng);

        return [
            'record' => $record,
            'distance_from_campus' => $distance,
            'has_selfie' => !empty($record->selfie_path),
            'has_location' => !empty($record->lat) && !empty($record->lng),
            'accuracy_flag' => $distance > 200 ? 'Outside Range' : 'Within Range',
            'device_info' => $record->user_agent ?? 'Unknown'
        ];
    });
```

**Metrics**:

- Records with missing location data
- Records with missing selfies
- Records outside geofence
- Device types used
- Potential fraud indicators

**Export**: Excel with audit flags

---

### 12. Activity Log Report (NEW - Student)

**Purpose**: Show student's attendance activity timeline

**Filters**:

- Date range
- Activity type (check-in, clock-out, missed)

**Logic**:

```php
$activities = collect();

// Check-ins
$checkIns = Attendance::where('student_id', $studentId)
    ->whereBetween('marked_at', [$startDate, $endDate])
    ->get()
    ->map(function($record) {
        return [
            'type' => 'check-in',
            'timestamp' => $record->marked_at,
            'status' => $record->status,
            'course' => $record->schedule->course->name,
            'location' => $record->lat && $record->lng ? 'On Campus' : 'Online'
        ];
    });

// Clock-outs
$clockOuts = Attendance::where('student_id', $studentId)
    ->whereNotNull('clock_out_time')
    ->whereBetween('clock_out_time', [$startDate, $endDate])
    ->get()
    ->map(function($record) {
        return [
            'type' => 'clock-out',
            'timestamp' => $record->clock_out_time,
            'duration' => $record->clock_out_time->diffInMinutes($record->marked_at),
            'course' => $record->schedule->course->name
        ];
    });

// Missed classes
$missed = Schedule::whereHas('group.students', function($q) use ($studentId) {
        $q->where('students.id', $studentId);
    })
    ->whereBetween('start_at', [$startDate, $endDate])
    ->whereDoesntHave('attendances', function($q) use ($studentId) {
        $q->where('student_id', $studentId);
    })
    ->get()
    ->map(function($schedule) {
        return [
            'type' => 'missed',
            'timestamp' => $schedule->start_at,
            'course' => $schedule->course->name,
            'reason' => 'No check-in recorded'
        ];
    });

$activities = $checkIns->concat($clockOuts)->concat($missed)->sortByDesc('timestamp');
```

**Metrics**:

- Total activities
- Check-in patterns (time of day)
- Average session duration
- Missed classes count

**Export**: PDF timeline view

---

## Filter Implementation

### Hierarchical Filters

All reports should support cascading filters:

1. **Campus** → Filters Faculties
2. **Faculty** → Filters Departments
3. **Department** → Filters Programs
4. **Program** → Filters Years of Study & Groups
5. **Year of Study** → Filters Courses & Groups
6. **Course** → Filters Groups (if course-specific)
7. **Group** → Filters Students

### Filter Logic

```php
$query = Student::query();

if ($campusId) {
    $query->whereHas('enrollment', function($q) use ($campusId) {
        $q->where('campus_id', $campusId);
    });
}

if ($facultyId) {
    $query->whereHas('enrollment.program.department.faculty', function($q) use ($facultyId) {
        $q->where('id', $facultyId);
    });
}

// ... continue for other filters
```

---

## Export Formats

### Excel Exports

- Use `Maatwebsite\Excel` package
- Include:
  - Report title and parameters
  - Generation timestamp
  - Filters applied
  - Summary statistics
  - Detailed data table
  - Footer with totals

### PDF Exports

- Use `barryvdh/laravel-dompdf` package
- Include:
  - University logo and header
  - Report title and date
  - Executive summary
  - Charts/graphs (where applicable)
  - Detailed tables
  - Footer with page numbers

---

## Performance Optimization

### Caching Strategy

- Cache report results for 5 minutes
- Cache key includes all filter parameters
- Invalidate cache on new attendance records

### Query Optimization

- Use eager loading for relationships
- Index foreign keys and date columns
- Use database views for complex aggregations
- Paginate large result sets

### Background Processing

- Queue large report generation
- Send email notification when ready
- Store generated files for 24 hours

---

## Accuracy Validation

### Data Integrity Checks

1. **Orphaned Records**: Attendance without valid schedule/student
2. **Duplicate Records**: Multiple attendance for same schedule/student
3. **Future Records**: Attendance marked for future schedules
4. **Invalid Status**: Status not in allowed enum values
5. **Missing Geolocation**: Physical classes without location data

### Calculation Verification

1. **Sum Check**: Total (present + late + absent + excused) = Expected
2. **Percentage Range**: 0% ≤ percentage ≤ 100%
3. **Time Logic**: Clock-out > Clock-in
4. **Date Logic**: Attendance date matches schedule date

---

## Best Practices

### Report Design

1. **Clear Metrics**: Define what each number means
2. **Visual Hierarchy**: Most important info first
3. **Actionable Insights**: Highlight what needs attention
4. **Consistent Formatting**: Same layout across reports
5. **Responsive Design**: Works on mobile devices

### User Experience

1. **Fast Loading**: Show summary first, details on demand
2. **Progressive Disclosure**: Drill-down from high-level to details
3. **Export Options**: Multiple formats for different use cases
4. **Saved Filters**: Remember user preferences
5. **Scheduled Reports**: Email reports automatically

### Security

1. **Role-Based Access**: Lecturers see only their courses
2. **Data Privacy**: Mask sensitive student info where appropriate
3. **Audit Trail**: Log who accessed which reports
4. **Rate Limiting**: Prevent report abuse

---

## Implementation Checklist

- [ ] Update ReportsController with accurate calculations
- [ ] Add new report methods (Year of Study, At Risk, Activity Log)
- [ ] Implement hierarchical filters
- [ ] Add real-time schedule attendance
- [ ] Create Excel export classes
- [ ] Create PDF export classes
- [ ] Add caching layer
- [ ] Optimize database queries
- [ ] Create report views with charts
- [ ] Add data validation checks
- [ ] Write unit tests for calculations
- [ ] Update documentation
- [ ] Train users on new reports

---

## Testing Strategy

### Unit Tests

- Test each calculation method independently
- Test edge cases (zero attendance, 100% attendance, etc.)
- Test filter combinations

### Integration Tests

- Test full report generation
- Test export functionality
- Test caching behavior

### Performance Tests

- Test with large datasets (1000+ students)
- Measure query execution time
- Test concurrent report generation

---

## Future Enhancements

1. **Predictive Analytics**: ML model to predict at-risk students
2. **Real-time Dashboards**: Live attendance monitoring
3. **Mobile App**: Push notifications for low attendance
4. **Integration**: Connect with student information system
5. **Automated Interventions**: Auto-email students below threshold
6. **Attendance Trends**: Historical analysis and forecasting
7. **Comparative Analytics**: Benchmark against other institutions

---

_Last Updated: March 7, 2026_
_Version: 2.0_
