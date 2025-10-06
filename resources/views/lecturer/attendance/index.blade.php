@extends('layouts/layoutMaster')

@section('title', 'Lecturer Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Today’s Classes</h4>
</div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Course</th>
          <th>Group</th>
          <th>Location</th>
          <th>Start</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($schedules as $schedule)
          <tr>
            <td>{{ optional($schedule->course)->name }}</td>
            <td>{{ optional($schedule->group)->name }}</td>
            <td>{{ $schedule->location }}</td>
            <td>{{ $schedule->start_at->format('Y-m-d H:i') }}</td>
            <td class="text-end">
              <a class="btn btn-sm btn-primary" href="{{ route('lecturer.attendance.edit', $schedule) }}">Mark Attendance</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center">No classes scheduled for today.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $schedules->links() }}</div>
</div>
@endsection