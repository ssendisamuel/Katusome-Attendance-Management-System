@extends('layouts/layoutMaster')

@section('title', 'Real-Time Schedule Attendance')

@section('content')
  <div class="row g-6">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Real-Time Schedule Attendance</h4>
      <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i>Back to Reports
      </a>
    </div>

    {{-- Schedule Info Card --}}
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="row g-4">
            <div class="col-12 col-md-6">
              <h5 class="mb-3">{{ $schedule->course->name }}</h5>
              <div class="d-flex flex-column gap-2">
                <div><strong>Course Code:</strong> {{ $schedule->course->code }}</div>
                <div><strong>Group:</strong> {{ $schedule->group->name ?? 'N/A' }}</div>
                <div><strong>Lecturer:</strong> {{ $schedule->lecturer->name ?? 'N/A' }}</div>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="d-flex flex-column gap-2">
                <div><strong>Date:</strong> {{ $schedule->start_at->format('d M Y') }}</div>
                <div><strong>Time:</strong> {{ $schedule->start_at->format('H:i') }} -
                  {{ $schedule->end_at->format('H:i') }}</div>
                <div><strong>Venue:</strong>
                  @if ($schedule->is_online)
                    <span class="badge bg-label-info">Online</span>
                  @else
                    {{ $schedule->venue->name ?? 'N/A' }}
                  @endif
                </div>
                <div><strong>Semester:</strong> {{ $schedule->academicSemester->year ?? '' }}
                  {{ $schedule->academicSemester->semester ?? '' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Summary Cards --}}
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
                  <p class="mb-0 small">Expected Students</p>
                  <h4 class="mb-0 text-primary">{{ $stats['expected'] }}</h4>
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
                    <i class="ri ri-user-follow-line text-success"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Checked In</p>
                  <h4 class="mb-0 text-success">{{ $stats['checked_in'] }}</h4>
                  <small class="text-muted">{{ $stats['present'] }} present + {{ $stats['late'] }} late</small>
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
                    <i class="ri ri-user-unfollow-line text-danger"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Not Checked In</p>
                  <h4 class="mb-0 text-danger">{{ $stats['not_checked_in'] }}</h4>
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
                    <i class="ri ri-percent-line text-primary"></i>
                  </div>
                </div>
                <div>
                  <p class="mb-0 small">Attendance Rate</p>
                  <h4 class="mb-0 text-primary">{{ number_format($stats['attendance_rate'], 1) }}%</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Additional Metrics --}}
    <div class="col-12">
      <div class="alert alert-info mb-0">
        <i class="ri ri-information-line me-2"></i>
        <strong>Live Stats:</strong>
        @if ($stats['avg_check_in_time'] !== null)
          Average check-in time: {{ abs($stats['avg_check_in_time']) }} minutes
          {{ $stats['avg_check_in_time'] < 0 ? 'before' : 'after' }} start time.
        @endif
        Late arrivals: {{ $stats['late_arrivals'] }}.
        @if ($stats['excused'] > 0)
          Excused: {{ $stats['excused'] }}.
        @endif
        <span class="text-muted">This page auto-refreshes every 30 seconds.</span>
      </div>
    </div>

    {{-- Student List --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Student Attendance List</h5>
          <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.schedule.attendance', ['schedule' => $schedule->id, 'export' => 'pdf']) }}"
              class="btn btn-sm btn-outline-danger">
              <i class="ri ri-file-pdf-line me-1"></i>Export PDF
            </a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Student</th>
                <th>Reg No</th>
                <th>Program</th>
                <th>Check-In Time</th>
                <th>Status</th>
                <th>Selfie</th>
                <th>Clock Out</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($studentList as $item)
                <tr class="{{ $item['status'] === 'not_checked_in' ? 'table-danger' : '' }}">
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar avatar-sm">
                        <span
                          class="avatar-initial rounded-circle bg-label-{{ $item['status'] === 'present' ? 'success' : ($item['status'] === 'late' ? 'warning' : 'secondary') }}">
                          {{ substr($item['student']->name, 0, 1) }}
                        </span>
                      </div>
                      <span>{{ $item['student']->name }}</span>
                    </div>
                  </td>
                  <td>{{ $item['student']->reg_no ?? $item['student']->student_no }}</td>
                  <td>{{ optional($item['student']->enrollments->first())->program->name ?? 'N/A' }}</td>
                  <td>
                    @if ($item['checked_in_at'])
                      {{ $item['checked_in_at']->format('H:i') }}
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $statusColors = [
                          'present' => 'success',
                          'late' => 'warning',
                          'absent' => 'danger',
                          'excused' => 'info',
                          'not_checked_in' => 'secondary',
                      ];
                      $color = $statusColors[$item['status']] ?? 'secondary';
                      $label = $item['status'] === 'not_checked_in' ? 'Not Checked In' : ucfirst($item['status']);
                    @endphp
                    <span class="badge bg-label-{{ $color }}">{{ $label }}</span>
                  </td>
                  <td>
                    @if ($item['has_selfie'])
                      <i class="ri ri-checkbox-circle-line text-success"></i>
                    @else
                      <i class="ri ri-close-circle-line text-muted"></i>
                    @endif
                  </td>
                  <td>
                    @if ($item['clocked_out'])
                      <i class="ri ri-checkbox-circle-line text-success"></i>
                    @else
                      <i class="ri ri-close-circle-line text-muted"></i>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    // Auto-refresh every 30 seconds
    setTimeout(function() {
      location.reload();
    }, 30000);
  </script>
@endsection
