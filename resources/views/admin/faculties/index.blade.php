@extends('layouts/layoutMaster')

@section('title', 'Manage Faculties')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">MUBS /</span> Faculties
            </h4>

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
                <div class="flex-grow-1">
                    <input type="text" id="faculty-search" class="form-control" placeholder="Search faculties...">
                </div>
                <select id="faculty-campus-filter" class="form-select" style="max-width: 200px;">
                    <option value="">All Campuses</option>
                    @foreach ($campuses as $campus)
                        <option value="{{ strtolower($campus->name) }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#facultyModal" onclick="resetFacultyForm()">
                    <i class="ri ri-add-line me-1"></i> Add Faculty
                </button>
            </div>

            {{-- Faculties Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="faculties-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Campuses</th>
                                <th>Departments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faculties as $faculty)
                                <tr data-search="{{ strtolower($faculty->code . ' ' . $faculty->name . ' ' . $faculty->campuses->pluck('name')->join(' ')) }}"
                                    data-campus="{{ strtolower($faculty->campuses->pluck('name')->join(' ')) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-label-primary fw-medium">{{ $faculty->code }}</span></td>
                                    <td><span class="fw-medium">{{ $faculty->name }}</span></td>
                                    <td>
                                        @forelse ($faculty->campuses as $c)
                                            <span class="badge bg-label-info me-1">{{ $c->name }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $faculty->departments_count }}</span></td>
                                    <td>
                                        @if ($faculty->is_active)
                                            <span class="badge bg-label-success">Active</span>
                                        @else
                                            <span class="badge bg-label-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editFaculty({{ json_encode($faculty->only('id', 'code', 'name', 'is_active')) }}, {{ json_encode($faculty->campuses->pluck('id')) }})"
                                                title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.faculties.destroy', $faculty) }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $faculty->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No faculties found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Faculty Modal --}}
    <div class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="facultyForm" method="POST" action="{{ route('admin.faculties.store') }}">
                @csrf
                <input type="hidden" name="_method" id="facultyFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="facultyModalTitle">Add Faculty</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Faculty Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fac_code" name="code" required
                                placeholder="e.g. FCI">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fac_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Campuses</label>
                            <div class="dropdown">
                                <button
                                    class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center"
                                    type="button" id="campusDropdownBtn" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" aria-expanded="false">
                                    <span id="campusDropdownLabel" class="text-truncate">Select campuses...</span>
                                    <i class="ri ri-arrow-down-s-line ms-2"></i>
                                </button>
                                <ul class="dropdown-menu w-100 p-2" aria-labelledby="campusDropdownBtn">
                                    @foreach ($campuses as $campus)
                                        <li>
                                            <label class="dropdown-item d-flex align-items-center gap-2 rounded py-2 px-3"
                                                for="campus_{{ $campus->id }}" style="cursor:pointer">
                                                <input class="form-check-input campus-checkbox m-0" type="checkbox"
                                                    name="campus_ids[]" value="{{ $campus->id }}"
                                                    id="campus_{{ $campus->id }}" onchange="updateCampusLabel()">
                                                {{ $campus->name }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="fac_is_active" name="is_active"
                                value="1" checked>
                            <label class="form-check-label" for="fac_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="facultySubmitBtn">Save Faculty</button>
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
                const term = document.getElementById('faculty-search').value.toLowerCase();
                const campus = document.getElementById('faculty-campus-filter').value;
                document.querySelectorAll('#faculties-table tbody tr[data-search]').forEach(row => {
                    const matchText = !term || row.dataset.search.includes(term);
                    const matchCampus = !campus || row.dataset.campus.includes(campus);
                    row.style.display = (matchText && matchCampus) ? '' : 'none';
                });
            }
            document.getElementById('faculty-search').addEventListener('keyup', filterRows);
            document.getElementById('faculty-campus-filter').addEventListener('change', filterRows);

            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Faculty?',
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

        function resetFacultyForm() {
            document.getElementById('facultyModalTitle').textContent = 'Add Faculty';
            document.getElementById('facultySubmitBtn').textContent = 'Save Faculty';
            document.getElementById('facultyForm').action = '{{ route('admin.faculties.store') }}';
            document.getElementById('facultyFormMethod').value = 'POST';
            document.getElementById('fac_code').value = '';
            document.getElementById('fac_name').value = '';
            // Clear checkboxes
            document.querySelectorAll('.campus-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('fac_is_active').checked = true;
            updateCampusLabel();
        }

        function editFaculty(fac, campusIds) {
            document.getElementById('facultyModalTitle').textContent = 'Edit Faculty';
            document.getElementById('facultySubmitBtn').textContent = 'Update Faculty';
            document.getElementById('facultyForm').action = `/admin/faculties/${fac.id}`;
            document.getElementById('facultyFormMethod').value = 'PUT';
            document.getElementById('fac_code').value = fac.code;
            document.getElementById('fac_name').value = fac.name;
            document.getElementById('fac_is_active').checked = fac.is_active;
            // Set checkboxes
            document.querySelectorAll('.campus-checkbox').forEach(cb => {
                cb.checked = campusIds.includes(parseInt(cb.value));
            });
            updateCampusLabel();
            new bootstrap.Modal(document.getElementById('facultyModal')).show();
        }

        function updateCampusLabel() {
            const checked = document.querySelectorAll('.campus-checkbox:checked');
            const label = document.getElementById('campusDropdownLabel');
            if (checked.length === 0) {
                label.textContent = 'Select campuses...';
            } else {
                const names = Array.from(checked).map(cb => cb.closest('label').textContent.trim());
                label.textContent = names.join(', ');
            }
        }
    </script>
@endsection
