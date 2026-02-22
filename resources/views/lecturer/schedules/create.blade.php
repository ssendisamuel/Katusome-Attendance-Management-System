@extends('layouts/layoutMaster')

@section('title', 'Add Schedule')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Add Schedule</h4>
        <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card p-4">
        <form action="{{ route('lecturer.schedules.store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Academic Semester</label>
                    <select name="academic_semester_id" class="form-select select2">
                        <option value="">Current Active Semester</option>
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem->id }}" @selected(old('academic_semester_id') == $sem->id)>
                                {{ $sem->year }} - {{ $sem->semester }} ({{ $sem->is_active ? 'Active' : 'Inactive' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Course (Restricted to Assigned) -->
                <div class="col-md-6">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-select select2" required>
                        <option value="">Select Course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Group -->
                <div class="col-md-6">
                    <label class="form-label">Group</label>
                    <select name="group_id" class="form-select select2" required>
                        <option value="">Select Group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('group_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Series (Optional) -->
                @if ($series && $series->isNotEmpty())
                    <div class="col-md-6">
                        <label class="form-label">Series (Optional)</label>
                        <select name="series_id" class="form-select select2">
                            <option value="">None</option>
                            @foreach ($series as $s)
                                <option value="{{ $s->id }}" {{ old('series_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location') }}"
                        placeholder="e.g., Room A1">
                </div>

                <!-- Timing -->
                <div class="col-md-6">
                    <label class="form-label">Start At</label>
                    <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at') }}"
                        required>
                    @error('start_at')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">End At</label>
                    <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at') }}" required>
                    @error('end_at')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Online & Settings -->
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="is_online" name="is_online" value="1"
                            {{ old('is_online') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_online">Online Class Session</label>
                    </div>
                </div>

                <div class="col-md-6" id="access-code-div" style="display: none;">
                    <label class="form-label">Access Code (OTP)</label>
                    <input type="text" name="access_code" class="form-control" value="{{ old('access_code') }}"
                        placeholder="e.g., 1234">
                </div>

                <div class="col-md-12">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="requires_clock_out" name="requires_clock_out"
                            value="1" {{ old('requires_clock_out') ? 'checked' : '' }}>
                        <label class="form-check-label" for="requires_clock_out">Requires Attendance Clock-Out?</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">Save Schedule</button>
                <a href="{{ route('lecturer.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
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
                    const input = codeDiv.querySelector('input');
                    if (!input.value) input.value = Math.floor(1000 + Math.random() * 9000);
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
