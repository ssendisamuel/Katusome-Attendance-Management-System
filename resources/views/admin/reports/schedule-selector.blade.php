@extends('layouts/layoutMaster')

@section('title', 'Class Attendance Reports')

@section('content')
  <div class="row g-6">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Class Attendance Reports</h4>
    </div>

    {{-- Filters Card --}}
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="row g-4" method="GET" action="{{ route('admin.reports.schedules') }}">
            <div class="col-12 col-md-2">
              <label class="form-label">Date</label>
              <input type="date" name="date" value="{{ $date }}" class="form-control" />
            </div>

            {{-- Hierarchical Filters --}}
            <div class="col-12 col-md-2">
              <label class="form-label">Campus</label>
              <select name="campus_id" class="form-select">
                <option value="">All Campuses</option>
                @foreach ($campuses as $campus)
                  <option value="{{ $campus->id }}" @selected(request('campus_id') == $campus->id)>
                    {{ $campus->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Faculty</label>
              <select name="faculty_id" class="form-select">
                <option value="">All Faculties</option>
                @foreach ($faculties as $faculty)
                  <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>
                    {{ $faculty->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Department</label>
              <select name="department_id" class="form-select">
                <option value="">All Departments</option>
                @foreach ($departments as $dept)
                  <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>
                    {{ $dept->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Program</label>
              <select name="program_id" class="form-select">
                <option value="">All Programs</option>
                @foreach ($programs as $program)
                  <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>
                    {{ $program->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Course</label>
              <select name="course_id" class="form-select">
                <option value="">All Courses</option>
                @foreach ($courses as $c)
                  <option value="{{ $c->id }}" @selected(request('course_id') == $c->id)>
                    {{ $c->code }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Group</label>
              <select name="group_id" class="form-select">
                <option value="">All Groups</option>
                @foreach ($groups as $g)
                  <option value="{{ $g->id }}" @selected(request('group_id') == $g->id)>
                    {{ $g->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label">Lecturer</label>
              <select name="lecturer_id" class="form-select">
                <option value="">All Lecturers</option>
                @foreach ($lecturers as $lec)
                  <option value="{{ $lec->id }}" @selected(request('lecturer_id') == $lec->id)>
                    {{ $lec->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri ri-filter-3-line me-1"></i>Filter
              </button>
              <a href="{{ route('admin.reports.schedules') }}" class="btn btn-outline-secondary">
                <i class="ri ri-refresh-line"></i>
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Schedules List --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Select a Class to View Attendance</h5>
          <small class="text-muted">Click "View Attendance" to see real-time attendance for any class</small>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Course</th>
                <th>Group</th>
                <th>Lecturer</th>
                <th>Venue</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schedules as $schedule)
                @php
                  $now = now();
                  $isUpcoming = $schedule->start_at->isFuture();
                  $isOngoing = $schedule->start_at->isPast() && $schedule->end_at->isFuture();
                  $isPast = $schedule->end_at->isPast();
                @endphp
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $schedule->start_at->format('d M Y') }}</span>
                      <small class="text-muted">
                        {{ $schedule->start_at->format('H:i') }} - {{ $schedule->end_at->format('H:i') }}
                      </small>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span>{{ $schedule->course->name }}</span>
                      <small class="text-muted">{{ $schedule->course->code }}</small>
                    </div>
                  </td>
                  <td>{{ $schedule->group->name ?? 'N/A' }}</td>
                  <td>{{ $schedule->lecturer->name ?? 'N/A' }}</td>
                  <td>
                    @if ($schedule->is_online)
                      <span class="badge bg-label-info">Online</span>
                    @else
                      {{ $schedule->venue->name ?? 'N/A' }}
                    @endif
                  </td>
                  <td>
                    @if ($isOngoing)
                      <span class="badge bg-success">
                        <i class="ri ri-live-line me-1"></i>Ongoing
                      </span>
                    @elseif ($isUpcoming)
                      <span class="badge bg-label-primary">Upcoming</span>
                    @else
                      <span class="badge bg-label-secondary">Completed</span>
                    @endif
                  </td>
                  <td>
                    <a href="{{ route('admin.reports.schedule.attendance', $schedule->id) }}"
                      class="btn btn-sm btn-primary">
                      <i class="ri ri-eye-line me-1"></i>View Attendance
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    No schedules found. Try adjusting your filters.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($schedules->hasPages())
          <div class="card-footer">
            {{ $schedules->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection


@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const campusSelect = document.querySelector('select[name="campus_id"]');
      const facultySelect = document.querySelector('select[name="faculty_id"]');
      const departmentSelect = document.querySelector('select[name="department_id"]');
      const programSelect = document.querySelector('select[name="program_id"]');
      const courseSelect = document.querySelector('select[name="course_id"]');
      const groupSelect = document.querySelector('select[name="group_id"]');

      function updateSelect(selectElement, items, valueKey = 'id', textKey = 'name') {
        if (!selectElement) return;
        const currentValue = selectElement.value;
        const firstOption = selectElement.querySelector('option:first-child');
        const placeholderText = firstOption ? firstOption.textContent : 'All';
        selectElement.innerHTML = `<option value="">${placeholderText}</option>`;
        items.forEach(item => {
          const option = document.createElement('option');
          option.value = item[valueKey];
          option.textContent = item.code ? `${item.code} - ${item[textKey]}` : item[textKey];
          if (item[valueKey] == currentValue) option.selected = true;
          selectElement.appendChild(option);
        });
      }

      if (campusSelect) {
        campusSelect.addEventListener('change', function() {
          const campusId = this.value;
          if (!campusId) {
            if (facultySelect) updateSelect(facultySelect, []);
            if (departmentSelect) updateSelect(departmentSelect, []);
            if (programSelect) updateSelect(programSelect, []);
            if (courseSelect) updateSelect(courseSelect, []);
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/faculties-by-campus?campus_id=${campusId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(facultySelect, data);
              if (departmentSelect) updateSelect(departmentSelect, []);
              if (programSelect) updateSelect(programSelect, []);
              if (courseSelect) updateSelect(courseSelect, []);
              if (groupSelect) updateSelect(groupSelect, []);
            });
        });
      }

      if (facultySelect) {
        facultySelect.addEventListener('change', function() {
          const facultyId = this.value;
          if (!facultyId) {
            if (departmentSelect) updateSelect(departmentSelect, []);
            if (programSelect) updateSelect(programSelect, []);
            if (courseSelect) updateSelect(courseSelect, []);
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/departments-by-faculty?faculty_id=${facultyId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(departmentSelect, data);
              if (programSelect) updateSelect(programSelect, []);
              if (courseSelect) updateSelect(courseSelect, []);
              if (groupSelect) updateSelect(groupSelect, []);
            });
        });
      }

      if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
          const departmentId = this.value;
          if (!departmentId) {
            if (programSelect) updateSelect(programSelect, []);
            if (courseSelect) updateSelect(courseSelect, []);
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/programs-by-department?department_id=${departmentId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(programSelect, data);
              if (courseSelect) updateSelect(courseSelect, []);
              if (groupSelect) updateSelect(groupSelect, []);
            });
        });
      }

      if (programSelect) {
        programSelect.addEventListener('change', function() {
          const programId = this.value;
          if (!programId) {
            if (courseSelect) updateSelect(courseSelect, []);
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          if (courseSelect) {
            fetch(`/admin/api/courses-by-program?program_id=${programId}`)
              .then(r => r.json())
              .then(data => updateSelect(courseSelect, data));
          }
          if (groupSelect) {
            fetch(`/admin/api/groups-by-program?program_id=${programId}`)
              .then(r => r.json())
              .then(data => updateSelect(groupSelect, data));
          }
        });
      }
    });
  </script>
@endsection
