@extends('layouts/layoutMaster')

@section('title', 'Manual Attendance')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Manual Attendance Entry</h4>
        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Step 1: Select Schedule</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" value="{{ $date }}" class="form-control" id="attendanceDateInput" />
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Program</label>
                    <select id="filterProgram" class="form-select">
                        <option value="">All Programs</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Year of Study</label>
                    <select id="filterYear" class="form-select">
                        <option value="">All Years</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>Year
                                {{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Course</label>
                    <select id="filterCourse" class="form-select">
                        <option value="">All Courses</option>
                        @foreach ($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">Group</label>
                    <select id="filterGroup" class="form-select">
                        <option value="">All Groups</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Schedule</label>
                    <select id="scheduleSelect" class="form-select">
                        <option value="">Select a schedule...</option>
                        @foreach ($schedules as $s)
                            <option value="{{ $s->id }}" data-course="{{ $s->course_id }}"
                                data-group="{{ $s->group_id }}" data-start="{{ $s->start_at?->format('Y-m-d H:i') }}"
                                data-end="{{ $s->end_at?->format('Y-m-d H:i') }}">
                                {{ optional($s->course)->name }} — {{ optional($s->group)->name }} —
                                {{ $s->start_at->format('Y-m-d H:i') }} @ {{ $s->location }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Use filters above to narrow schedules. Students will load automatically when
                        you select a schedule.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Students Table (loads via AJAX) -->
    <div id="studentsSection" style="display: none;">
        <form method="POST" action="{{ route('admin.attendance.store') }}" id="attendanceForm">
            @csrf
            <input type="hidden" name="schedule_id" id="hiddenScheduleId" />
            <input type="hidden" name="marked_at" id="hiddenMarkedAt" />

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Step 2: Mark Attendance</h5>
                    <div id="scheduleInfo" class="text-muted small"></div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <label class="form-label mb-0">Marked At</label>
                            <input type="datetime-local" id="markedAtInput"
                                class="form-control form-control-sm d-inline-block" style="width: auto;" />
                        </div>
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" id="studentSearch" class="form-control" placeholder="Search student...">
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                                <i class="ti ti-checkbox me-1"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                <i class="ti ti-square me-1"></i> Deselect All
                            </button>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-success" onclick="markAllAs('present')">
                                <i class="ti ti-check me-1"></i> All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" onclick="markAllAs('late')">
                                <i class="ti ti-clock me-1"></i> All Late
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="markAllAs('absent')">
                                <i class="ti ti-x me-1"></i> All Absent
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="studentsTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Select</th>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Student No</th>
                                    <th>Reg No</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <!-- Populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div id="noStudentsMessage" class="alert alert-info" style="display: none;">
                        No students found in this group. Please select a different schedule.
                    </div>

                    <div id="loadingMessage" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading students...</p>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <span id="studentCount" class="text-muted"></span>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="ti ti-device-floppy me-1"></i> Save Attendance
                    </button>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('page-script')
    <script>
        (function() {
            const filterProgram = document.getElementById('filterProgram');
            const filterYear = document.getElementById('filterYear');
            const filterCourse = document.getElementById('filterCourse');
            const filterGroup = document.getElementById('filterGroup');
            const scheduleSelect = document.getElementById('scheduleSelect');
            const dateInput = document.getElementById('attendanceDateInput');
            const markedAtInput = document.getElementById('markedAtInput');
            const studentsSection = document.getElementById('studentsSection');
            const studentsTableBody = document.getElementById('studentsTableBody');
            const scheduleInfo = document.getElementById('scheduleInfo');
            const studentCount = document.getElementById('studentCount');
            const noStudentsMessage = document.getElementById('noStudentsMessage');
            const loadingMessage = document.getElementById('loadingMessage');
            const submitBtn = document.getElementById('submitBtn');
            const hiddenScheduleId = document.getElementById('hiddenScheduleId');
            const hiddenMarkedAt = document.getElementById('hiddenMarkedAt');

            // Set default marked_at to now
            markedAtInput.value = new Date().toISOString().slice(0, 16);

            // Build URL with current filters and redirect
            function reloadWithFilters() {
                var base = '{{ route('admin.attendance.create') }}';
                var url = new URL(base, window.location.origin);
                if (dateInput.value) url.searchParams.set('date', dateInput.value);
                if (filterProgram.value) url.searchParams.set('program_id', filterProgram.value);
                if (filterYear.value) url.searchParams.set('year', filterYear.value);
                if (filterCourse.value) url.searchParams.set('course_id', filterCourse.value);
                if (filterGroup.value) url.searchParams.set('group_id', filterGroup.value);
                window.location.href = url.toString();
            }

            // Apply client-side filters to schedule dropdown
            function applyScheduleFilters() {
                const c = filterCourse.value;
                const g = filterGroup.value;
                const date = dateInput.value;
                [...scheduleSelect.options].forEach(opt => {
                    if (!opt.value) return;
                    const matchesCourse = !c || (opt.getAttribute('data-course') === c);
                    const matchesGroup = !g || (opt.getAttribute('data-group') === g);
                    const start = opt.getAttribute('data-start') || '';
                    const matchesDate = !date || (start.startsWith(date));
                    opt.hidden = !(matchesCourse && matchesGroup && matchesDate);
                });
            }

            // Date changes -> reload page
            dateInput.addEventListener('change', reloadWithFilters);

            // Program changes -> reload to get filtered courses
            filterProgram.addEventListener('change', reloadWithFilters);

            // Year changes -> reload to get filtered courses
            filterYear.addEventListener('change', reloadWithFilters);

            // Course/Group changes -> apply client-side filter
            filterCourse.addEventListener('change', applyScheduleFilters);
            filterGroup.addEventListener('change', applyScheduleFilters);

            // Load students when schedule is selected
            scheduleSelect.addEventListener('change', function() {
                const scheduleId = this.value;
                if (!scheduleId) {
                    studentsSection.style.display = 'none';
                    return;
                }

                // Set schedule start time as marked_at
                const opt = scheduleSelect.selectedOptions[0];
                const start = opt?.getAttribute('data-start');
                if (start) {
                    markedAtInput.value = start.replace(' ', 'T');
                }

                // Show loading
                studentsSection.style.display = 'block';
                loadingMessage.style.display = 'block';
                studentsTableBody.innerHTML = '';
                noStudentsMessage.style.display = 'none';
                submitBtn.disabled = true;

                // Fetch students via AJAX
                fetch('{{ route('admin.attendance.students') }}?schedule_id=' + scheduleId)
                    .then(res => res.json())
                    .then(data => {
                        loadingMessage.style.display = 'none';
                        hiddenScheduleId.value = data.schedule.id;

                        // Update schedule info
                        scheduleInfo.textContent =
                            `${data.schedule.course} — ${data.schedule.group} — ${data.schedule.start_at}`;

                        // Populate table
                        if (data.students.length === 0) {
                            studentsTableBody.innerHTML = '';
                            noStudentsMessage.style.display = 'block';
                            submitBtn.disabled = true;
                            studentCount.textContent = '0 students';
                            return;
                        }

                        noStudentsMessage.style.display = 'none';
                        submitBtn.disabled = false;

                        let html = '';
                        data.students.forEach((student, index) => {
                            const existingBadge = student.has_existing ?
                                '<span class="badge bg-label-info ms-2">Existing</span>' :
                                '';

                            // Determine if row should be selected (checked) by default
                            // Logic: If existing record, definitely check. If no existing record, also check (default is to mark all).
                            // User request implies they want to be able to NOT mark everyone.
                            // Let's check everyone by default, but allow unchecking.
                            const isChecked = true;

                            html += `
            <tr class="student-row">
              <td>
                <div class="form-check">
                  <input class="form-check-input student-checkbox" type="checkbox" value="${student.id}" id="check_${student.id}" ${isChecked ? 'checked' : ''}>
                </div>
              </td>
              <td>${index + 1}</td>
              <td><label class="form-check-label mb-0" for="check_${student.id}">${student.name}</label>${existingBadge}</td>
              <td>${student.student_no || '—'}</td>
              <td>${student.reg_no || '—'}</td>
              <td>
                <select name="statuses[${student.id}]" class="form-select form-select-sm status-select" style="width: 120px;">
                  <option value="present" ${student.status === 'present' ? 'selected' : ''}>Present</option>
                  <option value="late" ${student.status === 'late' ? 'selected' : ''}>Late</option>
                  <option value="absent" ${student.status === 'absent' ? 'selected' : ''}>Absent</option>
                </select>
              </td>
            </tr>
          `;
                        });
                        studentsTableBody.innerHTML = html;
                        studentCount.textContent = data.students.length + ' student' + (data.students
                            .length !== 1 ? 's' : '');
                    })
                    .catch(err => {
                        console.error('Failed to load students:', err);
                        loadingMessage.style.display = 'none';
                        noStudentsMessage.style.display = 'block';
                        noStudentsMessage.textContent = 'Failed to load students. Please try again.';
                    });
            });

            // Handle Form Submission - Disable unchecked inputs
            document.getElementById('attendanceForm').addEventListener('submit', function(e) {
                hiddenMarkedAt.value = markedAtInput.value;

                // Disable inputs for unchecked rows so they aren't submitted
                const rows = studentsTableBody.querySelectorAll('tr');
                let selectedCount = 0;

                rows.forEach(row => {
                    const checkbox = row.querySelector('.student-checkbox');
                    const select = row.querySelector('.status-select');

                    if (checkbox && !checkbox.checked) {
                        select.disabled = true;
                    } else if (checkbox && checkbox.checked) {
                        select.disabled = false;
                        selectedCount++;
                    }
                });

                if (selectedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one student to mark.');
                    // Re-enable everything to avoid UI confusion if they cancel
                    rows.forEach(row => {
                        const select = row.querySelector('.status-select');
                        if (select) select.disabled = false;
                    });
                }
            });

            // Select/Deselect All Buttons
            document.getElementById('selectAllBtn').addEventListener('click', function() {
                toggleAllCheckboxes(true);
            });
            document.getElementById('deselectAllBtn').addEventListener('click', function() {
                toggleAllCheckboxes(false);
            });

            function toggleAllCheckboxes(checked) {
                const rows = studentsTableBody.getElementsByTagName('tr');
                Array.from(rows).forEach(row => {
                    // Only toggle visible rows (respect functionality with search)
                    if (row.style.display !== 'none') {
                        const checkbox = row.querySelector('.student-checkbox');
                        if (checkbox) checkbox.checked = checked;
                    }
                });
            }

            // Student Search
            const studentSearch = document.getElementById('studentSearch');
            studentSearch.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const rows = studentsTableBody.getElementsByTagName('tr');
                Array.from(rows).forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                    if (row.style.display === 'none') {
                        // Optional: Uncheck hidden rows? No, maybe they searched to check specific ones.
                        // Let's keep state but only allow 'Select All' to affect visible ones.
                    }
                });
            });

            // Mark All As buttons
            window.markAllAs = function(status) {
                document.querySelectorAll('.status-select').forEach(select => {
                    select.value = status;
                });
            };

            // Update hidden marked_at before submit
            document.getElementById('attendanceForm').addEventListener('submit', function(e) {
                hiddenMarkedAt.value = markedAtInput.value;
            });

            // Initial filters
            applyScheduleFilters();

            // Auto-select first schedule if only one exists and no schedule selected
            if (scheduleSelect.options.length === 2) {
                const firstOpt = scheduleSelect.options[1];
                if (firstOpt && !firstOpt.hidden) {
                    scheduleSelect.value = firstOpt.value;
                    scheduleSelect.dispatchEvent(new Event('change'));
                }
            }
        })();
    </script>
@endsection
