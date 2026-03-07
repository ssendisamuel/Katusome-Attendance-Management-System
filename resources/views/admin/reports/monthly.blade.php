@extends('layouts/layoutMaster')

@section('title', 'Monthly Summary')

@section('content')
  <div class="row g-6">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Monthly Summary — {{ $month }}/{{ $year }}</h4>
    </div>

    {{-- Filters Card --}}
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form class="row g-4" method="GET" action="{{ route('admin.reports.monthly') }}">
            <div class="col-12 col-md-2">
              <label class="form-label">Month</label>
              <input type="number" min="1" max="12" name="month" value="{{ $month }}"
                class="form-control" required />
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label">Year</label>
              <input type="number" name="year" value="{{ $year }}" class="form-control" required />
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

            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri ri-filter-3-line me-1"></i>Apply
              </button>
              <a href="{{ route('admin.reports.monthly') }}" class="btn btn-outline-secondary">
                <i class="ri ri-refresh-line"></i>
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    {{-- At-Risk Alert --}}
    @if ($at_risk_count > 0)
      <div class="col-12">
        <div class="alert alert-warning mb-0">
          <i class="ri ri-alert-line me-2"></i>
          <strong>Warning:</strong> {{ $at_risk_count }} student(s) are at risk with attendance below 70%.
          They are highlighted in red below.
        </div>
      </div>
    @endif

    {{-- Summary Stats --}}
    <div class="col-12">
      <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card h-100 bg-primary-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-group-line text-primary"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Total Students</p>
                  <h4 class="mb-0 text-primary">{{ $total_students }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card h-100 bg-success-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-shield-check-line text-success"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">On Track</p>
                  <h4 class="mb-0 text-success">{{ $total_students - $at_risk_count }}</h4>
                  <small class="text-muted">≥ 70% attendance</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card h-100 bg-danger-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-alert-line text-danger"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">At Risk</p>
                  <h4 class="mb-0 text-danger">{{ $at_risk_count }}</h4>
                  <small class="text-muted">
                    < 70% attendance</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card h-100 bg-label-primary">
            <div class="card-body">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <i class="ri ri-bar-chart-line text-primary"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Groups</p>
                  <h4 class="mb-0 text-primary">{{ $byGroup->count() }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Group Performance --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Group Performance</h5>
          <a class="btn btn-outline-success"
            href="{{ route('admin.reports.monthly.csv', ['month' => $month, 'year' => $year]) }}">
            <i class="ri ri-file-excel-line me-1"></i>Export CSV
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Group</th>
                <th>Students</th>
                <th>Expected</th>
                <th>Present</th>
                <th>Late</th>
                <th>Absent</th>
                <th>At Risk</th>
                <th>Rate</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($byGroup as $groupName => $stats)
                <tr>
                  <td><strong>{{ $groupName ?: 'No Group' }}</strong></td>
                  <td>{{ $stats['student_count'] }}</td>
                  <td>{{ $stats['expected'] }}</td>
                  <td>{{ $stats['present'] }}</td>
                  <td>{{ $stats['late'] }}</td>
                  <td>{{ $stats['absent'] }}</td>
                  <td>
                    @if ($stats['at_risk_count'] > 0)
                      <span class="badge bg-label-danger">{{ $stats['at_risk_count'] }}</span>
                    @else
                      <span class="text-muted">0</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-label-{{ $stats['rate'] >= 70 ? 'success' : 'danger' }}">
                      {{ number_format($stats['rate'], 1) }}%
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Student Details Table --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Student Attendance Details</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Student</th>
                <th>Group</th>
                <th>Expected</th>
                <th>Present</th>
                <th>Late</th>
                <th>Excused</th>
                <th>Absent</th>
                <th>Attendance %</th>
              </tr>
            </thead>
            <tbody>
              @forelse($summary as $row)
                <tr class="{{ $row['at_risk'] ? 'table-danger' : '' }}">
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      @if ($row['at_risk'])
                        <i class="ri ri-alert-line text-danger"></i>
                      @endif
                      <div>
                        <div>{{ $row['student']->name }}</div>
                        <small class="text-muted">{{ $row['student']->reg_no ?? $row['student']->student_no }}</small>
                      </div>
                    </div>
                  </td>
                  <td>{{ $row['student']->group->name ?? 'N/A' }}</td>
                  <td>{{ $row['expected'] }}</td>
                  <td>{{ $row['present'] }}</td>
                  <td>{{ $row['late'] }}</td>
                  <td>{{ $row['excused'] }}</td>
                  <td>
                    {{ $row['absent'] }}
                    @if ($row['implicit_absent'] > 0)
                      <small class="text-muted">({{ $row['implicit_absent'] }} implicit)</small>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-label-{{ $row['percentage'] >= 70 ? 'success' : 'danger' }}">
                      {{ number_format($row['percentage'], 1) }}%
                    </span>
                    @if ($row['at_risk'])
                      <span class="badge bg-label-danger ms-1">At Risk</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">No Data Found</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
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
      const yearSelect = document.querySelector('select[name="year_of_study"]');
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
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/faculties-by-campus?campus_id=${campusId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(facultySelect, data);
              if (departmentSelect) updateSelect(departmentSelect, []);
              if (programSelect) updateSelect(programSelect, []);
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
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/departments-by-faculty?faculty_id=${facultyId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(departmentSelect, data);
              if (programSelect) updateSelect(programSelect, []);
              if (groupSelect) updateSelect(groupSelect, []);
            });
        });
      }

      if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
          const departmentId = this.value;
          if (!departmentId) {
            if (programSelect) updateSelect(programSelect, []);
            if (groupSelect) updateSelect(groupSelect, []);
            return;
          }
          fetch(`/admin/api/programs-by-department?department_id=${departmentId}`)
            .then(r => r.json())
            .then(data => {
              updateSelect(programSelect, data);
              if (groupSelect) updateSelect(groupSelect, []);
            });
        });
      }

      function loadGroups() {
        const programId = programSelect ? programSelect.value : null;
        const yearOfStudy = yearSelect ? yearSelect.value : null;
        if (!programId) {
          if (groupSelect) updateSelect(groupSelect, []);
          return;
        }
        if (groupSelect) {
          let url = `/admin/api/groups-by-program?program_id=${programId}`;
          if (yearOfStudy) url += `&year_of_study=${yearOfStudy}`;
          fetch(url).then(r => r.json()).then(data => updateSelect(groupSelect, data));
        }
      }

      if (programSelect) programSelect.addEventListener('change', loadGroups);
      if (yearSelect) yearSelect.addEventListener('change', loadGroups);
    });
  </script>
@endsection
