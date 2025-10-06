@extends('layouts/layoutMaster')

@section('title', 'Mark Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Mark Attendance — {{ optional($schedule->course)->name }} ({{ optional($schedule->group)->name }})</h4>
  <span class="text-muted">{{ $schedule->start_at->format('Y-m-d H:i') }} @ {{ $schedule->location }}</span>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('lecturer.attendance.update', $schedule) }}" class="table-responsive">
      @csrf
      <table class="table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($students as $student)
            @php($existingRec = $existing[$student->id] ?? null)
            <tr>
              <td>{{ $student->name }}</td>
              <td>
                <select name="statuses[{{ $student->id }}]" class="form-select">
                  @php($current = $existingRec?->status)
                  <option value="present" {{ $current === 'present' ? 'selected' : '' }}>Present</option>
                  <option value="late" {{ $current === 'late' ? 'selected' : '' }}>Late</option>
                  <option value="absent" {{ $current === 'absent' ? 'selected' : '' }}>Absent</option>
                </select>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection