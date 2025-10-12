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
        <form class="row g-4" method="GET" action="{{ route('lecturer.reports.daily') }}">
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
              <option value="">All</option>
              <option value="present" @selected(request('status')==='present')>Present</option>
              <option value="late" @selected(request('status')==='late')>Late</option>
              <option value="absent" @selected(request('status')==='absent')>Absent</option>
            </select>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex gap-2">
                <a href="{{ route('lecturer.reports.daily') }}" class="btn btn-outline-secondary">Reset</a>
                <button class="btn btn-primary">Filter</button>
              </div>
              <div class="report-actions d-flex gap-2">
                <a href="{{ route('lecturer.reports.daily.export.csv', request()->query()) }}" class="btn btn-outline-secondary">
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
            <h4 class="mb-0">{{ $expected }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Present</p>
            <h4 class="mb-0 text-success">{{ $present }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Absent</p>
            <h4 class="mb-0 text-danger">{{ $absent }}</h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Late</p>
            <h4 class="mb-0 text-warning">{{ $late }}</h4>
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
              <th>Student</th>
              <th>Group</th>
              <th>Course</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($attendances as $row)
              <tr>
                <td>{{ optional($row->student->user)->name ?? optional($row->student)->name }}</td>
                <td>{{ optional($row->schedule->group)->name }}</td>
                <td>{{ optional($row->schedule->course)->name }}</td>
                <td>{{ optional($row->marked_at)?->format('H:i') }}</td>
                <td>{{ ucfirst($row->status) }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center">No Data Found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer">{{ $attendances->withQueryString()->links() }}</div>
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