@extends('layouts/layoutMaster')

@section('title', 'Programme Management')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Academics /</span> Programmes
            </h4>

            {{-- Description --}}
            <p class="text-muted mb-4">
                Manage academic programmes under each faculty. Add, edit, or delete programmes and assign them to faculties
                with their duration.
            </p>

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
                <div class="flex-grow-1" style="min-width: 200px;">
                    <input type="text" id="prog-search" class="form-control" placeholder="Search programmes...">
                </div>
                <select id="prog-faculty-filter" class="form-select" style="max-width: 280px;">
                    <option value="">All Faculties</option>
                    @foreach ($faculties as $fac)
                        <option value="{{ strtolower($fac->code) }}"
                            {{ request('faculty_id') == $fac->id ? 'selected' : '' }}>
                            {{ $fac->code }} — {{ $fac->name }}
                        </option>
                    @endforeach
                </select>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-export="print"
                        data-export-target="#progsTable"><i class="ri ri-printer-line me-1"></i>Print</button>
                    <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#progsTable"
                        data-title="Programmes" data-filename="Programmes.pdf" data-header="MUBS"
                        data-footer-left="MUBS • Programmes"
                        data-json-url="{{ route('admin.programs.index', array_merge(request()->query(), ['format' => 'json'])) }}">
                        <i class="ri ri-file-pdf-line me-1"></i>PDF</button>
                    <button type="button" class="btn btn-outline-success" data-export="excel"
                        data-export-target="#progsTable" data-title="Programmes" data-filename="Programmes.xlsx"
                        data-json-url="{{ route('admin.programs.index', array_merge(request()->query(), ['format' => 'json'])) }}">
                        <i class="ri ri-file-excel-line me-1"></i>Excel</button>
                </div>
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#progModal" onclick="resetProgForm()">
                    <i class="ri ri-add-line me-1"></i> Add Programme
                </button>
            </div>

            {{-- Programmes Table --}}
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover" id="progsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Programme Name</th>
                                <th>Faculty</th>
                                <th>Duration</th>
                                <th>Courses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $currentFaculty = null; @endphp
                            @forelse($programs as $prog)
                                {{-- Faculty group header --}}
                                @if ($prog->faculty_id !== $currentFaculty)
                                    @php $currentFaculty = $prog->faculty_id; @endphp
                                    <tr class="table-light faculty-header"
                                        data-faculty="{{ strtolower($prog->faculty?->code ?? 'none') }}">
                                        <td colspan="7" class="fw-bold text-primary py-2">
                                            <i class="ri ri-government-line me-1"></i>
                                            {{ $prog->faculty?->name ?? 'No Faculty Assigned' }}
                                            @if ($prog->faculty?->campuses->count())
                                                <small class="text-muted ms-2">
                                                    ({{ $prog->faculty->campuses->pluck('name')->join(', ') }})
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <tr data-search="{{ strtolower($prog->code . ' ' . $prog->name . ' ' . ($prog->faculty?->code ?? '')) }}"
                                    data-faculty="{{ strtolower($prog->faculty?->code ?? 'none') }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge bg-label-primary fw-medium">{{ $prog->code }}</span></td>
                                    <td><span class="fw-medium">{{ $prog->name }}</span></td>
                                    <td>
                                        @if ($prog->faculty)
                                            <span class="badge bg-label-info">{{ $prog->faculty->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $prog->duration_years }} {{ $prog->duration_years == 1 ? 'Year' : 'Years' }}
                                    </td>
                                    <td><span class="badge bg-label-warning">{{ $prog->courses_count }}</span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editProg({{ json_encode($prog->only('id', 'code', 'name', 'faculty_id', 'duration_years')) }})"
                                                title="Edit">
                                                <i class="ri ri-pencil-line"></i>
                                            </button>
                                            <form action="{{ route('admin.programs.destroy', $prog) }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $prog->name }}" title="Delete">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No programmes found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Programme Modal --}}
    <div class="modal fade" id="progModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="progForm" method="POST" action="{{ route('admin.programs.store') }}">
                @csrf
                <input type="hidden" name="_method" id="progFormMethod" value="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="progModalTitle">Add Programme</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Programme Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prog_code" name="code" required
                                placeholder="e.g. BBC">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Programme Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="prog_name" name="name" required
                                placeholder="e.g. Bachelor of Business Computing">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty</label>
                            <select class="form-select" id="prog_faculty_id" name="faculty_id">
                                <option value="">— None —</option>
                                @foreach ($faculties as $fac)
                                    <option value="{{ $fac->id }}">{{ $fac->code }} — {{ $fac->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (Years) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="prog_duration" name="duration_years" required
                                min="1" max="7" value="3">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="progSubmitBtn">Save Programme</button>
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
            // ── Client-side filter (search + faculty) ──
            function filterRows() {
                const term = document.getElementById('prog-search').value.toLowerCase();
                const fac = document.getElementById('prog-faculty-filter').value;

                // Hide/show data rows
                document.querySelectorAll('#progsTable tbody tr[data-search]').forEach(row => {
                    const matchText = !term || row.dataset.search.includes(term);
                    const matchFac = !fac || row.dataset.faculty === fac;
                    row.style.display = (matchText && matchFac) ? '' : 'none';
                });

                // Hide/show faculty group headers
                document.querySelectorAll('#progsTable tbody tr.faculty-header').forEach(header => {
                    const facCode = header.dataset.faculty;
                    const matchFac = !fac || facCode === fac;
                    // Check if any visible data rows share this faculty
                    const hasVisibleRows = Array.from(
                        document.querySelectorAll(
                            `#progsTable tbody tr[data-search][data-faculty="${facCode}"]`)
                    ).some(r => r.style.display !== 'none');
                    header.style.display = (matchFac && hasVisibleRows) ? '' : 'none';
                });
            }
            document.getElementById('prog-search').addEventListener('keyup', filterRows);
            document.getElementById('prog-faculty-filter').addEventListener('change', filterRows);

            // ── SweetAlert deletes ──
            document.querySelectorAll('.js-delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete Programme?',
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

        function resetProgForm() {
            document.getElementById('progModalTitle').textContent = 'Add Programme';
            document.getElementById('progSubmitBtn').textContent = 'Save Programme';
            document.getElementById('progForm').action = '{{ route('admin.programs.store') }}';
            document.getElementById('progFormMethod').value = 'POST';
            document.getElementById('prog_code').value = '';
            document.getElementById('prog_name').value = '';
            document.getElementById('prog_faculty_id').value = '';
            document.getElementById('prog_duration').value = '3';
        }

        function editProg(prog) {
            document.getElementById('progModalTitle').textContent = 'Edit Programme';
            document.getElementById('progSubmitBtn').textContent = 'Update Programme';
            document.getElementById('progForm').action = `/admin/programs/${prog.id}`;
            document.getElementById('progFormMethod').value = 'PUT';
            document.getElementById('prog_code').value = prog.code;
            document.getElementById('prog_name').value = prog.name;
            document.getElementById('prog_faculty_id').value = prog.faculty_id || '';
            document.getElementById('prog_duration').value = prog.duration_years || 3;
            new bootstrap.Modal(document.getElementById('progModal')).show();
        }
    </script>
@endsection
