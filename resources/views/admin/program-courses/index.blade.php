@extends('layouts/layoutMaster')

@section('title', 'Programme Structure')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Academics /</span> Programme Structure
            </h4>

            <p class="text-muted mb-4">
                Assign courses to programmes by year and semester. Select a programme to view and manage its course
                structure.
            </p>

            {{-- Programme Selector --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-medium">Select Programme</label>
                            <select id="programSelect" class="form-select"
                                onchange="if(this.value) window.location='{{ route('admin.program-courses.index') }}?program_id=' + this.value">
                                <option value="">— Choose a programme —</option>
                                @foreach ($programs as $prog)
                                    <option value="{{ $prog->id }}"
                                        {{ $selectedProgram?->id == $prog->id ? 'selected' : '' }}>
                                        {{ $prog->code }} — {{ $prog->name }}
                                        @if ($prog->faculty)
                                            ({{ $prog->faculty->code }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($selectedProgram)
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#assignModal" onclick="resetAssignForm()">
                                    <i class="ri ri-add-line me-1"></i> Assign Course
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if (!$selectedProgram)
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ri ri-book-open-line ri-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Select a programme above to view its course structure</h5>
                    </div>
                </div>
            @else
                {{-- Programme Info --}}
                <div class="card mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ $selectedProgram->name }}</h5>
                            <span class="badge bg-label-primary me-2">{{ $selectedProgram->code }}</span>
                            @if ($selectedProgram->faculty)
                                <span class="badge bg-label-info me-2">{{ $selectedProgram->faculty->name }}</span>
                            @endif
                            <span class="badge bg-label-secondary">{{ $selectedProgram->duration_years }}
                                {{ $selectedProgram->duration_years == 1 ? 'Year' : 'Years' }}</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted">Total Courses:</span>
                            <span class="fs-4 fw-bold text-primary ms-1">{{ $selectedProgram->courses->count() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Year → Semester → Courses Structure --}}
                @if (empty($structure))
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="ri ri-file-list-3-line ri-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No courses assigned yet</h5>
                            <p class="text-muted">Click "Assign Course" to add courses to this programme.</p>
                        </div>
                    </div>
                @else
                    @foreach ($structure as $year => $semesters)
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="ri ri-calendar-line me-1"></i>
                                    Year {{ $year }}
                                    <span class="badge bg-primary ms-2">
                                        {{ collect($semesters)->flatten(1)->count() }} courses
                                    </span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                @foreach ($semesters as $semester => $semCourses)
                                    <div class="px-3 pt-3 pb-1">
                                        <h6 class="text-primary mb-2">
                                            <i class="ri ri-bookmark-line me-1"></i>{{ $semester }}
                                            <small class="text-muted">({{ count($semCourses) }} courses,
                                                {{ collect($semCourses)->sum(fn($c) => $c->pivot->credit_units) }}
                                                CU)</small>
                                        </h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40px">#</th>
                                                    <th>Code</th>
                                                    <th>Course Name</th>
                                                    <th>Type</th>
                                                    <th>CU</th>
                                                    <th style="width: 100px">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($semCourses as $idx => $course)
                                                    <tr>
                                                        <td>{{ $idx + 1 }}</td>
                                                        <td><span class="badge bg-label-primary">{{ $course->code }}</span>
                                                        </td>
                                                        <td>{{ $course->name }}</td>
                                                        <td>
                                                            @if ($course->pivot->course_type === 'Core')
                                                                <span class="badge bg-label-success">Core</span>
                                                            @elseif($course->pivot->course_type === 'Elective')
                                                                <span class="badge bg-label-warning">Elective</span>
                                                            @else
                                                                <span class="badge bg-label-secondary">Audit</span>
                                                            @endif
                                                        </td>
                                                        <td><span
                                                                class="fw-medium">{{ $course->pivot->credit_units }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex gap-1">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-icon btn-outline-primary"
                                                                    onclick="editAssignment({{ json_encode(['course_id' => $course->id, 'course_name' => $course->code . ' — ' . $course->name, 'year_of_study' => $course->pivot->year_of_study, 'semester_offered' => $course->pivot->semester_offered, 'credit_units' => $course->pivot->credit_units, 'course_type' => $course->pivot->course_type]) }})"
                                                                    title="Edit">
                                                                    <i class="ri ri-pencil-line"></i>
                                                                </button>
                                                                <form
                                                                    action="{{ route('admin.program-courses.destroy', [$selectedProgram, $course]) }}"
                                                                    method="POST" class="d-inline-block">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="btn btn-sm btn-icon btn-outline-danger js-remove-btn"
                                                                        data-name="{{ $course->name }}" title="Remove">
                                                                        <i class="ri ri-close-line"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">
                                                            No courses assigned to this semester.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif
        </div>
    </div>

    {{-- Assign Course Modal --}}
    @if ($selectedProgram)
        <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="assignForm" method="POST" action="{{ route('admin.program-courses.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="assignFormMethod" value="POST">
                    <input type="hidden" name="program_id" value="{{ $selectedProgram->id }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="assignModalTitle">Assign Course to
                                {{ $selectedProgram->code }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3" id="courseSelectGroup">
                                <label class="form-label" for="a_course_id">Course(s) <span
                                        class="text-danger">*</span></label>
                                <select class="select2 form-select" id="a_course_id" name="course_id[]" multiple required
                                    data-placeholder="Select courses">
                                    @foreach ($courses as $c)
                                        <option value="{{ $c->id }}"
                                            data-search="{{ strtolower($c->code . ' ' . $c->name) }}">
                                            {{ $c->code }} — {{ $c->name }}
                                            @if ($c->credit_units)
                                                ({{ $c->credit_units }} CU)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3" id="courseEditLabel" style="display: none;">
                                <label class="form-label">Course</label>
                                <input type="text" class="form-control" id="a_course_label" readonly>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                                    <select class="form-select" id="a_year" name="year_of_study" required>
                                        @for ($y = 1; $y <= $selectedProgram->duration_years; $y++)
                                            <option value="{{ $y }}">Year {{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" id="a_semester" name="semester_offered" required>
                                        <option value="Semester 1">Semester 1</option>
                                        <option value="Semester 2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="a_type" name="course_type" required>
                                        <option value="Core">Core</option>
                                        <option value="Elective">Elective</option>
                                        <option value="Audit">Audit</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="assignSubmitBtn">Assign Course</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize basic select2 if available
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#a_course_id').select2({
                    dropdownParent: $('#assignModal'),
                    width: '100%'
                });
            }

            document.querySelectorAll('.js-remove-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Remove Course?',
                            text: `Remove "${name}" from this programme?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, remove!'
                        }).then(r => {
                            if (r.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm(`Remove "${name}"?`)) form.submit();
                    }
                });
            });

            @if (session('success'))
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
            @if (session('error'))
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
            @if (session('warning'))
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'warning',
                    title: 'Notice',
                    text: '{{ session('warning') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            @endif
            @if ($errors->any())
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            @endif
        });

        function resetAssignForm() {
            document.getElementById('assignModalTitle').textContent =
                'Assign Course(s) to {{ $selectedProgram?->code ?? '' }}';
            document.getElementById('assignSubmitBtn').textContent = 'Assign Course(s)';
            document.getElementById('assignForm').action = '{{ route('admin.program-courses.store') }}';
            document.getElementById('assignFormMethod').value = 'POST';

            // Show course select, hide label
            document.getElementById('courseSelectGroup').style.display = '';
            document.getElementById('courseEditLabel').style.display = 'none';

            // reset select2
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#a_course_id').val(null).trigger('change');
                $('#a_course_id').prop('disabled', false);
            }

            document.getElementById('a_year').value = '1';
            document.getElementById('a_semester').value = 'Semester 1';
            document.getElementById('a_type').value = 'Core';
        }

        function editAssignment(data) {
            document.getElementById('assignModalTitle').textContent = 'Edit Assignment';
            document.getElementById('assignSubmitBtn').textContent = 'Update Assignment';
            document.getElementById('assignForm').action =
                `/admin/program-courses/{{ $selectedProgram?->id }}/${data.course_id}`;
            document.getElementById('assignFormMethod').value = 'PUT';

            // Hide course select, show label
            document.getElementById('courseSelectGroup').style.display = 'none';
            document.getElementById('courseEditLabel').style.display = '';
            document.getElementById('a_course_label').value = data.course_name;

            document.getElementById('a_year').value = data.year_of_study;
            document.getElementById('a_semester').value = data.semester_offered;
            document.getElementById('a_type').value = data.course_type;

            new bootstrap.Modal(document.getElementById('assignModal')).show();
        }
    </script>
@endsection
