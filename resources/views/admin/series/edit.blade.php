@extends('layouts/layoutMaster')

@section('title', 'Edit Schedule Series')

@section('content')
    <h4 class="mb-4">Edit Schedule Series</h4>
    <div class="row">
        <!-- Main Column -->
        <div class="col-md-12">
            <form method="POST" action="{{ route('admin.series.update', $series) }}">
                @csrf
                @method('PUT')

                <!-- Section 1: Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $series->name) }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Academic Semester</label>
                            <select name="academic_semester_id" class="form-select select2" required>
                                @foreach ($semesters as $sem)
                                    <option value="{{ $sem->id }}" @selected(old('academic_semester_id', $series->academic_semester_id) == $sem->id)>
                                        {{ $sem->year }} - {{ $sem->semester }}
                                        ({{ $sem->is_active ? 'Active' : 'Inactive' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_semester_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <select id="series-edit-course" name="course_id" class="form-select select2" required>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->id }}"
                                            data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}"
                                            @selected(old('course_id', $series->course_id) == $course->id)>{{ $course->name }}</option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group</label>
                                <select name="group_id" class="form-select select2" required>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}" @selected(old('group_id', $series->group_id) == $group->id)>
                                            {{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Schedule Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Schedule Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', $series->start_date ? $series->start_date->format('Y-m-d') : '') }}"
                                    required>
                                @error('start_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ old('end_date', $series->end_date ? $series->end_date->format('Y-m-d') : '') }}"
                                    required>
                                @error('end_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control"
                                    value="{{ old('start_time', $series->start_time ? $series->start_time->format('H:i') : '') }}"
                                    required>
                                @error('start_time')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control"
                                    value="{{ old('end_time', $series->end_time ? $series->end_time->format('H:i') : '') }}"
                                    required>
                                @error('end_time')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Days of Week</label>
                            @php
                                $selectedDays = collect(old('days_of_week', $series->days_of_week ?? []))->map(
                                    function ($d) {
                                        return strtolower(trim($d));
                                    },
                                );
                                $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                                $selectedLabel = $selectedDays->isEmpty()
                                    ? 'Select days'
                                    : $selectedDays->map(fn($d) => strtoupper($d))->implode(', ');
                            @endphp
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                    {{ $selectedLabel }}
                                </button>
                                <div class="dropdown-menu w-100 p-3">
                                    <div class="row">
                                        @foreach ($allDays as $day)
                                            <div class="col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="days_of_week[]"
                                                        value="{{ $day }}" id="day-{{ $day }}"
                                                        @checked($selectedDays->contains($day))>
                                                    <label class="form-check-label"
                                                        for="day-{{ $day }}">{{ strtoupper($day) }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('days_of_week')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_recurring" value="1"
                                id="is_recurring" @checked(old('is_recurring', $series->is_recurring))>
                            <label class="form-check-label" for="is_recurring">Recurring Schedule</label>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Location & Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Location & Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                @include('components.venue-dropdown', [
                                    'selectedVenue' => $series->venue_id,
                                ])
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_online"
                                        name="is_online" value="1"
                                        {{ old('is_online', $series->is_online) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_online">Online Class</label>
                                </div>
                                <div id="access-code-div" style="display: none;" class="mb-3">
                                    <label class="form-label">Access Code <small
                                            class="text-muted">(Auto-generated)</small></label>
                                    <input type="text" name="access_code" class="form-control"
                                        value="{{ old('access_code', $series->access_code) }}" placeholder="e.g. 1234">
                                    @error('access_code')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="requires_clock_out"
                                        value="1" id="requires_clock_out" @checked(old('requires_clock_out', $series->requires_clock_out))>
                                    <label class="form-check-label" for="requires_clock_out">Requires Attendance
                                        Clock-Out?</label>
                                    <small class="text-muted d-block">If unchecked, students only need to check in.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.series.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary">Update Series</button>
                </div>
            </form>
        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const onlineSwitch = document.getElementById('is_online');
        const codeDiv = document.getElementById('access-code-div');

        function toggleCode() {
            if (onlineSwitch.checked) {
                codeDiv.style.display = 'block';
                // Only auto-generate if empty
                const input = codeDiv.querySelector('input');
                if (!input.value) {
                    input.value = Math.floor(1000 + Math.random() * 9000);
                }
            } else {
                codeDiv.style.display = 'none';
            }
        }
        if (onlineSwitch) {
            onlineSwitch.addEventListener('change', toggleCode);
            toggleCode(); // Init state
        }
    });
</script>
