@extends('layouts/layoutMaster')

@section('title', 'Add Schedule')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Add Schedule</h4>
            <p class="mb-0 text-muted">Create a single class schedule.</p>
        </div>
        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <form action="{{ route('admin.schedules.store') }}" method="POST">
        @csrf
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
                                    <option value="">Select Semester</option>
                                    @foreach ($semesters as $sem)
                                        <option value="{{ $sem->id }}" @selected(old('academic_semester_id', optional($activeSemester)->id) == $sem->id)>
                                            {{ $sem->year }} - {{ $sem->semester }}
                                            ({{ $sem->is_active ? 'Active' : 'Inactive' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_semester_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Program -->
                            <div class="col-md-6">
                                <label class="form-label">Program</label>
                                <select id="schedule-program" name="program" class="form-select select2"
                                    data-placeholder="Select Program" required>
                                    <option value="">Select Program</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year of Study -->
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <select id="schedule-course" name="course_id" class="form-select select2"
                                    data-placeholder="Select Course" required disabled>
                                    <option value="">Select Program First</option>
                                </select>
                                @error('course_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Group -->
                            <div class="col-md-6">
                                <label class="form-label">Group</label>
                                <select name="group_id" class="form-select select2" data-placeholder="Select Group"
                                    required>
                                    <option value="">Select Group</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}"
                                            {{ old('group_id') == $group->id ? 'selected' : '' }}>
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
                                    value="{{ old('start_at') }}" required>
                                @error('start_at')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End At</label>
                                <input type="datetime-local" name="end_at" class="form-control"
                                    value="{{ old('end_at') }}" required>
                                @error('end_at')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                @include('components.venue-dropdown')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings -->
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
                                    value="1" {{ old('is_online') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_online">Online Session</label>
                            </div>
                            <small class="text-muted d-block mt-1">If enabled, students can check in remotely.</small>
                        </div>

                        <!-- Access Code (conditionally shown) -->
                        <div class="mb-3" id="access-code-div" style="display: none;">
                            <label class="form-label">Access Code (OTP)</label>
                            <input type="text" name="access_code" class="form-control" value="{{ old('access_code') }}"
                                placeholder="e.g., 1234" maxlength="10">
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
                                    {{ old('requires_clock_out') ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_clock_out">Require Clock-Out</label>
                            </div>
                            <small class="text-muted d-block mt-1">Marks as incomplete/late if not clocked out.</small>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary w-100 mb-2">Create Schedule</button>
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
            const courseSelect = $('#schedule-course');
            const yearSelect = $('#schedule-year');

            let allCourses = []; // Store fetched courses locally

            // Helper to render options based on current filter
            function renderCourses() {
                const selectedYear = yearSelect.val();

                courseSelect.empty().append('<option value="">Select Course</option>');

                let filtered = allCourses;
                if (selectedYear && selectedYear !== 'all') {
                    filtered = allCourses.filter(c => c.year_of_study == selectedYear);
                }

                if (filtered.length) {
                    filtered.forEach(c => {
                        const yearLabel = c.year_of_study ? ` (Yr ${c.year_of_study})` : '';
                        const option = new Option(`${c.code} - ${c.name}${yearLabel}`, c.id, false, false);
                        courseSelect.append(option);
                    });
                    courseSelect.prop('disabled', false);
                } else {
                    courseSelect.append('<option value="">No courses found for this year</option>');
                    courseSelect.prop('disabled', true); // Or keep active but empty? Better to disable if empty.
                }
                // Re-init select2 to refresh options if needed, or just trigger change
                // courseSelect.trigger('change');
            }

            // Program Change: Fetch Courses
            programSelect.on('change', function() {
                const programId = $(this).val();

                // Reset state
                courseSelect.empty().append('<option value="">Select Course</option>').prop('disabled',
                    true);
                yearSelect.prop('disabled', true).val('all').trigger('change.select2'); // Reset year filter
                allCourses = [];

                if (!programId) return;

                const url = "{{ route('admin.series.program-details', ':id') }}".replace(':id', programId);

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.courses && data.courses.length) {
                            allCourses = data.courses; // Cache them

                            // Enable filters
                            yearSelect.prop('disabled', false);

                            // Initial Render
                            renderCourses();
                        } else {
                            courseSelect.append('<option value="">No courses found</option>');
                        }
                    })
                    .catch(err => console.error(err));
            });

            // Year Change: Filter Courses
            yearSelect.on('change', function() {
                renderCourses();
            });

            // Online Toggle Logic
            const onlineSwitch = document.getElementById('is_online');
            const codeDiv = document.getElementById('access-code-div');

            function toggleCode() {
                if (onlineSwitch.checked) {
                    codeDiv.style.display = 'block';
                    // Auto-generate code if empty
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
                toggleCode(); // Init
            }
        });
    </script>
@endsection
