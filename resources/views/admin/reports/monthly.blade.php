@extends('layouts/layoutMaster')

@section('title', 'Monthly Summary')

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Monthly Summary — {{ $month }}/{{ $year }}</h4>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="card-body">
        <form class="row g-4" method="GET" action="{{ route('admin.reports.monthly') }}">
          <div class="col-6 col-md-2">
            <label class="form-label">Month</label>
            <input type="number" min="1" max="12" name="month" value="{{ $month }}" class="form-control" />
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Year</label>
            <input type="number" name="year" value="{{ $year }}" class="form-control" />
          </div>
          <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
            <button class="btn btn-primary">Apply</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title m-0">Attendance Trends</h5>
      </div>
      <div class="card-body">
        <div id="monthlyTrend"></div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Group Performance</h5>
        <a class="btn btn-outline-success" href="{{ route('admin.reports.monthly.csv',['month'=>$month,'year'=>$year]) }}"><span class="icon-base ri ri-file-text-line me-1"></span>CSV</a>
      </div>
      <div class="card-body">
        <div id="groupComparison"></div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-end mb-2 gap-2">
          <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
          <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#monthlySummaryTable" data-title="Monthly Summary — {{ $month }}/{{ $year }}" data-filename="Monthly_Summary_{{ $year }}_{{ $month }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Monthly" data-json-url="{{ route('admin.reports.monthly.json',['month'=>$month,'year'=>$year]) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
          <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#monthlySummaryTable" data-title="Monthly Summary — {{ $month }}/{{ $year }}" data-filename="Monthly_Summary_{{ $year }}_{{ $month }}.xlsx" data-json-url="{{ route('admin.reports.monthly.json',['month'=>$month,'year'=>$year]) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
        </div>
        <div class="table-responsive">
          <table id="monthlySummaryTable" class="table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Attendance %</th>
              </tr>
            </thead>
            <tbody>
              @forelse($summary as $row)
              <tr>
                <td>{{ $row['student']->name }}</td>
                <td>{{ $row['present'] }}</td>
                <td>{{ $row['absent'] }}</td>
                <td>{{ $row['late'] }}</td>
                <td>{{ $row['percentage'] }}%</td>
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
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
  const trendOptions = {
    chart: { type: 'line', height: 300 },
    series: [
      {
        name: 'Attendance %',
        data: @json(collect($summary)->map(fn($r)=>$r['percentage']))
      }
    ],
    xaxis: { categories: @json(collect($summary)->map(fn($r)=>$r['student']->name)) }
  };
  const trendChart = new ApexCharts(document.querySelector('#monthlyTrend'), trendOptions);
  trendChart.render();

  const groupOptions = {
    chart: { type: 'bar', height: 300 },
    series: [{ name: 'Rate', data: @json($byGroup->values()->map(fn($g)=>$g['rate'])) }],
    xaxis: { categories: @json($byGroup->keys()) }
  };
  const groupChart = new ApexCharts(document.querySelector('#groupComparison'), groupOptions);
  groupChart.render();
</script>
@endsection