<form id="attendanceFilters" method="GET" action="{{ route('admin.attendance.index') }}"
    class="row g-3 mb-3 align-items-end">
    <!-- Row 1 -->
    <div class="col-md-3">
        <label class="form-label">Academic Semester</label>
        <select name="academic_semester_id" class="form-select select2">
            <option value="">All Semesters</option>
            @isset($semesters)
                @foreach ($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ request('academic_semester_id') == $sem->id ? 'selected' : '' }}>
                        {{ $sem->year }} - {{ $sem->semester }}
                    </option>
                @endforeach
            @endisset
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select select2">
            <option value="">All Programs</option>
            @isset($programs)
                @foreach ($programs as $prog)
                    <option value="{{ $prog->id }}" {{ request('program_id') == $prog->id ? 'selected' : '' }}>
                        {{ $prog->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Year of Study</label>
        <select name="year_of_study" class="form-select">
            <option value="">All Years</option>
            @foreach (range(1, 5) as $year)
                <option value="{{ $year }}" {{ request('year_of_study') == $year ? 'selected' : '' }}>Year
                    {{ $year }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select select2">
            <option value="">All Courses</option>
            @isset($courses)
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                        {{ $course->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select select2">
            <option value="">All Groups</option>
            @isset($groups)
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                        {{ $group->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>

    <!-- Row 2 -->
    <div class="col-md-3">
        <label class="form-label">Lecturer</label>
        <select name="lecturer_id" class="form-select select2">
            <option value="">All Lecturers</option>
            @isset($lecturers)
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" {{ request('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                        {{ $lecturer->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ request('date') }}" class="form-control" />
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
            <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Search</label>
        <input id="attendanceSearch" type="text" name="search" value="{{ request('search') }}"
            placeholder="Search..." class="form-control" />
    </div>

    <div class="col-md-3">
        <label class="form-label">Per Page</label>
        <select name="per_page" class="form-select">
            @foreach ([10, 20, 50, 100, 200, 300, 500, 700] as $page)
                <option value="{{ $page }}" {{ request('per_page', 20) == $page ? 'selected' : '' }}>
                    {{ $page }}</option>
            @endforeach
        </select>
    </div>

    <!-- Actions -->
    <div class="col-12 text-end">
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
