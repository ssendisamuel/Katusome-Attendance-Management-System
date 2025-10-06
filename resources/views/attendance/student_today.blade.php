@extends('layouts/layoutMaster')

@section('title', 'Attendance — Today')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Attendance — Today</h4>
  <span class="text-muted">{{ \Carbon\Carbon::today()->format('D, M j') }}</span>
</div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">Courses You Are Taking Today</h5>
    <span class="text-muted">Welcome, {{ $student->name }}</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>Course</th>
            <th>Time</th>
            <th class="text-end">Actions</th>
            <th>Status</th>
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
                <td>{{ $schedule->start_at->format('h:i A') }} – {{ $schedule->end_at->format('h:i A') }}</td>
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
                <td>
                  @if($att)
                    <span class="badge bg-success">{{ $markedText }}</span>
                  @else
                    <span class="badge bg-secondary">{{ $markedText }}</span>
                  @endif
                </td>
              </tr>
            @endforeach
          @else
            <tr>
              <td colspan="4" class="text-center py-4">No classes scheduled for today.</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection