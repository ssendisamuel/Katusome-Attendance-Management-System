<form id="scheduleFilters" method="GET" action="{{ route('admin.schedules.index') }}" class="row g-3 mb-3">

    {{-- Row 1: High Level Filters --}}
    <div class="col-md-3">
        <label class="form-label">Semester</label>
        <select name="academic_semester_id" class="form-select">
            <option value="">All Semesters</option>
            @foreach ($semesters as $sem)
                <option value="{{ $sem->id }}" {{ request('academic_semester_id') == $sem->id ? 'selected' : '' }}>
                    {{ $sem->year }} - {{ $sem->semester }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
            <option value="">All Programs</option>
            @foreach ($programs as $prog)
                <option value="{{ $prog->id }}" {{ request('program_id') == $prog->id ? 'selected' : '' }}>
                    {{ $prog->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Year of Study</label>
        <select name="year_of_study" class="form-select">
            <option value="">All Years</option>
            @foreach ([1, 2, 3, 4, 5] as $y)
                <option value="{{ $y }}" {{ request('year_of_study') == $y ? 'selected' : '' }}>Year
                    {{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select">
            <option value="">All Courses</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                    {{ $course->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Row 2: Detailed Filters --}}
    <div class="col-md-3">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select">
            <option value="">All Groups</option>
            @foreach ($groups as $group)
                <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                    {{ $group->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Lecturer</label>
        <select name="lecturer_id" class="form-select">
            <option value="">All Lecturers</option>
            @foreach ($lecturers as $lecturer)
                <option value="{{ $lecturer->id }}" {{ request('lecturer_id') == $lecturer->id ? 'selected' : '' }}>
                    {{ $lecturer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Date From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" />
    </div>
    <div class="col-md-3">
        <label class="form-label">Date To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" />
    </div>

    {{-- Row 3: Show Rows + Search + Reset --}}
    <div class="col-md-2">
        <label class="form-label">Show Rows</label>
        <select name="per_page" class="form-select">
            @foreach ([10, 20, 50, 100, 200, 300, 500, 700] as $size)
                <option value="{{ $size }}" {{ request('per_page', 15) == $size ? 'selected' : '' }}>
                    {{ $size }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label">Search</label>
        <input id="scheduleSearch" type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by Course, Group, Lecturer, Location..." class="form-control" />
    </div>

    <div class="col-md-2 d-flex align-items-end">
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
    </div>
</form>
