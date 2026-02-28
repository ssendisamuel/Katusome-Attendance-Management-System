@extends('layouts/layoutMaster')

@section('title', 'Manage Campuses')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Organization /</span> Campuses
            </h4>

            {{-- Add Campus Button --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="flex-grow-1 me-3">
                    <input type="text" id="campus-search" class="form-control" placeholder="Search campuses..."
                        value="{{ request('search') }}">
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#campusModal"
                    onclick="resetCampusForm()">
                    <i class="ri ri-add-line me-1"></i> Add Campus
                </button>
            </div>

            {{-- Campuses Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="campuses-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Location</th>
                                <th>Faculties</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campuses as $campus)
                                <tr
                                    data-search="{{ strtolower($campus->name . ' ' . $campus->code . ' ' . $campus->location) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-medium">{{ $campus->name }}</span>
                                    </td>
                                    <td>
                                        @if ($campus->code)
                                            <span class="badge bg-label-primary">{{ $campus->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $campus->location ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $campus->faculties_count }}</span>
                                    </td>
                                    <td>
                                        @if ($campus->is_active)
                                            <span class="badge bg-label-success">Active</span>
                                        @else
                                            <span class="badge bg-label-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editCampus({{ json_encode($campus) }})" title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.campuses.destroy', $campus) }}" method="POST"
                                                class="d-inline-block js-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $campus->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No campuses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Campus Modal (Create / Edit) --}}
    <div class="modal fade" id="campusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="campusForm" method="POST" action="{{ route('admin.campuses.store') }}">
                @csrf
                <input type="hidden" name="_method" id="campusFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="campusModalTitle">Add Campus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="campus_name">Campus Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="campus_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="campus_code">Code</label>
                            <input type="text" class="form-control" id="campus_code" name="code"
                                placeholder="e.g. MAIN">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="campus_location">Location</label>
                            <input type="text" class="form-control" id="campus_location" name="location"
                                placeholder="e.g. Nakawa, Kampala">
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="campus_is_active" name="is_active"
                                value="1" checked>
                            <label class="form-check-label" for="campus_is_active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="campusSubmitBtn">Save Campus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Client-side search (instant, no reload) ──
            const searchInput = document.getElementById('campus-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const term = this.value.toLowerCase();
                    document.querySelectorAll('#campuses-table tbody tr[data-search]').forEach(row => {
                        const text = row.getAttribute('data-search');
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }

            // ── SweetAlert deletes ──
            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Campus?',
                            text: `Are you sure you want to delete "${name}"?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, delete!'
                        }).then(result => {
                            if (result.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm(`Delete "${name}"?`)) form.submit();
                    }
                });
            });

            // ── Flash messages ──
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
        });

        function resetCampusForm() {
            document.getElementById('campusModalTitle').textContent = 'Add Campus';
            document.getElementById('campusSubmitBtn').textContent = 'Save Campus';
            document.getElementById('campusForm').action = '{{ route('admin.campuses.store') }}';
            document.getElementById('campusFormMethod').value = 'POST';
            document.getElementById('campus_name').value = '';
            document.getElementById('campus_code').value = '';
            document.getElementById('campus_location').value = '';
            document.getElementById('campus_is_active').checked = true;
        }

        function editCampus(campus) {
            document.getElementById('campusModalTitle').textContent = 'Edit Campus';
            document.getElementById('campusSubmitBtn').textContent = 'Update Campus';
            document.getElementById('campusForm').action = `/admin/campuses/${campus.id}`;
            document.getElementById('campusFormMethod').value = 'PUT';
            document.getElementById('campus_name').value = campus.name;
            document.getElementById('campus_code').value = campus.code || '';
            document.getElementById('campus_location').value = campus.location || '';
            document.getElementById('campus_is_active').checked = campus.is_active;

            new bootstrap.Modal(document.getElementById('campusModal')).show();
        }
    </script>
@endsection
