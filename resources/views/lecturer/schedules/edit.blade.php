@extends('layouts/layoutMaster')

@section('title', 'Edit Schedule')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Schedule</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('lecturer.attendance.edit', $schedule->id) }}" class="btn btn-success">
                <i class="ti ti-checkup-list me-1"></i> Mark Attendance
            </a>
            <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card p-4">
        <form action="{{ route('lecturer.schedules.update', $schedule) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Academic Semester</label>
                    <select name="academic_semester_id" class="form-select select2">
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem->id }}" @selected(old('academic_semester_id', $schedule->academic_semester_id) == $sem->id)>
                                {{ $sem->year }} - {{ $sem->semester }} ({{ $sem->is_active ? 'Active' : 'Inactive' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select select2" required>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}"
                                {{ old('course_id', $schedule->course_id) == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Group</label>
                    <select name="group_id" class="form-select select2" required>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ old('group_id', $schedule->group_id) == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('group_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                @if ($series && $series->isNotEmpty())
                    <div class="col-md-6">
                        <label class="form-label">Series (Optional)</label>
                        <select name="series_id" class="form-select select2">
                            <option value="">None</option>
                            @foreach ($series as $s)
                                <option value="{{ $s->id }}" @selected(old('series_id', $schedule->series_id) == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control"
                        value="{{ old('location', $schedule->location) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Start At</label>
                    <input type="datetime-local" name="start_at" class="form-control"
                        value="{{ old('start_at', optional($schedule->start_at)->format('Y-m-d\TH:i')) }}" required>
                    @error('start_at')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">End At</label>
                    <input type="datetime-local" name="end_at" class="form-control"
                        value="{{ old('end_at', optional($schedule->end_at)->format('Y-m-d\TH:i')) }}" required>
                    @error('end_at')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="is_online" name="is_online" value="1"
                            {{ old('is_online', $schedule->is_online) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_online">Online Class Session</label>
                    </div>
                </div>

                <div class="col-md-6" id="access-code-div" style="display: none;">
                    <label class="form-label">Access Code (OTP)</label>
                    <input type="text" name="access_code" class="form-control"
                        value="{{ old('access_code', $schedule->access_code) }}">
                </div>

                <div class="col-md-12">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="requires_clock_out" name="requires_clock_out"
                            value="1" {{ old('requires_clock_out', $schedule->requires_clock_out) ? 'checked' : '' }}>
                        <label class="form-check-label" for="requires_clock_out">Requires Attendance Clock-Out?</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">Update Schedule</button>
                <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Attendance Controls -->
    <div class="card mt-4 p-4">
        <h5 class="mb-4">Attendance Status</h5>
        <div
            class="alert alert-{{ $schedule->attendance_status == 'open' ? 'success' : ($schedule->attendance_status == 'closed' ? 'danger' : 'secondary') }}">
            Current Status: <strong>{{ ucfirst($schedule->attendance_status) }}</strong>
            @if ($schedule->attendance_open_at)
                <br><small>Opened at: {{ $schedule->attendance_open_at->format('d M H:i') }}</small>
            @endif
        </div>

        @if ($schedule->attendance_status === 'scheduled' || $schedule->attendance_status === 'closed')
            <form action="{{ route('lecturer.schedules.status', $schedule->id) }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_status" value="open">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Mark Late After (Minutes from now)</label>
                        <input type="number" name="late_at_minutes" class="form-control" value="15" min="0">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100">Open Attendance</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($schedule->attendance_status === 'open')
            <div class="d-flex gap-2">
                <form action="{{ route('lecturer.schedules.status', $schedule->id) }}" method="POST" class="w-50">
                    @csrf
                    <input type="hidden" name="attendance_status" value="late">
                    <button class="btn btn-warning w-100">Force Late</button>
                </form>
                <form action="{{ route('lecturer.schedules.status', $schedule->id) }}" method="POST" class="w-50">
                    @csrf
                    <input type="hidden" name="attendance_status" value="closed">
                    <button class="btn btn-danger w-100">Close Attendance</button>
                </form>
            </div>
        @endif

        @if ($schedule->attendance_status === 'late')
            <form action="{{ route('lecturer.schedules.status', $schedule->id) }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_status" value="closed">
                <button class="btn btn-danger w-100">Close Attendance</button>
            </form>
        @endif
    </div>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/forms-selects.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const onlineSwitch = document.getElementById('is_online');
            const codeDiv = document.getElementById('access-code-div');

            function toggleCode() {
                if (onlineSwitch.checked) {
                    codeDiv.style.display = 'block';
                } else {
                    codeDiv.style.display = 'none';
                }
            }
            if (onlineSwitch) {
                onlineSwitch.addEventListener('change', toggleCode);
                toggleCode();
            }
        });
    </script>
@endsection
