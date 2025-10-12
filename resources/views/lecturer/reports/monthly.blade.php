@extends('layouts/layoutMaster')

@section('title', 'Monthly Summary')

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Monthly Summary — {{ sprintf('%02d', $month) }}/{{ $year }}</h4>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="card-body">
        <form class="row g-4" method="GET" action="{{ route('lecturer.reports.monthly') }}">
          <div class="col-12 col-md-3">
            <label class="form-label">Month</label>
            <select name="month" class="form-select">
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @selected($m==$month)>{{ $m }}</option>
              @endfor
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="form-control" />
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex gap-2">
                <a href="{{ route('lecturer.reports.monthly') }}" class="btn btn-outline-secondary">Reset</a>
                <button class="btn btn-primary">Filter</button>
              </div>
              <div class="report-actions d-flex gap-2">
                <a href="{{ route('lecturer.reports.monthly.export.csv', request()->query()) }}" class="btn btn-outline-secondary">
                  <span class="icon-base ri ri-file-list-2-line me-2"></span> CSV
                </a>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Expected</p>
            <h4 class="mb-0">{{ $summary['expected'] }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Present</p>
            <h4 class="mb-0 text-success">{{ $summary['present'] }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Absent</p>
            <h4 class="mb-0 text-danger">{{ $summary['absent'] }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Late</p>
            <h4 class="mb-0 text-warning">{{ $summary['late'] }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Expected</th>
              <th>Present</th>
              <th>Absent</th>
              <th>Late</th>
            </tr>
          </thead>
          <tbody>
            @forelse($trend as $date => $stat)
              <tr>
                <td>{{ $date }}</td>
                <td>{{ $stat['expected'] }}</td>
                <td>{{ $stat['present'] }}</td>
                <td>{{ $stat['absent'] }}</td>
                <td>{{ $stat['late'] }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center">No Data Found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-style')
<style>
  .report-actions .btn { padding: 0.5rem 1rem; line-height: 1.5; display: inline-flex; align-items: center; }
  .report-actions .btn .icon-base { line-height: 1; }
</style>
@endsection