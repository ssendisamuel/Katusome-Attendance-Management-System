@extends('layouts/layoutMaster')

@section('title', 'Edit Schedule')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Edit Schedule</h4>
            <p class="mb-0 text-muted">Update schedule details and manage attendance status.</p>
        </div>
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Left Column: Primary Details -->
            <div class="col-md-8">
                <!-- Basic Information Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Academic Semester</label>
                                <select name="academic_semester_id" class="form-select select2" required>
                                    @foreach ($semesters as $sem)
                                        <option value="{{ $sem->id }}" @selected(old('academic_semester_id', $schedule->academic_semester_id) == $sem->id)>
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
                                $currentCourse = $schedule->course;
                                $currentProgram = null;
                                if ($currentCourse) {
                                    $tlRow = \Illuminate\Support\Facades\DB::table('course_lecturer')
                                        ->where('course_id', $currentCourse->id)
                                        ->first();
                                    if ($tlRow && $tlRow->program_code) {
                                        $currentProgram = \App\Models\Program::where(
                                            'code',
                                            $tlRow->program_code,
                                        )->first();
                                    }
                                }
                                $preselectedProgramId = old('program', $currentProgram ? $currentProgram->id : '');
                            @endphp

                            <!-- Program -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Program</label>
                                <select id="schedule-program" name="program" class="form-select select2"
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year of Study</label>
                                <select id="schedule-year" class="form-select select2" disabled>
                                    <option value="all">All Years</option>
                                    <option value="1">Year 1</option>
                                    <option value="2">Year 2</option>
                                    <option value="3">Year 3</option>
                                    <option value="4">Year 4</option>
                                    <option value="5">Year 5</option>
                                </select>
                            </div>

                            <!-- Course -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <select id="schedule-edit-course" name="course_id" class="form-select select2" required>
                                    <option value="{{ $schedule->course_id }}" selected>
                                        {{ optional($schedule->course)->name }}
                                    </option>
                                </select>
                                @error('course_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Group -->
                            <div class="col-md-6 mb-3">
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

                            <!-- Lecturer (auto-assigned from Teaching Load) -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Lecturer <small class="text-muted">(auto-assigned from Teaching
                                        Load)</small></label>
                                <input type="hidden" id="schedule-lecturer" name="lecturer_id"
                                    value="{{ old('lecturer_id', $schedule->lecturer_id) }}">
                                <div id="sched-lecturer-display" class="form-control bg-light" style="min-height:38px;">
                                    @if ($schedule->lecturer)
                                        {{ optional($schedule->lecturer)->title }}
                                        {{ optional($schedule->lecturer->user)->name }}
                                    @else
                                        No lecturer assigned
                                    @endif
                                </div>
                                <small class="text-muted" id="sched-lecturer-hint">Auto-assigned from teaching load.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timing & Location Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Timing & Location</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Start At</label>
                                <input type="datetime-local" name="start_at" class="form-control"
                                    value="{{ old('start_at', optional($schedule->start_at)->format('Y-m-d\TH:i')) }}"
                                    required>
                                @error('start_at')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End At</label>
                                <input type="datetime-local" name="end_at" class="form-control"
                                    value="{{ old('end_at', optional($schedule->end_at)->format('Y-m-d\TH:i')) }}"
                                    required>
                                @error('end_at')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                @include('components.venue-dropdown', [
                                    'selectedVenue' => $schedule->venue_id,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings & Actions -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Settings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Online Toggle -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_online" name="is_online"
                                    value="1" {{ old('is_online', $schedule->is_online) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_online">Online Session</label>
                            </div>
                            <small class="text-muted d-block mt-1">If enabled, students can check in remotely.</small>
                        </div>

                        <!-- Access Code (conditionally shown) -->
                        <div class="mb-3" id="access-code-div" style="display: none;">
                            <label class="form-label">Access Code (OTP)</label>
                            <input type="text" name="access_code" class="form-control"
                                value="{{ old('access_code', $schedule->access_code) }}" placeholder="e.g., 1234"
                                maxlength="10">
                            <small class="text-muted">Required for online check-in.</small>
                            @error('access_code')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Clock Out Toggle -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="requires_clock_out"
                                    name="requires_clock_out" value="1"
                                    {{ old('requires_clock_out', $schedule->requires_clock_out) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_clock_out">Require Clock-Out</label>
                            </div>
                            <small class="text-muted d-block mt-1">Marks as incomplete/late if not clocked out.</small>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary w-100 mb-2">Update Schedule</button>
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                    </div>
                </div>

                <!-- Status Controls Card -->
                <div class="card mb-4 border-info">
                    <div class="card-header bg-label-info">
                        <h5 class="card-title mb-0 text-info">Attendance Status</h5>
                    </div>
                    <div class="card-body mt-3">
                        <div
                            class="alert alert-{{ $schedule->attendance_status == 'open' ? 'success' : ($schedule->attendance_status == 'closed' ? 'danger' : 'secondary') }} mb-3">
                            Status: <strong>{{ ucfirst($schedule->attendance_status) }}</strong>
                            @if ($schedule->attendance_open_at)
                                <br><small>Opened:
                                    {{ $schedule->attendance_open_at->format('d M H:i') }}</small>
                            @endif
                        </div>
    </form> <!-- Close Main Form here to allow separate forms for status actions -->

    <!-- Status Actions (Separate Forms) -->
    @if ($schedule->attendance_status === 'scheduled' || $schedule->attendance_status === 'closed')
        <form action="{{ route('admin.schedules.status', $schedule->id) }}" method="POST">
            @csrf
            <input type="hidden" name="attendance_status" value="open">
            <div class="mb-2">
                <label class="form-label small">Mark Late After (Mins)</label>
                <input type="number" name="late_at_minutes" class="form-control form-control-sm" value="15"
                    min="0">
            </div>
            <button type="submit" class="btn btn-success w-100 btn-sm">
                <i class="ri-play-circle-line me-1"></i> Open Attendance
            </button>
        </form>
    @endif

    @if ($schedule->attendance_status === 'open')
        <div class="d-grid gap-2">
            <form action="{{ route('admin.schedules.status', $schedule->id) }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_status" value="late">
                <button class="btn btn-warning w-100 btn-sm">Force Late</button>
            </form>
            <form action="{{ route('admin.schedules.status', $schedule->id) }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_status" value="closed">
                <button class="btn btn-danger w-100 btn-sm">Close Attendance</button>
            </form>
        </div>
    @endif

    @if ($schedule->attendance_status === 'late')
        <form action="{{ route('admin.schedules.status', $schedule->id) }}" method="POST">
            @csrf
            <input type="hidden" name="attendance_status" value="closed">
            <button class="btn btn-danger w-100 btn-sm">Close Attendance</button>
        </form>
    @endif

    </div>
    </div>
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
            const programSelect = $('#schedule-program');
            const yearSelect = $('#schedule-year');
            const courseSelect = $('#schedule-edit-course');
            const preselectedCourseId = "{{ $schedule->course_id }}";
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
                const lecInput = document.getElementById('schedule-lecturer');
                const lecDisplay = document.getElementById('sched-lecturer-display');

                if (!courseId || !window._courseLecturers) return;

                const lecturers = window._courseLecturers[courseId] || [];
                if (lecturers.length) {
                    lecInput.value = lecturers[0].id;
                    const names = lecturers.map(l => (l.title ? l.title + ' ' : '') + l.name);
                    lecDisplay.innerHTML = '<strong>' + names[0] + '</strong>' +
                        (names.length > 1 ? ' <span class="text-muted">+ ' + (names.length - 1) +
                            ' more</span>' : '');
                    document.getElementById('sched-lecturer-hint').textContent = names.join(', ');
                }
            });

            // Online Toggle Logic
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
                toggleCode(); // Init
            }
        });
    </script>
@endsection
