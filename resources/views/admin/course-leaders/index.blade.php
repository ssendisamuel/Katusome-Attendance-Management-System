@extends('layouts/layoutMaster')

@section('title', 'Manage Course Leaders')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Admin /</span> Course Leaders
            </h4>

            <!-- Assign New Leader Form -->
            <div class="card mb-4">
                <h5 class="card-header border-bottom">Assign Course Leader</h5>
                <form action="{{ route('admin.course-leaders.store') }}" method="POST" class="card-body mt-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="academic_semester_id">Academic Semester</label>
                            <select name="academic_semester_id" id="academic_semester_id" class="form-select" required>
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
                                        {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="year_of_study">Year of Study</label>
                            <select name="year_of_study" id="year_of_study" class="form-select" required>
                                <option value="">Select Year</option>
                                @foreach ([1, 2, 3, 4, 5] as $y)
                                    <option value="{{ $y }}" {{ old('year_of_study') == $y ? 'selected' : '' }}>
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
                                        {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label" for="student_ids">Select Student(s)</label>
                            <select name="student_ids[]" id="student_ids" class="form-select custom-select2" multiple
                                required
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
                            <small class="text-muted d-block mt-1">Select students enrolled in the chosen cohort to assign
                                as Course Leaders.</small>
                        </div>
                        <div class="col-md-3 d-flex align-items-center mt-4">
                            <button type="submit" class="btn btn-primary w-100">Assign Leader(s)</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filter / Search form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.course-leaders.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by name, email, admission..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="program_id" class="form-select">
                                <option value="">All Programs</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}"
                                        {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="year_of_study" class="form-select">
                                <option value="">All Years</option>
                                @foreach ([1, 2, 3, 4, 5] as $y)
                                    <option value="{{ $y }}"
                                        {{ request('year_of_study') == $y ? 'selected' : '' }}>Year {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="group_id" class="form-select">
                                <option value="">All Groups</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}"
                                        {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-50">Filter</button>
                            <a href="{{ route('admin.course-leaders.index') }}"
                                class="btn btn-outline-secondary w-50">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leaders List -->
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
                                                    {{ $leader->student?->user?->name ?? $leader->student?->name }}</h6>
                                                <small class="text-truncate">{{ $leader->student->student_no }} |
                                                    {{ $leader->student?->user?->email ?? $leader->student?->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-label-primary">{{ $leader->program->code ?? $leader->program->name }}
                                            {{ $leader->year_of_study }} {{ $leader->group->name }}</span>
                                    </td>
                                    <td>{{ $leader->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <form action="{{ route('admin.course-leaders.destroy', $leader) }}" method="POST"
                                            class="d-inline-block">
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
                                    <td colspan="4" class="text-center">No course leaders found.</td>
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
            // Wait for jQuery and Select2 to load
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
                    if (isFilled) {
                        $student.prop('disabled', false);
                    } else {
                        $student.prop('disabled', true);
                    }
                }

                [$semester, $program, $year, $group].forEach($el => {
                    $el.on('change', function() {
                        // Clear currently selected students when any cohort setting changes
                        // Must append the empty option back for placeholder to work properly
                        $student.empty().append('<option value=""></option>').val(null).trigger(
                            'change');
                        checkCohortFilled();
                    });
                });

                checkCohortFilled(); // Run on initial load to setup correctly if old values are present

                // Destroy any existing Select2 instance (e.g. from template auto-init)
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
