@extends('layouts/layoutMaster')

@section('title', 'Add Schedule Series')

@section('content')
    <h4 class="mb-4">Add Schedule Series</h4>
    <div class="row">
        <!-- Main Column -->
        <div class="col-md-12">
            <form method="POST" action="{{ route('admin.series.store') }}">
                @csrf

                <!-- Section 1: Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Auto-generated Name Field -->
                        <div class="mb-3">
                            <label class="form-label">Name <small class="text-muted">(Auto-generated)</small></label>
                            <div class="input-group">
                                <input type="text" id="series-name" name="name" class="form-control"
                                    value="{{ old('name') }}" placeholder="Auto-generated based on selection" required>
                                <button type="button" class="btn btn-outline-secondary" id="btn-regenerate-name"
                                    title="Regenerate Name"><i class="ri-refresh-line"></i></button>
                            </div>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Program</label>
                                <select id="series-program" name="program" class="form-select select2"
                                    data-placeholder="Select Program" required>
                                    <option value="">Select Program</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" data-code="{{ $program->code }}">
                                            {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course</label>
                                <select id="series-create-course" name="course_id" class="form-select select2"
                                    data-placeholder="Select Course" required disabled>
                                    <option value="">Select Program First</option>
                                </select>
                                @error('course_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Group</label>
                                <select id="series-group" name="group_id" class="form-select select2"
                                    data-placeholder="Select Group" required>
                                    <option value="">Select Group</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}" data-name="{{ $group->name }}"
                                            @selected(old('group_id') == $group->id)>
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
                                    value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}"
                                    required>
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
                                $selectedDays = collect(old('days_of_week', []))->map(function ($d) {
                                    return strtolower(trim($d));
                                });
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
                                                        value="{{ $day }}" id="create-day-{{ $day }}"
                                                        @checked($selectedDays->contains($day))>
                                                    <label class="form-check-label"
                                                        for="create-day-{{ $day }}">{{ strtoupper($day) }}</label>
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
                                id="is_recurring" @checked(old('is_recurring'))>
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
                                @include('components.venue-dropdown')
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_online"
                                        name="is_online" value="1" {{ old('is_online') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_online">Online Class</label>
                                </div>
                                <div id="access-code-div" style="display: none;" class="mb-3">
                                    <label class="form-label">Access Code <small
                                            class="text-muted">(Auto-generated)</small></label>
                                    <input type="text" name="access_code" class="form-control"
                                        value="{{ old('access_code') }}" placeholder="e.g. 1234">
                                    @error('access_code')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_clock_out"
                                        value="1" id="requires_clock_out" @checked(old('requires_clock_out'))>
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
                    <button class="btn btn-primary">Create Series</button>
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
            const courseSelect = $('#series-create-course');
            const groupSelect = $('#series-group');
            const startTimeInput = document.getElementById('series-start-time');
            const endTimeInput = document.getElementById('series-end-time');
            const nameInput = document.getElementById('series-name');
            const refreshBtn = document.getElementById('btn-regenerate-name');

            // Roman numeral helper for Year
            function toRoman(num) {
                if (!num) return '';
                const roman = {
                    M: 1000,
                    CM: 900,
                    D: 500,
                    CD: 400,
                    C: 100,
                    XC: 90,
                    L: 50,
                    XL: 40,
                    X: 10,
                    IX: 9,
                    V: 5,
                    IV: 4,
                    I: 1
                };
                let str = '';
                for (let i of Object.keys(roman)) {
                    let q = Math.floor(num / roman[i]);
                    num -= q * roman[i];
                    str += i.repeat(q);
                }
                return str;
            }

            // Generate Name logic
            function generateName() {
                // Format: ProgramCode YearRoman - Group : CourseCode (Start - End)
                // Get Program Code from data attribute of selected option
                const programData = programSelect.select2('data')[0];
                const programCode = programData ? $(programData.element).data('code') : '';

                const groupData = groupSelect.select2('data')[0];
                const groupName = groupData ? $(groupData.element).data('name') : '';

                const courseData = courseSelect.select2('data')[0];
                const courseCode = courseData ? $(courseData.element).data('code') : '';
                const year = courseData ? $(courseData.element).data('year') : '';
                const yearRoman = year ? toRoman(year) : '';

                const start = startTimeInput.value;
                const end = endTimeInput.value;

                // format time to AM/PM if possible, or simple H:i
                const formatTime = (t) => {
                    if (!t) return '';
                    let [h, m] = t.split(':');
                    let ampm = h >= 12 ? 'PM' : 'AM';
                    h = h % 12;
                    h = h ? h : 12;
                    return `${h}:${m}${ampm}`;
                };

                const timeRange = (start && end) ? `(${formatTime(start)} - ${formatTime(end)})` : '';

                const parts = [];
                if (programCode) parts.push(programCode);
                if (yearRoman) parts.push(yearRoman);

                let leftSide = parts.join(' '); // BBC II

                let fullString = leftSide;
                if (groupName) fullString += ` - ${groupName}`; // BBC II - A
                if (courseCode) fullString += ` : ${courseCode}`; // BBC II - A : WSA
                if (timeRange) fullString += ` ${timeRange}`; // BBC II - A : WSA (8:00AM - 10:00AM)

                if (fullString.trim().length > 3) { // minimal check
                    nameInput.value = fullString;
                }
            }

            // Event Listeners for Name Generation
            [startTimeInput, endTimeInput].forEach(el => el.addEventListener('change', generateName));
            groupSelect.on('change', generateName);
            courseSelect.on('change', generateName);
            programSelect.on('change', generateName); // also triggers fetch
            refreshBtn.addEventListener('click', generateName);


            // Program Change: Fetch Courses
            programSelect.on('change', function() {
                const programId = $(this).val();

                // clear course select
                courseSelect.empty().append('<option value="">Select Course</option>').prop('disabled',
                    true);

                if (!programId) return;

                const url = "{{ route('admin.series.program-details', ':id') }}".replace(':id', programId);

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        // data.courses is array of {id, code, name, year_of_study}
                        if (data.courses && data.courses.length) {
                            data.courses.forEach(c => {
                                const option = new Option(`${c.code} - ${c.name}`, c.id, false,
                                    false);
                                // Store data for name generation
                                $(option).attr('data-code', c.code);
                                $(option).attr('data-year', c.year_of_study);
                                courseSelect.append(option);
                            });
                            courseSelect.prop('disabled', false);
                            // Re-trigger select2 update if needed (forms-selects.js usually handles basic binding, but appending options manually requires notification)
                            courseSelect.trigger('change');
                        } else {
                            courseSelect.append(
                                '<option value="">No courses found for this program</option>');
                        }
                    })
                    .catch(err => console.error(err));
            });

        });
    </script>
@endsection
