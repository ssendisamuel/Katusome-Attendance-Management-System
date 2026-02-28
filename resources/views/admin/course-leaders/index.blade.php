@extends('layouts/layoutMaster')

@section('title', 'Manage Course Leaders')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Admin /</span> Course Leaders
            </h4>

            <div class="nav-align-top mb-4">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ request('tab') !== 'logs' ? 'active' : '' }}" role="tab"
                            data-bs-toggle="tab" data-bs-target="#tab-leaders" aria-controls="tab-leaders"
                            aria-selected="{{ request('tab') !== 'logs' ? 'true' : 'false' }}">Course Leaders</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link {{ request('tab') === 'logs' ? 'active' : '' }}"
                            role="tab" data-bs-toggle="tab" data-bs-target="#tab-logs" aria-controls="tab-logs"
                            aria-selected="{{ request('tab') === 'logs' ? 'true' : 'false' }}">Activity Logs</button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- ═══════════════════════════════════════════════ --}}
                    {{-- TAB 1: Course Leaders                          --}}
                    {{-- ═══════════════════════════════════════════════ --}}
                    <div class="tab-pane fade {{ request('tab') !== 'logs' ? 'show active' : '' }}" id="tab-leaders"
                        role="tabpanel">

                        {{-- Assign New Leader Form --}}
                        <div class="card mb-4">
                            <h5 class="card-header border-bottom">Assign Course Leader</h5>
                            <form action="{{ route('admin.course-leaders.store') }}" method="POST" class="card-body mt-3">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label" for="academic_semester_id">Academic Semester</label>
                                        <select name="academic_semester_id" id="academic_semester_id" class="form-select"
                                            required>
                                            <option value="">Select Semester</option>
                                            @foreach ($semesters as $semester)
                                                <option value="{{ $semester->id }}"
                                                    {{ old('academic_semester_id') == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="program_id">Program</label>
                                        <select name="program_id" id="program_id" class="form-select" required>
                                            <option value="">Select Program</option>
                                            @foreach ($programs as $program)
                                                <option value="{{ $program->id }}"
                                                    {{ old('program_id') == $program->id ? 'selected' : '' }}>
                                                    {{ $program->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="year_of_study">Year of Study</label>
                                        <select name="year_of_study" id="year_of_study" class="form-select" required>
                                            <option value="">Select Year</option>
                                            @foreach ([1, 2, 3, 4, 5] as $y)
                                                <option value="{{ $y }}"
                                                    {{ old('year_of_study') == $y ? 'selected' : '' }}>
                                                    Year {{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="group_id">Group</label>
                                        <select name="group_id" id="group_id" class="form-select" required>
                                            <option value="">Select Group</option>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->id }}"
                                                    {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                                    {{ $group->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-9">
                                        <label class="form-label" for="student_ids">Select Student(s)</label>
                                        <select name="student_ids[]" id="student_ids" class="form-select custom-select2"
                                            multiple required
                                            {{ old('academic_semester_id') && old('program_id') && old('year_of_study') && old('group_id') ? '' : 'disabled' }}
                                            data-placeholder="Search for enrolled students...">
                                            <option value=""></option>
                                            @if (old('student_ids') && is_array(old('student_ids')))
                                                @foreach (\App\Models\Student::with('user')->whereIn('id', old('student_ids'))->get() as $oldStudent)
                                                    <option value="{{ $oldStudent->id }}" selected>
                                                        {{ $oldStudent->user?->name ?? $oldStudent->name }}
                                                        ({{ $oldStudent->student_no }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="text-muted d-block mt-1">Select students enrolled in the chosen
                                            cohort to assign as Course Leaders.</small>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-center mt-4">
                                        <button type="submit" class="btn btn-primary w-100">Assign Leader(s)</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Dynamic Filter Bar --}}
                        <div class="card mb-4">
                            <div class="card-body py-3">
                                <form id="leaders-filter-form" method="GET"
                                    action="{{ route('admin.course-leaders.index') }}" class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Search name, email, admission..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="filter_semester_id" class="form-select js-auto-filter">
                                            <option value="">All Semesters</option>
                                            @foreach ($semesters as $semester)
                                                <option value="{{ $semester->id }}"
                                                    {{ request('filter_semester_id') == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="program_id" class="form-select js-auto-filter">
                                            <option value="">All Programs</option>
                                            @foreach ($programs as $program)
                                                <option value="{{ $program->id }}"
                                                    {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                                    {{ $program->code ?? $program->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="year_of_study" class="form-select js-auto-filter">
                                            <option value="">All Years</option>
                                            @foreach ([1, 2, 3, 4, 5] as $y)
                                                <option value="{{ $y }}"
                                                    {{ request('year_of_study') == $y ? 'selected' : '' }}>Year
                                                    {{ $y }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="group_id" class="form-select js-auto-filter">
                                            <option value="">All Groups</option>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->id }}"
                                                    {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <a href="{{ route('admin.course-leaders.index') }}"
                                            class="btn btn-icon btn-outline-secondary w-100" title="Reset filters">
                                            <i class="ri ri-refresh-line"></i>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Leaders Table --}}
                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Cohort</th>
                                            <th>Assigned At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @forelse($leaders as $leader)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-3">
                                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                                {{ substr($leader->student?->user?->name ?? $leader->student?->name, 0, 2) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 text-truncate">
                                                                {{ $leader->student?->user?->name ?? $leader->student?->name }}
                                                            </h6>
                                                            <small
                                                                class="text-truncate">{{ $leader->student->student_no }}
                                                                |
                                                                {{ $leader->student?->user?->email ?? $leader->student?->email }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-primary">{{ $leader->program->code ?? $leader->program->name }}
                                                        {{ $leader->year_of_study }}
                                                        {{ $leader->group->name }}</span>
                                                </td>
                                                <td>{{ $leader->created_at->format('M d, Y') }}</td>
                                                <td>
                                                    <form action="{{ route('admin.course-leaders.destroy', $leader) }}"
                                                        method="POST" class="d-inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-icon border-danger text-danger js-delete-leader"
                                                            data-name="{{ $leader->student->name }}">
                                                            <i class="ri ri-delete-bin-line"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">No course leaders
                                                    found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($leaders->hasPages())
                                <div class="card-footer pb-0 border-top">
                                    {{ $leaders->links() }}
                                </div>
                            @endif
                        </div>

                    </div>
                    {{-- END TAB 1 --}}

                    {{-- ═══════════════════════════════════════════════ --}}
                    {{-- TAB 2: Activity Logs                           --}}
                    {{-- ═══════════════════════════════════════════════ --}}
                    <div class="tab-pane fade {{ request('tab') === 'logs' ? 'show active' : '' }}" id="tab-logs"
                        role="tabpanel">

                        {{-- Dynamic Filter Bar --}}
                        <div class="card mb-4">
                            <div class="card-body py-3">
                                <form id="logs-filter-form" method="GET"
                                    action="{{ route('admin.course-leaders.index') }}" class="row g-3 align-items-end">
                                    <input type="hidden" name="tab" value="logs">
                                    <div class="col-md-3">
                                        <select name="log_semester_id" class="form-select js-auto-filter-logs">
                                            <option value="">All Semesters</option>
                                            @foreach ($semesters as $semester)
                                                <option value="{{ $semester->id }}"
                                                    {{ request('log_semester_id') == $semester->id ? 'selected' : '' }}>
                                                    {{ $semester->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="log_program_id" class="form-select js-auto-filter-logs">
                                            <option value="">All Programs</option>
                                            @foreach ($programs as $program)
                                                <option value="{{ $program->id }}"
                                                    {{ request('log_program_id') == $program->id ? 'selected' : '' }}>
                                                    {{ $program->code ?? $program->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="log_group_id" class="form-select js-auto-filter-logs">
                                            <option value="">All Groups</option>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->id }}"
                                                    {{ request('log_group_id') == $group->id ? 'selected' : '' }}>
                                                    {{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="log_action" class="form-select js-auto-filter-logs">
                                            <option value="">All Actions</option>
                                            <option value="status_updated"
                                                {{ request('log_action') == 'status_updated' ? 'selected' : '' }}>Status
                                                Updated</option>
                                            <option value="venue_updated"
                                                {{ request('log_action') == 'venue_updated' ? 'selected' : '' }}>Venue
                                                Updated</option>
                                            <option value="mode_updated"
                                                {{ request('log_action') == 'mode_updated' ? 'selected' : '' }}>Mode
                                                Updated</option>
                                            <option value="actuals_logged"
                                                {{ request('log_action') == 'actuals_logged' ? 'selected' : '' }}>Actuals
                                                Logged</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1 small">From</label>
                                        <input type="date" name="log_date_from"
                                            class="form-control js-auto-filter-logs"
                                            value="{{ request('log_date_from') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1 small">To</label>
                                        <input type="date" name="log_date_to" class="form-control js-auto-filter-logs"
                                            value="{{ request('log_date_to') }}">
                                    </div>
                                    <div class="col-md-1">
                                        <a href="{{ route('admin.course-leaders.index', ['tab' => 'logs']) }}"
                                            class="btn btn-icon btn-outline-secondary w-100" title="Reset filters">
                                            <i class="ri ri-refresh-line"></i>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Logs Table --}}
                        <div class="card">
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Course Leader</th>
                                            <th>Schedule</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @forelse($logs as $log)
                                            <tr>
                                                <td>
                                                    <span
                                                        class="fw-medium">{{ $log->created_at->format('M d, Y') }}</span>
                                                    <br>
                                                    <small
                                                        class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-3">
                                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                                {{ substr($log->student?->user?->name ?? $log->student?->name, 0, 2) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 text-truncate">
                                                                {{ $log->student?->user?->name ?? $log->student?->name }}
                                                            </h6>
                                                            <small
                                                                class="text-truncate">{{ $log->student?->student_no }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-medium">{{ $log->schedule?->course?->code ?? 'N/A' }}</span>
                                                        <small class="text-muted">{{ $log->schedule?->group?->name }}
                                                            &middot;
                                                            {{ $log->schedule?->academicSemester?->display_name }}</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($log->action === 'status_updated')
                                                        <span class="badge bg-label-danger">Status Updated</span>
                                                    @elseif($log->action === 'venue_updated')
                                                        <span class="badge bg-label-info">Venue Updated</span>
                                                    @elseif($log->action === 'mode_updated')
                                                        <span class="badge bg-label-warning">Mode Updated</span>
                                                    @elseif($log->action === 'actuals_logged')
                                                        <span class="badge bg-label-success">Actuals Logged</span>
                                                    @else
                                                        <span
                                                            class="badge bg-label-secondary">{{ Str::title(str_replace('_', ' ', $log->action)) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($log->action === 'status_updated')
                                                        <small>Status set to
                                                            <b>{{ $log->details['status'] ?? 'N/A' }}</b></small>
                                                    @elseif($log->action === 'venue_updated')
                                                        <small>Venue changed to
                                                            <b>{{ $log->details['location'] ?? 'N/A' }}</b></small>
                                                    @elseif($log->action === 'mode_updated')
                                                        <small>Mode changed to
                                                            <b>{{ $log->details['is_online'] ?? false ? 'Online' : 'Physical' }}</b></small>
                                                    @elseif($log->action === 'actuals_logged')
                                                        <small>Start:
                                                            <b>{{ \Carbon\Carbon::parse($log->details['actual_start_at'] ?? now())->format('M d H:i') }}</b>
                                                            &rarr; End:
                                                            <b>{{ \Carbon\Carbon::parse($log->details['actual_end_at'] ?? now())->format('M d H:i') }}</b></small>
                                                    @else
                                                        <small><code>{{ json_encode($log->details) }}</code></small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">
                                                    <i class="ri ri-file-list-3-line ri-2x mb-2 d-block"></i>
                                                    No activity logs found. Logs are created when course leaders make
                                                    changes (venue, mode, status, actuals).
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($logs->hasPages())
                                <div class="card-footer pb-0 border-top">
                                    {{ $logs->links() }}
                                </div>
                            @endif
                        </div>

                    </div>
                    {{-- END TAB 2 --}}
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

            // ── Dynamic auto-submit filters (Leaders tab) ──
            let searchTimer;
            document.querySelectorAll('#leaders-filter-form .js-auto-filter').forEach(function(el) {
                el.addEventListener('change', function() {
                    document.getElementById('leaders-filter-form').submit();
                });
            });
            const searchInput = document.querySelector('#leaders-filter-form input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        document.getElementById('leaders-filter-form').submit();
                    }, 500);
                });
            }

            // ── Dynamic auto-submit filters (Logs tab) ──
            document.querySelectorAll('#logs-filter-form .js-auto-filter-logs').forEach(function(el) {
                el.addEventListener('change', function() {
                    document.getElementById('logs-filter-form').submit();
                });
            });

            // ── Wait for jQuery and Select2 to load ──
            const initInterval = setInterval(function() {
                if (typeof $ !== 'undefined' && $.fn && $.fn.select2) {
                    clearInterval(initInterval);
                    initCourseLeaders();
                }
            }, 50);

            function initCourseLeaders() {
                const $semester = $('#academic_semester_id');
                const $program = $('#program_id');
                const $year = $('#year_of_study');
                const $group = $('#group_id');
                const $student = $('#student_ids');

                function checkCohortFilled() {
                    const isFilled = $semester.val() && $program.val() && $year.val() && $group.val();
                    $student.prop('disabled', !isFilled);
                }

                [$semester, $program, $year, $group].forEach($el => {
                    $el.on('change', function() {
                        $student.empty().append('<option value=""></option>').val(null).trigger(
                            'change');
                        checkCohortFilled();
                    });
                });

                checkCohortFilled();

                // Destroy any existing Select2 instance
                if ($student.hasClass('select2-hidden-accessible')) {
                    $student.select2('destroy');
                }

                // Initialize Select2 for student search filtered by cohort
                $student.select2({
                    placeholder: 'Search for enrolled students...',
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('admin.course-leaders.students.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term || '',
                                academic_semester_id: $semester.val(),
                                program_id: $program.val(),
                                year_of_study: $year.val(),
                                group_id: $group.val(),
                                _ts: Date.now()
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.name
                                    };
                                })
                            };
                        },
                        cache: false
                    }
                });

                // SweetAlert for Delete Action
                $('.js-delete-leader').on('click', function(e) {
                    e.preventDefault();
                    let form = $(this).closest('form');
                    let name = $(this).data('name');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Remove Leader?',
                            text: `Are you sure you want to remove ${name} as a course leader?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, remove!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        if (confirm(`Are you sure you want to remove ${name}?`)) form.submit();
                    }
                });

                @if (session('success'))
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: '{{ session('success') }}',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                @endif

                @if (session('error'))
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: '{{ session('error') }}',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                @endif

                @if ($errors->any())
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: `{!! implode('<br>', $errors->all()) !!}`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000
                        });
                    }
                @endif
            }
        });
    </script>
@endsection
