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

                        @php
                            // Find program from teaching load (course_lecturer) instead of course_program
                            $currentCourse = $series->course;
                            $currentProgram = null;
                            if ($currentCourse) {
                                $tlRow = \Illuminate\Support\Facades\DB::table('course_lecturer')
                                    ->where('course_id', $currentCourse->id)
                                    ->first();
                                if ($tlRow && $tlRow->program_code) {
                                    $currentProgram = \App\Models\Program::where('code', $tlRow->program_code)->first();
                                }
                            }
                            $preselectedProgramId = old('program', $currentProgram ? $currentProgram->id : '');
                        @endphp

                        <div class="row">
                            <!-- Program -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Program</label>
                                <select id="series-program" name="program" class="form-select select2"
                                    data-placeholder="Select Program" required>
                                    <option value="">Select Program</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected($preselectedProgramId == $program->id)>
                                            {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year of Study -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Year of Study</label>
                                <select id="series-year" class="form-select select2" disabled>
                                    <option value="all">All Years</option>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                    <option value="5">Year 5</option>
                                </select>
                            </div>

                            <!-- Course -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Course</label>
                                <select id="series-edit-course" name="course_id" class="form-select select2" required>
                                    <option value="{{ $series->course_id }}" selected>
                                        {{ optional($series->course)->code }} - {{ optional($series->course)->name }}
                                    </option>
                                </select>
                                @error('course_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Group -->
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

                            <!-- Lecturer (auto-assigned from Teaching Load) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lecturer <small class="text-muted">(auto-assigned from Teaching
                                        Load)</small></label>
                                <input type="hidden" id="series-lecturer" name="lecturer_id"
                                    value="{{ old('lecturer_id', $series->lecturer_id) }}">
                                <div id="lecturer-display" class="form-control bg-light" style="min-height:38px;">
                                    @if ($series->lecturer)
                                        {{ optional($series->lecturer)->title }}
                                        {{ optional($series->lecturer->user)->name }}
                                    @else
                                        No lecturer assigned
                                    @endif
                                </div>
                                <small class="text-muted" id="lecturer-hint">Auto-assigned from teaching load.</small>
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
            const programSelect = $('#series-program');
            const yearSelect = $('#series-year');
            const courseSelect = $('#series-edit-course');
            const preselectedCourseId = "{{ $series->course_id }}";
            const preselectedProgramId = "{{ $preselectedProgramId }}";

            let allCourses = []; // Store fetched courses

            function renderCourses(keepSelected = false) {
                const selectedYear = yearSelect.val();
                let filteredCourses = allCourses;

                if (selectedYear !== 'all') {
                    filteredCourses = allCourses.filter(c => String(c.year_of_study) === String(selectedYear));
                }

                courseSelect.empty().append('<option value="">Select Course</option>');

                if (filteredCourses.length > 0) {
                    window._courseLecturers = {};
                    filteredCourses.forEach(c => {
                        window._courseLecturers[c.id] = c.lecturers || [];
                        const isSelected = keepSelected && (String(c.id) === String(preselectedCourseId));
                        const option = new Option(
                            `${c.code} - ${c.name} (Yr ${c.year_of_study})`, c.id,
                            isSelected, isSelected);
                        courseSelect.append(option);
                    });
                    courseSelect.prop('disabled', false);
                    if (keepSelected) {
                        courseSelect.trigger('change');
                    }
                } else {
                    courseSelect.append('<option value="">No courses match filters</option>');
                    courseSelect.prop('disabled', true);
                    courseSelect.trigger('change');
                }
            }

            function fetchCourses(programId, keepSelected = false) {
                if (!programId) {
                    allCourses = [];
                    yearSelect.prop('disabled', true);
                    courseSelect.empty().append('<option value="">Select Program First</option>').prop('disabled',
                        true);
                    return;
                }

                yearSelect.prop('disabled', false);

                const url = "{{ route('admin.series.program-details', ':id') }}".replace(':id', programId);
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.courses && data.courses.length) {
                            allCourses = data.courses;

                            // If we have a preselected course, auto-select the year if initially loading
                            if (keepSelected && preselectedCourseId) {
                                const matchedCourse = allCourses.find(c => String(c.id) === String(
                                    preselectedCourseId));
                                if (matchedCourse) {
                                    yearSelect.val(matchedCourse.year_of_study).trigger('change.select2');
                                }
                            }

                            renderCourses(keepSelected);
                        } else {
                            allCourses = [];
                            courseSelect.empty().append('<option value="">No courses found</option>');
                            courseSelect.prop('disabled', true);
                            courseSelect.trigger('change');
                        }
                    })
                    .catch(err => console.error(err));
            }

            // Initial load
            if (preselectedProgramId) {
                fetchCourses(preselectedProgramId, true);
            }

            // On Program Change
            programSelect.on('change', function() {
                const pid = $(this).val();
                fetchCourses(pid, false);
            });

            // On Year Change
            yearSelect.on('change', function() {
                renderCourses(false);
            });

            // Course Change: Auto-assign Lecturer from Teaching Load
            courseSelect.on('change', function() {
                const courseId = $(this).val();
                const lecInput = document.getElementById('series-lecturer');
                const lecDisplay = document.getElementById('lecturer-display');

                if (!courseId || !window._courseLecturers) return;

                const lecturers = window._courseLecturers[courseId] || [];
                if (lecturers.length) {
                    lecInput.value = lecturers[0].id;
                    const names = lecturers.map(l => (l.title ? l.title + ' ' : '') + l.name);
                    lecDisplay.innerHTML = '<strong>' + names[0] + '</strong>' +
                        (names.length > 1 ? ' <span class="text-muted">+ ' + (names.length - 1) +
                            ' more</span>' : '');
                    document.getElementById('lecturer-hint').textContent = names.join(', ');
                }
            });

            // Online Toggle
            const onlineSwitch = document.getElementById('is_online');
            const codeDiv = document.getElementById('access-code-div');

            function toggleCode() {
                if (onlineSwitch.checked) {
                    codeDiv.style.display = 'block';
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
@endsection
