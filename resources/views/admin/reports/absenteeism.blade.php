@extends('layouts/layoutMaster')

@section('title', 'Absenteeism & Lateness')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Absenteeism & Lateness</h4>
  <div></div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.reports.absenteeism') }}" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Start Date</label>
        <input type="date" name="start" value="{{ $start->toDateString() }}" class="form-control" />
      </div>
      <div class="col-md-4">
        <label class="form-label">End Date</label>
        <input type="date" name="end" value="{{ $end->toDateString() }}" class="form-control" />
      </div>
      <div class="col-md-2">
        <label class="form-label">Threshold</label>
        <input type="number" name="threshold" value="{{ $threshold }}" class="form-control" />
      </div>
      <div class="col-md-2 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.reports.absenteeism') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-outline-primary"><span class="icon-base ri ri-filter-3-line me-1"></span>Apply Filters</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="absenteeismTable" class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Student</th>
            <th>Group</th>
            <th>Late Count</th>
            <th>Absent Count</th>
            <th>Flag</th>
          </tr>
        </thead>
        <tbody>
          @forelse($patterns as $row)
            <tr>
              <td>{{ optional($row['student']->user)->name ?? $row['student']->name }}</td>
              <td>{{ optional($row['student']->group)->name }}</td>
              <td>{{ $row['late'] }}</td>
              <td>{{ $row['absent'] }}</td>
              <td>
                @if($row['flag'])
                  <span class="badge bg-danger">Flagged</span>
                @else
                  <span class="badge bg-success">OK</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted">No data found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end gap-2">
      <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
      <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#absenteeismTable" data-title="Absenteeism & Lateness" data-filename="Absenteeism_Lateness_{{ $start->toDateString() }}_to_{{ $end->toDateString() }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Absenteeism" data-json-url="{{ route('admin.reports.absenteeism.json',['start'=>$start->toDateString(),'end'=>$end->toDateString(),'threshold'=>$threshold]) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
      <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#absenteeismTable" data-title="Absenteeism & Lateness" data-filename="Absenteeism_Lateness_{{ $start->toDateString() }}_to_{{ $end->toDateString() }}.xlsx" data-json-url="{{ route('admin.reports.absenteeism.json',['start'=>$start->toDateString(),'end'=>$end->toDateString(),'threshold'=>$threshold]) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      <a class="btn btn-outline-success" href="{{ route('admin.reports.absenteeism.csv', ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'threshold' => $threshold]) }}"><span class="icon-base ri ri-file-text-line me-1"></span>CSV</a>
    </div>
  </div>
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
@endsection