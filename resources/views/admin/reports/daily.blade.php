@extends('layouts/layoutMaster')

@section('title', 'Daily Attendance Report')

@section('content')
  <div class="row g-6">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Daily Attendance Report — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h4>
    </div>

    {{-- Filters Card --}}
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="row g-4" method="GET" action="{{ route('admin.reports.daily') }}">
            <div class="col-12 col-md-2">
              <label class="form-label">Date</label>
              <input type="date" name="date" value="{{ $date }}" class="form-control" required />
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
              <label class="form-label">Year of Study</label>
              <select name="year_of_study" class="form-select">
                <option value="">All Years</option>
                @foreach ($years as $year)
                  <option value="{{ $year }}" @selected(request('year_of_study') == $year)>
                    Year {{ $year }}
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

            <div class="col-12 col-md-2">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="">Any Status</option>
                @foreach (['present', 'late', 'absent', 'excused'] as $st)
                  <option value="{{ $st }}" @selected(request('status') == $st)>
                    {{ ucfirst($st) }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Search</label>
              <input type="text" name="search" placeholder="Search by name, reg no, or student no"
                value="{{ request('search') }}" class="form-control" />
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri ri-filter-3-line me-1"></i>Apply
              </button>
              <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline-secondary">
                <i class="ri ri-refresh-line"></i>
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- Summary Cards --}}
    <div class="col-12">
      <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-primary-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-calendar-check-line text-primary"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Expected</p>
                  <h4 class="mb-0 text-primary">{{ $expected }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-success-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-user-follow-line text-success"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Present</p>
                  <h4 class="mb-0 text-success">{{ $present }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-warning-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-time-line text-warning"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Late</p>
                  <h4 class="mb-0 text-warning">{{ $late }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-info-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-shield-check-line text-info"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Excused</p>
                  <h4 class="mb-0 text-info">{{ $excused }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-danger-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-user-unfollow-line text-danger"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Absent</p>
                  <h4 class="mb-0 text-danger">{{ $absent }}</h4>
                  <small class="text-muted">{{ $explicit_absent }} explicit + {{ $implicit_absent }} implicit</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-2">
          <div class="card h-100 bg-label-primary">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-percent-line text-primary"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Rate</p>
                  <h4 class="mb-0 text-primary">{{ number_format($percentage, 1) }}%</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Additional Info --}}
    <div class="col-12">
      <div class="alert alert-info mb-0">
        <i class="ri ri-information-line me-2"></i>
        <strong>Summary:</strong> {{ $unique_students }} unique students across {{ $total_schedules }} schedules.
        Attendance Rate: {{ number_format($percentage, 1) }}% ({{ $present + $late }} attended out of
        {{ $expected }} expected).
        @if ($incomplete > 0)
          <span class="text-warning">{{ $incomplete }} incomplete sessions (auto-clocked out).</span>
        @endif
      </div>
    </div>

    {{-- Data Table --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Attendance Records</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.daily.csv', request()->all()) }}" class="btn btn-sm btn-outline-success">
              <i class="ri ri-file-excel-line me-1"></i>Export CSV
            </a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Student</th>
                <th>Group</th>
                <th>Course</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($attendances as $row)
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ optional($row->student)->name }}</span>
                      <small
                        class="text-muted">{{ optional($row->student)->reg_no ?? optional($row->student)->student_no }}</small>
                    </div>
                  </td>
                  <td>{{ optional($row->schedule->group)->name }}</td>
                  <td>
                    <div class="d-flex flex-column">
                      <span>{{ optional($row->schedule->course)->name }}</span>
                      <small class="text-muted">{{ optional($row->schedule->course)->code }}</small>
                    </div>
                  </td>
                  <td>{{ optional($row->marked_at)?->format('H:i') }}</td>
                  <td>
                    @if ($row->clock_out_time)
                      {{ $row->clock_out_time->format('H:i') }}
                      @if ($row->is_auto_clocked_out)
                        <span class="badge bg-label-warning" title="Auto Clocked Out">Auto</span>
                      @endif
                    @else
                      —
                    @endif
                  </td>
                  <td>
                    @php
                      $statusColors = [
                          'present' => 'success',
                          'late' => 'warning',
                          'absent' => 'danger',
                          'excused' => 'info',
                      ];
                      $color = $statusColors[$row->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-label-{{ $color }}">{{ ucfirst($row->status) }}</span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No attendance records found for this date</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($attendances->hasPages())
          <div class="card-footer">
            {{ $attendances->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    /**
     * Cascading Filters for Reports
     */
    document.addEventListener('DOMContentLoaded', function() {
      const campusSelect = document.querySelector('select[name="campus_id"]');
      const facultySelect = document.querySelector('select[name="faculty_id"]');
      const departmentSelect = document.querySelector('select[name="department_id"]');
      const programSelect = document.querySelector('select[name="program_id"]');
      const yearSelect = document.querySelector('select[name="year_of_study"]');
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

      function loadCoursesAndGroups() {
        const programId = programSelect ? programSelect.value : null;
        const yearOfStudy = yearSelect ? yearSelect.value : null;
        if (!programId) {
          if (courseSelect) updateSelect(courseSelect, []);
          if (groupSelect) updateSelect(groupSelect, []);
          return;
        }
        if (courseSelect) {
          let url = `/admin/api/courses-by-program?program_id=${programId}`;
          if (yearOfStudy) url += `&year_of_study=${yearOfStudy}`;
          fetch(url).then(r => r.json()).then(data => updateSelect(courseSelect, data));
        }
        if (groupSelect) {
          let url = `/admin/api/groups-by-program?program_id=${programId}`;
          if (yearOfStudy) url += `&year_of_study=${yearOfStudy}`;
          fetch(url).then(r => r.json()).then(data => updateSelect(groupSelect, data));
        }
      }

      if (programSelect) programSelect.addEventListener('change', loadCoursesAndGroups);
      if (yearSelect) yearSelect.addEventListener('change', loadCoursesAndGroups);
    });
  </script>
@endsection
