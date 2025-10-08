@extends('layouts/layoutMaster')

@section('title', 'Daily Attendance')

@section('content')
<div class="row g-6">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Daily Attendance — {{ $date }}</h4>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="card-body">
        <form class="row g-4" method="GET" action="{{ route('admin.reports.daily') }}">
          <div class="col-12 col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control" />
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select">
              <option value="">All Courses</option>
              @foreach($courses as $c)
                <option value="{{ $c->id }}" @selected(request('course_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-select">
              <option value="">All Groups</option>
              @foreach($groups as $g)
                <option value="{{ $g->id }}" @selected(request('group_id')==$g->id)>{{ $g->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Any Status</option>
              @foreach(['present','absent','late','excused'] as $st)
                <option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Search</label>
            <input type="text" name="search" placeholder="Search name" value="{{ request('search') }}" class="form-control" />
          </div>
          <div class="col-12 col-md-6 d-flex align-items-end justify-content-end gap-2 report-actions">
            <button class="btn btn-outline-primary"><span class="icon-base ri ri-filter-3-line me-1"></span>Apply Filters</button>
            <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
            <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#dailyTable" data-title="Daily Attendance — {{ $date }}" data-filename="Daily_Attendance_{{ $date }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Daily" data-json-url="{{ route('admin.reports.daily.json',['date'=>$date,'course_id'=>request('course_id'),'group_id'=>request('group_id'),'status'=>request('status'),'search'=>request('search')]) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
            <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#dailyTable" data-title="Daily Attendance — {{ $date }}" data-filename="Daily_Attendance_{{ $date }}.xlsx" data-json-url="{{ route('admin.reports.daily.json',['date'=>$date,'course_id'=>request('course_id'),'group_id'=>request('group_id'),'status'=>request('status'),'search'=>request('search')]) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
            <a href="{{ route('admin.reports.daily.csv',['date'=>$date,'course_id'=>request('course_id'),'group_id'=>request('group_id'),'status'=>request('status'),'search'=>request('search')]) }}" class="btn btn-outline-success"><span class="icon-base ri ri-file-text-line me-1"></span>CSV</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-primary-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-calendar-line icon-22px text-primary"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Expected</p>
              <h4 class="mb-0 text-primary">{{ $expected }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-success-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-user-follow-line icon-22px text-success"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Present</p>
              <h4 class="mb-0 text-success">{{ $present }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-danger-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-user-unfollow-line icon-22px text-danger"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Absent</p>
              <h4 class="mb-0 text-danger">{{ $absent }}</h4>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 bg-warning-subtle">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="avatar avatar-md">
              <div class="avatar-initial rounded bg-white">
                <span class="icon-base ri ri-time-line icon-22px text-warning"></span>
              </div>
            </div>
            <div>
              <p class="mb-0">Late</p>
              <h4 class="mb-0 text-warning">{{ $late }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="dailyTable" class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>ID</th>
                <th>Group</th>
                <th>Course</th>
                <th>Time In</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($attendances as $row)
                <tr>
                  <td>{{ optional($row->student)->name }}</td>
                  <td>{{ optional($row->student)->student_no ?? optional($row->student)->reg_no }}</td>
                  <td>{{ optional($row->schedule->group)->name }}</td>
                  <td>{{ optional($row->schedule->course)->name }}</td>
                  <td>{{ optional($row->marked_at)?->format('H:i') }}</td>
                  <td>{{ ucfirst($row->status) }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center">No Data Found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">{{ $attendances->withQueryString()->links() }}</div>
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

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
@endsection