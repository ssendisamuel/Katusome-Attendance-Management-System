@extends('layouts/layoutMaster')

@section('title', 'Add Schedule Series')

@section('content')
    <h4 class="mb-4">Add Schedule Series</h4>
    <div class="card p-4">
        <form method="POST" action="{{ route('lecturer.series.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Name <small class="text-muted">(Auto-generated or Custom)</small></label>
                <div class="input-group">
                    <input type="text" id="series-name" name="name" class="form-control" value="{{ old('name') }}"
                        placeholder="e.g. CLAW 1101 - Group A (Mon 8-10)" required>
                    <button type="button" class="btn btn-outline-secondary" id="btn-regenerate-name"
                        title="Regenerate Name">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
                @error('name')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Academic Semester</label>
                <select name="academic_semester_id" class="form-select select2">
                    <option value="">Current Active Semester</option>
                    @foreach ($semesters as $sem)
                        <option value="{{ $sem->id }}" @selected(old('academic_semester_id', optional($activeSemester)->id) == $sem->id)>
                            {{ $sem->year }} - {{ $sem->semester }} ({{ $sem->is_active ? 'Active' : 'Inactive' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course</label>
                    <select id="series-course" name="course_id" class="form-select select2" required>
                        <option value="">Select Course</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" data-code="{{ $course->code }}"
                                data-name="{{ $course->name }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Group</label>
                    <select id="series-group" name="group_id" class="form-select select2" required>
                        <option value="">Select Group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" data-name="{{ $group->name }}"
                                {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('group_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                    @error('start_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                    @error('end_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" id="series-start-time" name="start_time" class="form-control"
                        value="{{ old('start_time') }}" required>
                    @error('start_time')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Time</label>
                    <input type="time" id="series-end-time" name="end_time" class="form-control"
                        value="{{ old('end_time') }}" required>
                    @error('end_time')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Days of Week</label>
                @php
                    $selectedDays = collect(old('days_of_week', []))->map(fn($d) => strtolower(trim($d)));
                    $allDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
                    $selectedLabel = $selectedDays->isEmpty()
                        ? 'Select days'
                        : $selectedDays->map(fn($d) => strtoupper($d))->implode(', ');
                @endphp
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside">
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

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="is_recurring"
                    @checked(old('is_recurring', true))>
                <label class="form-check-label" for="is_recurring">Recurring</label>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location') }}"
                        placeholder="e.g. Block B, Room 3">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_online" name="is_online"
                            value="1" {{ old('is_online') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_online">Online Class</label>
                    </div>
                    <div id="access-code-div" style="display: none;">
                        <input type="text" name="access_code" class="form-control" value="{{ old('access_code') }}"
                            placeholder="Access Code">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary">Save Series</button>
            <a href="{{ route('lecturer.series.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
            const courseSelect = $('#series-course');
            const groupSelect = $('#series-group');
            const startTimeInput = document.getElementById('series-start-time');
            const endTimeInput = document.getElementById('series-end-time');
            const nameInput = document.getElementById('series-name');
            const refreshBtn = document.getElementById('btn-regenerate-name');

            function generateName() {
                const courseData = courseSelect.select2('data')[0];
                const courseCode = courseData ? $(courseData.element).data('code') : '';

                const groupData = groupSelect.select2('data')[0];
                const groupName = groupData ? $(groupData.element).data('name') : '';

                const start = startTimeInput.value;
                const end = endTimeInput.value;

                const formatTime = (t) => {
                    if (!t) return '';
                    let [h, m] = t.split(':');
                    h = parseInt(h);
                    let ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12;
                    h = h ? h : 12;
                    return `${h}:${m}${ampm}`;
                };

                const timeRange = (start && end) ? `(${formatTime(start)} - ${formatTime(end)})` : '';

                let FullName = '';
                if (courseCode) FullName += courseCode;
                if (groupName) FullName += ` - ${groupName}`;
                if (timeRange) FullName += ` ${timeRange}`;

                if (FullName.length > 5) {
                    nameInput.value = FullName;
                }
            }

            [startTimeInput, endTimeInput].forEach(el => el.addEventListener('change', generateName));
            groupSelect.on('change', generateName);
            courseSelect.on('change', generateName);
            refreshBtn.addEventListener('click', generateName);

            // Online Toggle
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
