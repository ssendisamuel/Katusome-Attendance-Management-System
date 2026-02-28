@extends('layouts/layoutMaster')

@section('title', 'Manage ' . ucfirst(str_replace('_', ' ', $role)) . 's')

@php
    $roleLabel = ucfirst(str_replace('_', ' ', $role));
    $rolePlural = $roleLabel . 's';
@endphp

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">People /</span> {{ $rolePlural }}
            </h4>

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
                <div class="flex-grow-1">
                    <input type="text" id="user-search" class="form-control"
                        placeholder="Search {{ strtolower($rolePlural) }}...">
                </div>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#userModal" onclick="resetUserForm()">
                    <i class="ri ri-add-line me-1"></i> Add {{ $roleLabel }}
                </button>
            </div>

            {{-- Users Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                @if ($role === 'dean')
                                    <th>Faculty</th>
                                @elseif ($role === 'hod')
                                    <th>Department</th>
                                @elseif ($role === 'campus_chief')
                                    <th>Campus</th>
                                @endif
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                @php
                                    $extra = '';
                                    if ($role === 'dean') {
                                        $extra = $user->deanOfFaculty?->name ?? '—';
                                    } elseif ($role === 'hod') {
                                        $extra = $user->hodOfDepartment?->name ?? '—';
                                    }
                                @endphp
                                <tr data-search="{{ strtolower($user->name . ' ' . $user->email . ' ' . $extra) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <span class="fw-medium">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    @if ($role === 'dean')
                                        <td>
                                            @if ($user->deanOfFaculty)
                                                <span class="badge bg-label-info">{{ $user->deanOfFaculty->code }}</span>
                                            @else
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </td>
                                    @elseif ($role === 'hod')
                                        <td>
                                            @if ($user->hodOfDepartment)
                                                <span class="badge bg-label-info">{{ $user->hodOfDepartment->code }} —
                                                    {{ $user->hodOfDepartment->name }}</span>
                                            @else
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </td>
                                    @elseif ($role === 'campus_chief')
                                        <td>—</td>
                                    @endif
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editUser({{ json_encode($user->only('id', 'name', 'email')) }}, {{ json_encode($user->deanOfFaculty?->id) }}, {{ json_encode($user->hodOfDepartment?->id) }})"
                                                title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.users.destroy', [$role, $user]) }}"
                                                method="POST" class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $user->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">No {{ strtolower($rolePlural) }}
                                        found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- User Modal --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="userForm" method="POST" action="{{ route('admin.users.store', $role) }}">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">Add {{ $roleLabel }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="u_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="u_email" name="email" required>
                        </div>
                        <div class="mb-3" id="password-field">
                            <label class="form-label">Password <small class="text-muted">(leave blank for
                                    default)</small></label>
                            <input type="password" class="form-control" id="u_password" name="password"
                                placeholder="Default: password123">
                        </div>

                        @if ($role === 'dean')
                            <div class="mb-3">
                                <label class="form-label">Assign to Faculty</label>
                                <select class="form-select" id="u_faculty_id" name="faculty_id">
                                    <option value="">— None —</option>
                                    @foreach ($faculties as $fac)
                                        <option value="{{ $fac->id }}">{{ $fac->code }} — {{ $fac->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($role === 'hod')
                            <div class="mb-3">
                                <label class="form-label">Assign to Department</label>
                                <select class="form-select" id="u_department_id" name="department_id">
                                    <option value="">— None —</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->code }} — {{ $dept->name }}
                                            ({{ $dept->faculty?->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="userSubmitBtn">Save
                            {{ $roleLabel }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('user-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const term = this.value.toLowerCase();
                    document.querySelectorAll('#users-table tbody tr[data-search]').forEach(row => {
                        row.style.display = row.dataset.search.includes(term) ? '' : 'none';
                    });
                });
            }

            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete {{ $roleLabel }}?',
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

        function resetUserForm() {
            document.getElementById('userModalTitle').textContent = 'Add {{ $roleLabel }}';
            document.getElementById('userSubmitBtn').textContent = 'Save {{ $roleLabel }}';
            document.getElementById('userForm').action = '{{ route('admin.users.store', $role) }}';
            document.getElementById('userFormMethod').value = 'POST';
            document.getElementById('u_name').value = '';
            document.getElementById('u_email').value = '';
            document.getElementById('u_password').value = '';
            const facSelect = document.getElementById('u_faculty_id');
            if (facSelect) facSelect.value = '';
            const deptSelect = document.getElementById('u_department_id');
            if (deptSelect) deptSelect.value = '';
        }

        function editUser(user, facultyId, departmentId) {
            document.getElementById('userModalTitle').textContent = 'Edit {{ $roleLabel }}';
            document.getElementById('userSubmitBtn').textContent = 'Update {{ $roleLabel }}';
            document.getElementById('userForm').action = `/admin/users/{{ $role }}/${user.id}`;
            document.getElementById('userFormMethod').value = 'PUT';
            document.getElementById('u_name').value = user.name;
            document.getElementById('u_email').value = user.email;
            document.getElementById('u_password').value = '';
            const facSelect = document.getElementById('u_faculty_id');
            if (facSelect) facSelect.value = facultyId || '';
            const deptSelect = document.getElementById('u_department_id');
            if (deptSelect) deptSelect.value = departmentId || '';

            new bootstrap.Modal(document.getElementById('userModal')).show();
        }
    </script>
@endsection
