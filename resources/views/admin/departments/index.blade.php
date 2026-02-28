@extends('layouts/layoutMaster')

@section('title', 'Manage Departments')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Organization /</span> Departments
            </h4>

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
                <div class="flex-grow-1">
                    <input type="text" id="dept-search" class="form-control" placeholder="Search departments...">
                </div>
                <select id="dept-faculty-filter" class="form-select" style="max-width: 280px;">
                    <option value="">All Faculties</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ strtolower($faculty->code) }}">{{ $faculty->code }} — {{ $faculty->name }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#deptModal" onclick="resetDeptForm()">
                    <i class="ri ri-add-line me-1"></i> Add Department
                </button>
            </div>

            {{-- Departments Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="depts-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Courses</th>
                                <th>Lecturers</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr data-search="{{ strtolower($dept->code . ' ' . $dept->name . ' ' . ($dept->faculty?->code ?? '')) }}"
                                    data-faculty="{{ strtolower($dept->faculty?->code ?? '') }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-label-primary fw-medium">{{ $dept->code }}</span></td>
                                    <td><span class="fw-medium">{{ $dept->name }}</span></td>
                                    <td>
                                        <small>{{ $dept->faculty?->code ?? '—' }}</small>
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $dept->courses_count }}</span></td>
                                    <td><span class="badge bg-label-warning">{{ $dept->lecturers_count }}</span></td>
                                    <td>
                                        @if ($dept->is_active)
                                            <span class="badge bg-label-success">Active</span>
                                        @else
                                            <span class="badge bg-label-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editDept({{ json_encode($dept) }})" title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $dept->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No departments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Department Modal --}}
    <div class="modal fade" id="deptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="deptForm" method="POST" action="{{ route('admin.departments.store') }}">
                @csrf
                <input type="hidden" name="_method" id="deptFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deptModalTitle">Add Department</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Department Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dept_code" name="code" required
                                placeholder="e.g. CSE">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dept_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty <span class="text-danger">*</span></label>
                            <select class="form-select" id="dept_faculty_id" name="faculty_id" required>
                                <option value="">Select Faculty</option>
                                @foreach ($faculties as $faculty)
                                    <option value="{{ $faculty->id }}">{{ $faculty->code }} — {{ $faculty->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dept_is_active" name="is_active"
                                value="1" checked>
                            <label class="form-check-label" for="dept_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="deptSubmitBtn">Save Department</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function filterRows() {
                const term = document.getElementById('dept-search').value.toLowerCase();
                const faculty = document.getElementById('dept-faculty-filter').value;
                document.querySelectorAll('#depts-table tbody tr[data-search]').forEach(row => {
                    const matchText = !term || row.dataset.search.includes(term);
                    const matchFaculty = !faculty || row.dataset.faculty === faculty;
                    row.style.display = (matchText && matchFaculty) ? '' : 'none';
                });
            }
            document.getElementById('dept-search').addEventListener('keyup', filterRows);
            document.getElementById('dept-faculty-filter').addEventListener('change', filterRows);

            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Department?',
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

        function resetDeptForm() {
            document.getElementById('deptModalTitle').textContent = 'Add Department';
            document.getElementById('deptSubmitBtn').textContent = 'Save Department';
            document.getElementById('deptForm').action = '{{ route('admin.departments.store') }}';
            document.getElementById('deptFormMethod').value = 'POST';
            document.getElementById('dept_code').value = '';
            document.getElementById('dept_name').value = '';
            document.getElementById('dept_faculty_id').value = '';
            document.getElementById('dept_is_active').checked = true;
        }

        function editDept(dept) {
            document.getElementById('deptModalTitle').textContent = 'Edit Department';
            document.getElementById('deptSubmitBtn').textContent = 'Update Department';
            document.getElementById('deptForm').action = `/admin/departments/${dept.id}`;
            document.getElementById('deptFormMethod').value = 'PUT';
            document.getElementById('dept_code').value = dept.code;
            document.getElementById('dept_name').value = dept.name;
            document.getElementById('dept_faculty_id').value = dept.faculty_id;
            document.getElementById('dept_is_active').checked = dept.is_active;
            new bootstrap.Modal(document.getElementById('deptModal')).show();
        }
    </script>
@endsection
