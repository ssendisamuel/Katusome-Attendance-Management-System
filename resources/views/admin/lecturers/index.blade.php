@extends('layouts/layoutMaster')

@section('title', 'Lecturers')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="ri ri-group-line me-2"></i>Lecturers</h4>
                <p class="text-muted mb-0">Manage lecturers — add, edit, bulk import, and assign to departments.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkModal">
                    <i class="ri ri-upload-cloud-line me-1"></i> Bulk Import
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lecturerModal"
                    onclick="resetForm()">
                    <i class="ri ri-add-line me-1"></i> Add Lecturer
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="🔍 Search name, email, designation..." value="" oninput="filterTable()">
                    </div>
                    <div class="col-md-3">
                        <select id="deptFilter" class="form-select" onchange="filterTable()">
                            <option value="">All Departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->code }}">{{ $dept->code }} — {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="desigFilter" class="form-select" onchange="filterTable()">
                            <option value="">All Designations</option>
                            <option value="Professor">Professor</option>
                            <option value="Associate Professor">Assoc. Professor</option>
                            <option value="Senior Lecturer">Senior Lecturer</option>
                            <option value="Lecturer">Lecturer</option>
                            <option value="Assistant Lecturer">Assistant Lecturer</option>
                            <option value="Teaching Assistant">Teaching Assistant</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="badge bg-primary" id="countBadge">{{ count($lecturers) }} lecturers</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-2"
                            onclick="document.getElementById('searchInput').value='';document.getElementById('deptFilter').value='';document.getElementById('desigFilter').value='';filterTable()">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover" id="lecturersTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>NAME</th>
                            <th>DESIGNATION</th>
                            <th>EMAIL</th>
                            <th>PHONE</th>
                            <th>DEPARTMENT</th>
                            <th class="text-center">COURSES</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lecturers as $i => $lecturer)
                            <tr data-search="{{ strtolower(($lecturer->title ?? '') . ' ' . (optional($lecturer->user)->name ?? '') . ' ' . ($lecturer->designation ?? '') . ' ' . (optional($lecturer->user)->email ?? '') . ' ' . ($lecturer->phone ?? '')) }}"
                                data-dept="{{ $lecturer->department?->code ?? '' }}"
                                data-desig="{{ $lecturer->designation ?? '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="text-muted">{{ $lecturer->title ?? '' }}</span>
                                    <strong>{{ optional($lecturer->user)->name ?? '—' }}</strong>
                                </td>
                                <td>
                                    @if ($lecturer->designation)
                                        <span class="badge bg-label-info">{{ $lecturer->designation }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ optional($lecturer->user)->email ?? '—' }}</td>
                                <td>{{ $lecturer->phone ?? '—' }}</td>
                                <td>
                                    @if ($lecturer->department)
                                        <span class="badge bg-label-primary">{{ $lecturer->department->code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $lecturer->courses_count > 0 ? 'primary' : 'secondary' }}">
                                        {{ $lecturer->courses_count }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-icon btn-outline-primary me-1"
                                        onclick="editLecturer({{ json_encode([
                                            'id' => $lecturer->id,
                                            'name' => optional($lecturer->user)->name ?? '',
                                            'email' => optional($lecturer->user)->email ?? '',
                                            'phone' => $lecturer->phone ?? '',
                                            'title' => $lecturer->title ?? '',
                                            'designation' => $lecturer->designation ?? '',
                                            'department_id' => $lecturer->department_id ?? '',
                                        ]) }})"
                                        title="Edit">
                                        <i class="ri ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('admin.lecturers.destroy', $lecturer) }}" method="POST"
                                        class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                            data-name="{{ optional($lecturer->user)->name ?? 'this lecturer' }}"
                                            title="Delete">
                                            <i class="ri ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($lecturers) === 0)
                <div class="card-body text-center py-5">
                    <i class="ri ri-group-line" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="text-muted mt-2">No lecturers found. Add your first lecturer or bulk import.</p>
                </div>
            @endif
        </div>

        {{-- Add/Edit Modal --}}
        @if (true)
            <div class="modal fade" id="lecturerModal" tabindex="-1">
                <div class="modal-dialog">
                    <form id="lecturerForm" method="POST" action="{{ route('admin.lecturers.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTitle">Add Lecturer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Title</label>
                                        <select class="form-select" id="f_title" name="title">
                                            <option value="">—</option>
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Assoc. Prof.">Assoc. Prof.</option>
                                            <option value="Prof.">Prof.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="f_name" name="name"
                                            required placeholder="e.g. Ssendi Samuel" oninput="autoGenerateEmail()">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Designation</label>
                                    <select class="form-select" id="f_designation" name="designation">
                                        <option value="">— Select —</option>
                                        <option value="Teaching Assistant">Teaching Assistant</option>
                                        <option value="Assistant Lecturer">Assistant Lecturer</option>
                                        <option value="Lecturer">Lecturer</option>
                                        <option value="Senior Lecturer">Senior Lecturer</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Professor">Professor</option>
                                    </select>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="f_email" name="email"
                                            required placeholder="auto-generated from name">
                                        <small class="text-muted" id="emailHint">Auto-generated: type a name above</small>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="f_phone" name="phone"
                                            placeholder="e.g. 0772123456">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" id="f_department_id" name="department_id">
                                        <option value="">— Select —</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->code }} —
                                                {{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">Add Lecturer</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Bulk Import Modal --}}
        <div class="modal fade" id="bulkModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.lecturers.bulk-import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bulk Import Lecturers</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">Upload a CSV file with the following columns:</p>
                            <div class="alert alert-info py-2">
                                <code>name, email, phone, title, designation, department</code>
                                <br><small>email & department(code) are optional — email will be auto-generated, department
                                    matched by code.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CSV File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="csv_file" accept=".csv,.txt" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri ri-upload-cloud-line me-1"></i> Import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        // ── Filter Table ──
        function filterTable() {
            var search = document.getElementById('searchInput').value.toLowerCase();
            var dept = document.getElementById('deptFilter').value;
            var desig = document.getElementById('desigFilter').value;
            var rows = document.querySelectorAll('#lecturersTable tbody tr');
            var count = 0;
            rows.forEach(function(row) {
                var matchSearch = !search || row.getAttribute('data-search').indexOf(search) !== -1;
                var matchDept = !dept || row.getAttribute('data-dept') === dept;
                var matchDesig = !desig || row.getAttribute('data-desig') === desig;
                var show = matchSearch && matchDept && matchDesig;
                row.style.display = show ? '' : 'none';
                if (show) count++;
            });
            document.getElementById('countBadge').textContent = count + ' lecturers';
        }

        // ── Auto-generate email ──
        function autoGenerateEmail() {
            var name = document.getElementById('f_name').value.trim();
            var emailField = document.getElementById('f_email');
            var hint = document.getElementById('emailHint');
            if (!name || emailField.dataset.manual === 'true') return;

            var parts = name.split(/\s+/);
            var email = '';
            if (parts.length >= 2) {
                var surname = parts[parts.length - 1].toLowerCase().replace(/[^a-z]/g, '');
                var first = parts[0][0].toLowerCase().replace(/[^a-z]/g, '');
                email = first + surname + '@mubs.ac.ug';
            } else {
                email = name.toLowerCase().replace(/[^a-z]/g, '') + '@mubs.ac.ug';
            }
            emailField.value = email;
            hint.textContent = 'Auto: ' + email;
        }

        // Allow manual override
        document.addEventListener('DOMContentLoaded', function() {
            var emailField = document.getElementById('f_email');
            if (emailField) {
                emailField.addEventListener('input', function() {
                    this.dataset.manual = 'true';
                });
            }
        });

        // ── Reset form for Add ──
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add Lecturer';
            document.getElementById('submitBtn').textContent = 'Add Lecturer';
            document.getElementById('lecturerForm').action = '{{ route('admin.lecturers.store') }}';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('f_title').value = '';
            document.getElementById('f_name').value = '';
            document.getElementById('f_designation').value = '';
            document.getElementById('f_email').value = '';
            document.getElementById('f_email').dataset.manual = 'false';
            document.getElementById('f_phone').value = '';
            document.getElementById('f_department_id').value = '';
            document.getElementById('emailHint').textContent = 'Auto-generated: type a name above';
        }

        // ── Edit form ──
        function editLecturer(data) {
            document.getElementById('modalTitle').textContent = 'Edit Lecturer';
            document.getElementById('submitBtn').textContent = 'Update Lecturer';
            document.getElementById('lecturerForm').action = '/admin/lecturers/' + data.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('f_title').value = data.title || '';
            document.getElementById('f_name').value = data.name;
            document.getElementById('f_email').value = data.email;
            document.getElementById('f_email').dataset.manual = 'true';
            document.getElementById('f_phone').value = data.phone || '';
            document.getElementById('f_designation').value = data.designation || '';
            document.getElementById('f_department_id').value = data.department_id || '';
            document.getElementById('emailHint').textContent = '';

            new bootstrap.Modal(document.getElementById('lecturerModal')).show();
        }

        // ── Delete confirmation ──
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var form = this.closest('form');
                    var name = this.dataset.name || 'this lecturer';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete ' + name + '?',
                            text: 'This action cannot be undone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete',
                            cancelButtonText: 'Cancel'
                        }).then(function(result) {
                            if (result.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm('Delete ' + name + '?')) form.submit();
                    }
                });
            });

            // SweetAlert toasts for session messages
            @if (session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: @json(session('success')),
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
                        title: @json(session('error')),
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000
                    });
                }
            @endif
        });
    </script>
@endsection
