@extends('layouts/layoutMaster')

@section('title', 'Reports')

@section('content')
<div class="row g-6">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-1">Attendance Reports</h4>
        <p class="text-muted mb-0">Select a report below or use filters to preview.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-4">
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.reports.daily') }}" class="text-reset text-decoration-none">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="icon-base ri ri-calendar-check-line icon-28px me-2 text-primary"></span>
                <h5 class="mb-0">Daily Attendance</h5>
              </div>
              <p class="mb-0 text-muted">View today's attendance records and summary.</p>
            </div>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.reports.monthly') }}" class="text-reset text-decoration-none">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="icon-base ri ri-bar-chart-2-line icon-28px me-2 text-info"></span>
                <h5 class="mb-0">Monthly Summary</h5>
              </div>
              <p class="mb-0 text-muted">Summaries, trends and charts by month.</p>
            </div>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.reports.individual') }}" class="text-reset text-decoration-none">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="icon-base ri ri-user-time-line icon-28px me-2 text-success"></span>
                <h5 class="mb-0">Individual History</h5>
              </div>
              <p class="mb-0 text-muted">Attendance for a specific student or staff.</p>
            </div>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.reports.absenteeism') }}" class="text-reset text-decoration-none">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="icon-base ri ri-time-line icon-28px me-2 text-warning"></span>
                <h5 class="mb-0">Absenteeism & Lateness</h5>
              </div>
              <p class="mb-0 text-muted">Patterns and threshold-based flags.</p>
            </div>
          </div>
        </a>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ route('admin.reports.devices') }}" class="text-reset text-decoration-none">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <span class="icon-base ri ri-device-line icon-28px me-2 text-secondary"></span>
                <h5 class="mb-0">Device/Source Logs</h5>
              </div>
              <p class="mb-0 text-muted">Raw check-ins with metadata.</p>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</div>
@endsection