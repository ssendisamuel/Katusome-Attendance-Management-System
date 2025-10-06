<form id="scheduleFilters" method="GET" action="{{ route('admin.schedules.index') }}" class="row g-3 mb-3">
  <div class="col-md-3">
    <label class="form-label">Course</label>
    <select name="course_id" class="form-select">
      <option value="">All</option>
      @isset($courses)
        @foreach($courses as $course)
          <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
        @endforeach
      @endisset
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Group</label>
    <select name="group_id" class="form-select">
      <option value="">All</option>
      @isset($groups)
        @foreach($groups as $group)
          <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
        @endforeach
      @endisset
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Lecturer</label>
    <select name="lecturer_id" class="form-select">
      <option value="">All</option>
      @isset($lecturers)
        @foreach($lecturers as $lecturer)
          <option value="{{ $lecturer->id }}" {{ request('lecturer_id') == $lecturer->id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
        @endforeach
      @endisset
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Date</label>
    <input type="date" name="date" value="{{ request('date') }}" class="form-control" />
  </div>
  <div class="col-md-6">
    <label class="form-label">Search by name or location</label>
    <input id="scheduleSearch" type="text" name="search" value="{{ request('search') }}" placeholder="Course, Group, Lecturer, Series, Location" class="form-control" />
  </div>
  <div class="col-md-6 d-flex align-items-end justify-content-end">
    <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
    <button type="submit" class="btn btn-primary">Filter</button>
  </div>
</form>