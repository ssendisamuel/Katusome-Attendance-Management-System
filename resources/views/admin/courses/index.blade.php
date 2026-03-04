@extends('layouts/layoutMaster')

@section('title', 'Course Management')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Academics /</span> Courses
            </h4>

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex-grow-1" style="min-width: 200px;">
                    <input type="text" id="course-search" class="form-control"
                        placeholder="Search by code, name, or abbreviation...">
                </div>
                <select id="course-fac-filter" class="form-select" style="max-width: 220px;">
                    <option value="">All Faculties</option>
                    @foreach ($faculties as $fac)
                        <option value="{{ strtolower($fac->code) }}">{{ $fac->code }} — {{ $fac->name }}</option>
                    @endforeach
                </select>
                <select id="course-dept-filter" class="form-select" style="max-width: 220px;">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ strtolower($dept->code) }}"
                            data-faculty="{{ strtolower($dept->faculty?->code ?? '') }}">{{ $dept->code }} —
                            {{ $dept->name }}</option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-export="print"
                        data-export-target="#coursesTable"><i class="ri ri-printer-line me-1"></i>Print</button>
                    <button type="button" class="btn btn-outline-danger" data-export="pdf"
                        data-export-target="#coursesTable" data-title="Courses" data-filename="Courses.pdf"
                        data-header="MUBS" data-footer-left="MUBS • Courses"
                        data-json-url="{{ route('admin.courses.index', array_merge(request()->query(), ['format' => 'json'])) }}">
                        <i class="ri ri-file-pdf-line me-1"></i>PDF</button>
                    <button type="button" class="btn btn-outline-success" data-export="excel"
                        data-export-target="#coursesTable" data-title="Courses" data-filename="Courses.xlsx"
                        data-json-url="{{ route('admin.courses.index', array_merge(request()->query(), ['format' => 'json'])) }}">
                        <i class="ri ri-file-excel-line me-1"></i>Excel</button>
                </div>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#courseModal" onclick="resetCourseForm()">
                    <i class="ri ri-add-line me-1"></i> Add Course
                </button>
            </div>

            {{-- Courses Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="coursesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Abbreviation</th>
                                <th>Credit Units</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                <tr data-search="{{ strtolower($course->code . ' ' . $course->name . ' ' . ($course->abbreviation ?? '') . ' ' . ($course->department?->code ?? '')) }}"
                                    data-dept="{{ strtolower($course->department?->code ?? 'none') }}"
                                    data-faculty="{{ strtolower($course->department?->faculty?->code ?? 'none') }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-label-primary fw-medium">{{ $course->code }}</span></td>
                                    <td><span class="fw-medium">{{ $course->name }}</span></td>
                                    <td>{{ $course->abbreviation ?? '—' }}</td>
                                    <td>
                                        @if ($course->credit_units)
                                            <span class="badge bg-label-warning">{{ $course->credit_units }} CU</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($course->department)
                                            <span class="badge bg-label-info">{{ $course->department->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editCourse({{ json_encode($course->only('id', 'code', 'name', 'abbreviation', 'credit_units', 'department_id')) }})"
                                                title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.courses.destroy', $course) }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $course->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No courses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Course Modal --}}
    <div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="courseForm" method="POST" action="{{ route('admin.courses.store') }}">
                @csrf
                <input type="hidden" name="_method" id="courseFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="courseModalTitle">Add Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="c_code" name="code" required
                                    placeholder="e.g. BUC1222">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Abbreviation</label>
                                <input type="text" class="form-control" id="c_abbr" name="abbreviation"
                                    placeholder="e.g. CN">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="c_name" name="name" required
                                placeholder="e.g. Computer Networks">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Credit Units</label>
                                <input type="number" class="form-control" id="c_credits" name="credit_units"
                                    min="1" max="20" placeholder="e.g. 4">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select class="form-select" id="c_dept" name="department_id">
                                    <option value="">— None —</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="courseSubmitBtn">Save Course</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/assets/js/report-export.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const facFilter = document.getElementById('course-fac-filter');
            const deptFilter = document.getElementById('course-dept-filter');
            const allDeptOpts = Array.from(deptFilter.querySelectorAll('option[data-faculty]'));

            // Cascade: when faculty changes, filter department options
            facFilter.addEventListener('change', function() {
                const fac = this.value;
                deptFilter.value = '';
                allDeptOpts.forEach(opt => {
                    opt.style.display = (!fac || opt.dataset.faculty === fac) ? '' : 'none';
                });
                filterRows();
            });

            function filterRows() {
                const term = document.getElementById('course-search').value.toLowerCase();
                const fac = facFilter.value;
                const dept = deptFilter.value;
                document.querySelectorAll('#coursesTable tbody tr[data-search]').forEach(row => {
                    const matchText = !term || row.dataset.search.includes(term);
                    const matchFac = !fac || row.dataset.faculty === fac;
                    const matchDept = !dept || row.dataset.dept === dept;
                    row.style.display = (matchText && matchFac && matchDept) ? '' : 'none';
                });
            }
            document.getElementById('course-search').addEventListener('keyup', filterRows);
            deptFilter.addEventListener('change', filterRows);

            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Course?',
                            text: `Are you sure you want to delete "${name}"?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete!'
                        }).then(r => {
                            if (r.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm(`Delete "${name}"?`)) form.submit();
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

        function resetCourseForm() {
            document.getElementById('courseModalTitle').textContent = 'Add Course';
            document.getElementById('courseSubmitBtn').textContent = 'Save Course';
            document.getElementById('courseForm').action = '{{ route('admin.courses.store') }}';
            document.getElementById('courseFormMethod').value = 'POST';
            document.getElementById('c_code').value = '';
            document.getElementById('c_abbr').value = '';
            document.getElementById('c_name').value = '';
            document.getElementById('c_credits').value = '';
            document.getElementById('c_dept').value = '';
        }

        function editCourse(c) {
            document.getElementById('courseModalTitle').textContent = 'Edit Course';
            document.getElementById('courseSubmitBtn').textContent = 'Update Course';
            document.getElementById('courseForm').action = `/admin/courses/${c.id}`;
            document.getElementById('courseFormMethod').value = 'PUT';
            document.getElementById('c_code').value = c.code;
            document.getElementById('c_abbr').value = c.abbreviation || '';
            document.getElementById('c_name').value = c.name;
            document.getElementById('c_credits').value = c.credit_units || '';
            document.getElementById('c_dept').value = c.department_id || '';
            new bootstrap.Modal(document.getElementById('courseModal')).show();
        }
    </script>
@endsection
