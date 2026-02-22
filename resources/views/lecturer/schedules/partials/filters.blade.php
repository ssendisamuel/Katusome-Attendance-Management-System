<form id="scheduleFilters" method="GET" action="{{ route('lecturer.schedules.index') }}" class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select">
            <option value="">All My Courses</option>
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
        <select name="group_id" class="form-select">
            <option value="">All Groups</option>
            @isset($groups)
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                        {{ $group->name }}</option>
                @endforeach
            @endisset
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ request('date') }}" class="form-control" />
    </div>
    <div class="col-md-3 d-flex align-items-end justify-content-end">
        <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
