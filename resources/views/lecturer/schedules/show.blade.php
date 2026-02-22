@extends('layouts/layoutMaster')

@section('title', 'Schedule Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Schedule Details</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('lecturer.attendance.edit', $schedule->id) }}" class="btn btn-success">
                <i class="ti ti-checkup-list me-1"></i> Mark Attendance
            </a>
            <a href="{{ route('lecturer.schedules.edit', $schedule->id) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Course</label>
                    <p>{{ $schedule->course->name }} ({{ $schedule->course->code }})</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Group</label>
                    <p>{{ $schedule->group->name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Time</label>
                    <p>{{ $schedule->start_at->format('D, M d, Y H:i') }} - {{ $schedule->end_at->format('H:i') }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Location</label>
                    <p>{{ $schedule->location ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold">Status</label>
                    <p><span class="badge bg-label-primary">{{ ucfirst($schedule->attendance_status) }}</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Attendance Records</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Marked At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedule->attendanceRecords as $att)
                        <tr>
                            <td>{{ optional($att->student->user)->name ?? $att->student->student_no }}</td>
                            <td>{{ ucfirst($att->status) }}</td>
                            <td>{{ $att->marked_at?->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No attendance marked yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
