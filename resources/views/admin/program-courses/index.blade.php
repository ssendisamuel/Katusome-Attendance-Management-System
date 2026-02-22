@extends('layouts/layoutMaster')

@section('title', 'Program Courses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Program Courses</h4>
        <a href="{{ route('admin.program-courses.create') }}" class="btn btn-primary">Assign Course</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="filter-form" method="GET" action="{{ route('admin.program-courses.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-select select2" data-placeholder="Select Program"
                        onchange="this.form.submit()">
                        <option value="">Select Program...</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>
                                {{ $program->name }} ({{ $program->courses_count ?? 0 }} courses)
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (request('program_id'))
                    <div class="col-md-2">
                        <label class="form-label">Year of Study</label>
                        <select name="year_of_study" class="form-select">
                            <option value="">All Years</option>
                            @for ($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" @selected(request('year_of_study') == $i)>Year {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Semester</label>
                        <select name="semester_offered" class="form-select">
                            <option value="">All Semesters</option>
                            <option value="Semester 1" @selected(request('semester_offered') == 'Semester 1')>Semester 1</option>
                            <option value="Semester 2" @selected(request('semester_offered') == 'Semester 2')>Semester 2</option>
                            <option value="Both" @selected(request('semester_offered') == 'Both')>Both</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Course Type</label>
                        <select name="course_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="Core" @selected(request('course_type') == 'Core')>Core</option>
                            <option value="Elective" @selected(request('course_type') == 'Elective')>Elective</option>
                            <option value="Audit" @selected(request('course_type') == 'Audit')>Audit</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('admin.program-courses.index', ['program_id' => request('program_id')]) }}"
                            class="btn btn-outline-secondary">Reset</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($selectedProgram)
        <div class="card" id="courses-table-container">
            <div class="card-header">
                <h5 class="mb-0">Courses for {{ $selectedProgram->name }}</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>C.U</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selectedProgram->courses as $course)
                            <tr>
                                <td>{{ $course->code }}</td>
                                <td>{{ $course->name }}</td>
                                <td>Year {{ $course->pivot->year_of_study }}</td>
                                <td>{{ $course->pivot->semester_offered }}</td>
                                <td>{{ $course->pivot->credit_units }}</td>
                                <td>
                                    @php
                                        $type = $course->pivot->course_type ?? 'Core';
                                        $badgeClass = match ($type) {
                                            'Core' => 'bg-label-primary',
                                            'Elective' => 'bg-label-info',
                                            'Audit' => 'bg-label-secondary',
                                            default => 'bg-label-primary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('admin.program-courses.edit', ['program' => $selectedProgram->id, 'course' => $course->id]) }}"
                                            class="btn btn-sm btn-outline-primary me-2">
                                            <i class="ri-pencil-line me-1"></i> Edit
                                        </a>
                                        <form
                                            action="{{ route('admin.program-courses.destroy', ['program' => $selectedProgram->id, 'course' => $course->id]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-danger js-delete-program-course">
                                                <i class="ri-delete-bin-line me-1"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No courses assigned to this program yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Please select a program above to view its assigned courses.
        </div>
    @endif

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
            // Reusable function to bind delete buttons
            function bindDeleteButtons() {
                document.querySelectorAll('.js-delete-program-course').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const form = this.closest('form');
                        const warningText = "This will remove the course from the program.";

                        if (window.Swal) {
                            window.Swal.fire({
                                title: 'Are you sure?',
                                text: warningText,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, remove it!',
                                customClass: {
                                    confirmButton: 'btn btn-primary me-3',
                                    cancelButton: 'btn btn-label-secondary'
                                },
                                buttonsStyling: false
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            });
                        } else {
                            if (confirm(warningText)) {
                                form.submit();
                            }
                        }
                    });
                });
            }

            // Bind initially
            bindDeleteButtons();

            // Dynamic AJAX Filtering
            const filterForm = document.getElementById('filter-form');
            if (filterForm) {
                const selects = filterForm.querySelectorAll('select:not([name="program_id"])');
                selects.forEach(select => {
                    select.addEventListener('change', function() {
                        const url = new URL(filterForm.action);
                        const formData = new FormData(filterForm);
                        url.search = new URLSearchParams(formData).toString();

                        const container = document.getElementById('courses-table-container');
                        if (container) {
                            container.style.opacity = '0.5';
                        }

                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newContainer = doc.getElementById(
                                    'courses-table-container');

                                if (newContainer && container) {
                                    container.innerHTML = newContainer.innerHTML;
                                    container.style.opacity = '1';
                                    bindDeleteButtons(); // Re-bind SweetAlert to new DOM
                                }
                                window.history.pushState({}, '', url);
                            });
                    });
                });
            }
        });
    </script>
@endsection
