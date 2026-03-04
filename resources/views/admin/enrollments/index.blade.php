@php
    $configData = Helper::appClasses();
    // Build a JS-safe map: program_id → { faculty_id, faculty_name, campus_ids: [{id,name}] }
    $programMap = $programs
        ->mapWithKeys(
            fn($p) => [
                $p->id => [
                    'faculty_id' => $p->faculty_id,
                    'faculty_name' => optional($p->faculty)->name ?? '',
                    'campuses' => optional($p->faculty)
                        ->campuses->map(fn($c) => ['id' => $c->id, 'name' => $c->name])
                        ->values()
                        ->toArray(),
                ],
            ],
        )
        ->toArray();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Student Enrollments')

@section('page-style')
    <style>
        .badge-custom {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="ri ri-article-line me-2"></i>Student Enrollments</h4>
                <p class="text-muted mb-0">Manage and review all student semester enrollments.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="ri-add-line me-1"></i> Add Enrollment
                </button>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm mb-1">Semester</label>
                        <select id="filterSemester" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Semesters</option>
                            @foreach ($semesters as $sem)
                                <option value="{{ $sem->display_name }}">{{ $sem->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Campus</label>
                        <select id="filterCampus" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Campuses</option>
                            @foreach ($campuses as $campus)
                                <option value="{{ $campus->name }}">{{ $campus->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Faculty</label>
                        <select id="filterFaculty" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Faculties</option>
                            @foreach ($faculties as $fac)
                                <option value="{{ $fac->name }}">{{ $fac->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Program</label>
                        <select id="filterProgram" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">All Programs</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->code }}">{{ $prog->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label form-label-sm mb-1">Year</label>
                        <select id="filterYear" class="form-select form-select-sm" onchange="filterTable()">
                            <option value="">Any</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">Yr {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm mb-1">Search Student</label>
                        <input type="text" id="filterSearch" class="form-control form-control-sm"
                            placeholder="🔍 Name or student no..." oninput="filterTable()">
                    </div>
                    <div class="col-auto d-flex align-items-end gap-2">
                        <span class="badge bg-primary" id="countBadge">{{ $enrollments->total() }} records</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enrollments Table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="enrollmentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>STUDENT</th>
                            <th>PROGRAM</th>
                            <th>YR</th>
                            <th>GROUP</th>
                            <th>CAMPUS / FACULTY</th>
                            <th>SEMESTER</th>
                            <th>ENROLLED AT</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enrollments as $i => $enr)
                            @php
                                $enrolledCampusId =
                                    $enr->campus_id ??
                                    optional(optional(optional($enr->program)->faculty)->campuses)->first()?->id;
                                $campus =
                                    $enr->campus?->name ??
                                    (optional(optional(optional($enr->program)->faculty)->campuses)->first()?->name ??
                                        'Main Campus');
                                $faculty = optional(optional($enr->program)->faculty)->name ?? '';
                                $semester = optional($enr->academicSemester)->display_name ?? '';
                                $progCode = optional($enr->program)->code ?? '';
                                $studentName = optional($enr->student?->user)->name ?? '';
                                $studentNo = optional($enr->student)->student_no ?? '';

                                $enrData = [
                                    'id' => $enr->id,
                                    'academic_semester_id' => $enr->academic_semester_id,
                                    'program_id' => $enr->program_id,
                                    'year_of_study' => $enr->year_of_study,
                                    'group_id' => $enr->group_id,
                                    'campus_id' => $enrolledCampusId,
                                    'student_name' => $studentName ?: $studentNo,
                                ];
                            @endphp
                            <tr data-search="{{ strtolower($studentName . ' ' . $studentNo . ' ' . optional($enr->student)->reg_no) }}"
                                data-semester="{{ $semester }}" data-campus="{{ $campus }}"
                                data-faculty="{{ $faculty }}" data-program="{{ $progCode }}"
                                data-year="{{ $enr->year_of_study }}">
                                <td>{{ $i + $enrollments->firstItem() }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-heading">{{ $studentName }}</span>
                                        <small
                                            class="text-muted">{{ $studentNo }}{{ optional($enr->student)->reg_no ? ' / ' . $enr->student->reg_no : '' }}</small>
                                    </div>
                                </td>
                                <td><span class="fw-medium">{{ $progCode }}</span></td>
                                <td><span class="badge bg-label-info badge-custom">Yr {{ $enr->year_of_study }}</span></td>
                                <td>{{ optional($enr->group)->name }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">{{ $campus }}</span>
                                        <small class="text-muted">{{ $faculty }}</small>
                                    </div>
                                </td>
                                <td><span class="badge bg-label-success badge-custom">{{ $semester }}</span></td>
                                <td><small>{{ $enr->enrolled_at ? $enr->enrolled_at->format('d M Y') : 'N/A' }}</small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-icon btn-outline-primary me-1"
                                        onclick='editEnrollment(@json($enrData))' title="Edit">
                                        <i class="ri ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('admin.enrollments.destroy', $enr->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                            data-name="{{ $studentName ?: $studentNo }}" title="Delete">
                                            <i class="ri ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="9" class="text-center py-5">
                                    <i class="ri ri-inbox-line" style="font-size:2rem;opacity:.3;"></i>
                                    <p class="text-muted mt-2">No enrollments found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($enrollments->hasPages())
                <div class="card-body pt-0">
                    {{ $enrollments->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        {{-- Add Modal --}}
        <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form id="addForm" action="{{ route('admin.enrollments.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="addModalLabel">Add Enrollment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Student <span class="text-danger">*</span></label>
                                <select name="student_id" id="a_student_id" class="form-select select2" required
                                    style="width: 100%;">
                                    <option value="">Search Student...</option>
                                </select>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Academic Semester <span class="text-danger">*</span></label>
                                    <select name="academic_semester_id" id="a_semester" class="form-select" required>
                                        <option value="">— Select Semester —</option>
                                        @foreach ($semesters as $sem)
                                            <option value="{{ $sem->id }}">{{ $sem->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Program <span class="text-danger">*</span></label>
                                    <select name="program_id" id="a_program" class="form-select" required
                                        onchange="onAddProgramChange()">
                                        <option value="">— Select Program —</option>
                                        @foreach ($programs as $prog)
                                            <option value="{{ $prog->id }}">{{ $prog->code }} -
                                                {{ $prog->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Faculty</label>
                                    <input type="text" id="a_faculty_display" class="form-control" readonly
                                        placeholder="Auto from Program">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Campus <span class="text-danger">*</span></label>
                                    <select name="campus_id" id="a_campus" class="form-select" required>
                                        <option value="">— Select Campus —</option>
                                        @foreach ($campuses as $campus)
                                            <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                                    <select name="year_of_study" id="a_year" class="form-select" required>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}">Year {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Study Group <span class="text-danger">*</span></label>
                                    <select name="group_id" id="a_group" class="form-select" required>
                                        @foreach ($groups as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Enrollment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="editForm" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalTitle">Edit Enrollment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning mb-3">
                            <h6 class="alert-heading fw-bold mb-1"><i class="ri-error-warning-line me-1"></i>Note</h6>
                            <span class="small">Saving will also update the student's current program, year &amp;
                                group.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Academic Semester <span class="text-danger">*</span></label>
                            <select name="academic_semester_id" id="e_semester" class="form-select" required>
                                @foreach ($semesters as $sem)
                                    <option value="{{ $sem->id }}">{{ $sem->display_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Program <span class="text-danger">*</span></label>
                            <select name="program_id" id="e_program" class="form-select" required
                                onchange="onEditProgramChange()">
                                <option value="">— Select Program —</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Faculty</label>
                                <input type="text" id="e_faculty_display" class="form-control" readonly
                                    placeholder="Auto from Program">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Campus <span class="text-danger">*</span></label>
                                <select name="campus_id" id="e_campus" class="form-select" required>
                                    <option value="">— Select Campus —</option>
                                    @foreach ($campuses as $campus)
                                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Year of Study <span class="text-danger">*</span></label>
                                <select name="year_of_study" id="e_year" class="form-select" required>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">Year {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Study Group <span class="text-danger">*</span></label>
                                <select name="group_id" id="e_group" class="form-select" required>
                                    @foreach ($groups as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        // ── Program → Faculty/Campus map ──────────────────────────────────────────
        const programMap = @json($programMap);

        // ── Live filters ──────────────────────────────────────────────────────────
        function filterTable() {
            var search = document.getElementById('filterSearch').value.toLowerCase().trim();
            var semester = document.getElementById('filterSemester').value;
            var campus = document.getElementById('filterCampus').value;
            var faculty = document.getElementById('filterFaculty').value;
            var program = document.getElementById('filterProgram').value;
            var year = document.getElementById('filterYear').value;

            var rows = document.querySelectorAll('#enrollmentsTable tbody tr[data-search]');
            var count = 0;

            rows.forEach(function(row) {
                var ok = true;
                if (search && !row.dataset.search.includes(search)) ok = false;
                if (semester && row.dataset.semester !== semester) ok = false;
                if (campus && row.dataset.campus !== campus) ok = false;
                if (faculty && row.dataset.faculty !== faculty) ok = false;
                if (program && row.dataset.program !== program) ok = false;
                if (year && row.dataset.year !== year) ok = false;

                row.style.display = ok ? '' : 'none';
                if (ok) count++;
            });

            document.getElementById('countBadge').textContent = count + ' records';
        }

        function resetFilters() {
            ['filterSemester', 'filterCampus', 'filterFaculty', 'filterProgram', 'filterYear']
            .forEach(id => document.getElementById(id).value = '');
            document.getElementById('filterSearch').value = '';
            filterTable();
        }

        // ── Program change → update faculty display + campus options ─────────────
        function updateModalFacultyCampus(prefix) {
            var pid = document.getElementById(prefix + '_program').value;
            var info = programMap[pid];
            var fDisp = document.getElementById(prefix + '_faculty_display');
            var cSel = document.getElementById(prefix + '_campus');
            var currentVal = cSel.value;

            if (!info) {
                fDisp.value = '';
                // Hide all campuses except placeholder if no program
                cSel.querySelectorAll('option').forEach(o => {
                    if (o.value) o.style.display = 'none';
                });
                cSel.value = '';
                return;
            }

            fDisp.value = info.faculty_name;

            var campusIds = info.campuses.map(c => c.id.toString());
            var isCurrentValid = false;

            // Show matching, hide others
            cSel.querySelectorAll('option').forEach(function(o) {
                if (!o.value) {
                    o.style.display = '';
                    return;
                }
                var valid = campusIds.includes(o.value);
                o.style.display = valid ? '' : 'none';
                if (valid && o.value === currentVal) {
                    isCurrentValid = true;
                }
            });

            // If only one campus, auto-select it
            if (campusIds.length === 1) {
                cSel.value = campusIds[0];
            } else if (!isCurrentValid) {
                // If current selection is no longer valid for this program, reset it
                cSel.value = '';
            }
        }

        function onEditProgramChange() {
            updateModalFacultyCampus('e');
        }

        function onAddProgramChange() {
            updateModalFacultyCampus('a');
        }

        // ── Edit modal ────────────────────────────────────────────────────────────
        function editEnrollment(data) {
            document.getElementById('editForm').action = '/admin/enrollments/' + data.id;
            document.getElementById('editModalTitle').textContent = 'Edit Enrollment: ' + data.student_name;
            document.getElementById('e_semester').value = data.academic_semester_id;
            document.getElementById('e_program').value = data.program_id;
            document.getElementById('e_year').value = data.year_of_study;
            document.getElementById('e_group').value = data.group_id;
            // Update faculty display and filter campus options for the selected program
            onEditProgramChange();
            // Now pre-select the student's actual saved campus
            if (data.campus_id) {
                document.getElementById('e_campus').value = data.campus_id;
            }
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        // ── Delete with SweetAlert ────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var form = this.closest('form');
                    var name = this.dataset.name || 'this enrollment';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Delete ' + name + '\'s enrollment?',
                            text: 'This action cannot be undone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete',
                            cancelButtonText: 'Cancel',
                            customClass: {
                                confirmButton: 'btn btn-danger me-2',
                                cancelButton: 'btn btn-outline-secondary'
                            },
                            buttonsStyling: false
                        }).then(function(result) {
                            if (result.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm('Delete enrollment for ' + name + '?')) form.submit();
                    }
                });
            });

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

            @if ($errors->any())
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: '<ul style="text-align:left">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>'
                    });
                }
            @endif

            // Initialize select2 for Add Modal student search
            if ($.fn.select2) {
                $('#addModal').on('shown.bs.modal', function() {
                    $('#a_student_id').select2({
                        dropdownParent: $('#addModal'),
                        placeholder: 'Search Student by Name or No...',
                        allowClear: true,
                        ajax: {
                            url: '{{ route('admin.enrollments.students.search') }}',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term // search term
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: $.map(data, function(item) {
                                        return {
                                            text: item.name,
                                            id: item.id
                                        }
                                    })
                                };
                            },
                            cache: true
                        }
                    });
                });
            }
        });
    </script>
@endsection
