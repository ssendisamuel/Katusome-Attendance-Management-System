@extends('layouts/layoutMaster')

@section('title', 'Device / Source Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Device / Source Logs</h4>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
    <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#devicesTable" data-title="Device / Source Logs" data-filename="Device_Source_Logs_{{ $start->toDateString() }}_to_{{ $end->toDateString() }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Device Logs" data-json-url="{{ route('admin.reports.devices.json',['start'=>$start->toDateString(),'end'=>$end->toDateString()]) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
    <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#devicesTable" data-title="Device / Source Logs" data-filename="Device_Source_Logs_{{ $start->toDateString() }}_to_{{ $end->toDateString() }}.xlsx" data-json-url="{{ route('admin.reports.devices.json',['start'=>$start->toDateString(),'end'=>$end->toDateString()]) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.reports.devices') }}" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Start Date</label>
        <input type="date" name="start" value="{{ $start->toDateString() }}" class="form-control" />
      </div>
      <div class="col-md-4">
        <label class="form-label">End Date</label>
        <input type="date" name="end" value="{{ $end->toDateString() }}" class="form-control" />
      </div>
      <div class="col-md-4 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.reports.devices') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Apply</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <table id="devicesTable" class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Student</th>
            <th>Course</th>
            <th>Time</th>
            <th>Status</th>
            <th>Location</th>
            <th>Selfie</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $row)
            <tr>
              <td>{{ optional($row->student?->user)->name ?? optional($row->student)->name }}</td>
              <td>{{ optional($row->schedule->course)->name }}</td>
              <td>{{ optional($row->marked_at)?->format('Y-m-d H:i') }}</td>
              <td>{{ ucfirst($row->status) }}</td>
              <td>
                @if($row->lat && $row->lng)
                  {{ $row->lat }}, {{ $row->lng }}
                @else
                  —
                @endif
              </td>
              <td>
                @if($row->selfie_path)
                  <a href="{{ asset('storage/' . $row->selfie_path) }}" target="_blank">View</a>
                @else
                  —
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">No data found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-2">{{ $records->withQueryString()->links() }}</div>
  </div>
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
@endsection