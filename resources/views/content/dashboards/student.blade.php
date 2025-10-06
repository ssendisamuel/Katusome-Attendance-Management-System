@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Student Dashboard')

@section('content')
<div class="row gy-6">
  @if(session('success'))
    <div class="col-12">
      <div class="alert alert-success">{{ session('success') }}</div>
    </div>
  @endif
  <!-- Top stats and donut chart -->
  <div class="col-12">
    <div class="card bg-transparent shadow-none border-0 mb-6">
      <div class="card-body row g-6 p-0 pb-5">
        <div class="col-12 col-md-8 card-separator">
          <h5 class="mb-2">Your Attendance Overview</h5>
          <div class="row g-4 me-12">
            <div class="col-12 col-sm-6 col-lg-6">
              <div class="card h-100 bg-primary-subtle">
                <div class="card-body d-flex align-items-center gap-4">
                  <div class="avatar avatar-lg">
                    <div class="avatar-initial rounded bg-white">
                      <span class="icon-base ri ri-checkbox-circle-line icon-28px text-primary"></span>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-1 fw-medium text-primary text-nowrap">Present Today</p>
                    <span class="text-primary mb-0 h5">—</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-6">
              <div class="card h-100 bg-warning-subtle">
                <div class="card-body d-flex align-items-center gap-4">
                  <div class="avatar avatar-lg">
                    <div class="avatar-initial rounded bg-white">
                      <span class="icon-base ri ri-time-line icon-28px text-warning"></span>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-1 fw-medium text-warning text-nowrap">Late</p>
                    <span class="text-warning mb-0 h5">—</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4 ps-md-4 ps-lg-6">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div>
                <p class="mb-9">Time Spent</p>
              </div>
              <div class="time-spending-chart">
                <div id="leadsReportChart"></div>
              </div>
            </div>
            <div>
              <span class="icon-base ri ri-bar-chart-2-line icon-32px text-success"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Courses You Are Taking Today -->
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Courses You Are Taking Today</h5>
        <span class="text-muted">{{ \Carbon\Carbon::today()->format('D, M j') }}</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>Course</th>
                <th>Lecturer</th>
                <th>Time</th>
                <th>Location</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($schedules) && (is_array($schedules) ? count($schedules) : ($schedules instanceof \Illuminate\Support\Collection ? $schedules->count() : (method_exists($schedules, 'count') ? $schedules->count() : 0))))
                @foreach($schedules as $schedule)
                  @php
                    $att = $attendanceBySchedule[$schedule->id] ?? null;
                    $markedText = $att ? ('Marked at ' . $att->marked_at->format('h:i A')) : 'Not Marked';
                    $now = now();
                    $withinTime = $now->between($schedule->start_at, $schedule->end_at);
                    $canRecord = !$att && $withinTime;
                  @endphp
                  <tr>
                    <td>{{ optional($schedule->course)->name }}</td>
                    <td>{{ optional($schedule->lecturer)->name }}</td>
                    <td>{{ $schedule->start_at->format('h:i A') }} – {{ $schedule->end_at->format('h:i A') }}</td>
                    <td>{{ $schedule->location }}</td>
                    <td>
                      @if($att)
                        <span class="badge bg-success">{{ $markedText }}</span>
                      @else
                        <span class="badge bg-secondary">{{ $markedText }}</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <a href="{{ route('attendance.checkin.show', $schedule) }}"
                         class="btn btn-sm btn-primary {{ $canRecord ? '' : 'disabled' }}"
                         @if(!$canRecord) aria-disabled="true" @endif>
                        Record Attendance
                      </a>
                      @if(!$withinTime)
                        <div class="small text-muted mt-1">Available during class time only</div>
                      @endif
                    </td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="6" class="text-center py-4">No classes scheduled for today.</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Horizontal bar chart and progress -->
  <div class="col-12">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0 me-2">Your Courses</h5>
      </div>
      <div class="card-body">
        <div id="horizontalBarChart" class="mb-5"></div>
        <div class="d-flex gap-4 flex-wrap">
          <div class="chart-progress" data-color="primary" data-series="75" data-progress_variant="true"></div>
          <div class="chart-progress" data-color="info" data-series="45" data-progress_variant="false"></div>
          <div class="chart-progress" data-color="success" data-series="30" data-progress_variant="false"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Courses DataTable -->
  <div class="col-12">
    <div class="card">
      <div class="table-responsive">
        <table class="datatables-academy-course table">
          <thead>
            <tr>
              <th></th>
              <th></th>
              <th>Course</th>
              <th>Time</th>
              <th>Progress</th>
              <th>Status</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/moment/moment.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-academy-dashboard.js'])
@endsection