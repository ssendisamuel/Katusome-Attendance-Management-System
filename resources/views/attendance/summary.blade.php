@extends('layouts/layoutMaster')

@section('title', 'Record Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Record Attendance — {{ optional($schedule->course)->name }} ({{ optional($schedule->course)->code }})</h4>
  <span class="text-muted">{{ $schedule->start_at->format('H:i') }}–{{ $schedule->end_at->format('H:i') }} @ {{ $schedule->location }}</span>
</div>

<div class="card">
  <div class="card-body">
    <ul class="list-unstyled mb-4">
      <li><strong>Student:</strong> {{ $student->name }} ({{ $student->reg_no ?? $student->student_no }})</li>
      <li><strong>Course:</strong> {{ optional($schedule->course)->name }} ({{ optional($schedule->course)->code }})</li>
@php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
      <li><strong>Lecturer:</strong> {{ ($hasPivot && $schedule->relationLoaded('lecturers') && $schedule->lecturers && $schedule->lecturers->count()) ? $schedule->lecturers->pluck('name')->implode(', ') : (optional($schedule->lecturer)->name ?? '—') }}</li>
      <li><strong>Start Time:</strong> {{ $schedule->start_at->format('H:i') }}</li>
      <li><strong>Current Time:</strong> {{ \Carbon\Carbon::now()->format('H:i:s') }}</li>
    </ul>

    @if($attendance)
      <div class="alert alert-info">Already marked: {{ ucfirst($attendance->status) }} at {{ optional($attendance->marked_at)->format('H:i') }}</div>
      @if($attendance->selfie_path)
        <div class="mt-3">
          <p class="mb-2">Selfie captured at check-in:</p>
          <img src="{{ url(\Illuminate\Support\Facades\Storage::url($attendance->selfie_path)) }}" alt="Attendance selfie" class="img-fluid rounded" style="max-width: 320px;" />
        </div>
      @endif
    @endif

    <div class="mt-4">
      <a href="{{ route('student.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
      <a href="{{ route('attendance.checkin.show', $schedule) }}" class="btn btn-outline-secondary">Return to Check-In</a>
    </div>
  </div>
</div>
@endsection