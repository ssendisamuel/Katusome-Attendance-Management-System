@extends('layouts/layoutMaster')

@section('title', 'Teaching Load Management')

@section('content')
    <style>
        /* ---- Print Styles ---- */
        @media print {
            body * {
                visibility: hidden;
            }

            #printArea,
            #printArea * {
                visibility: visible;
            }

            #printArea {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }
        }

        /* ---- Multi-Select Checklist ---- */
        .checklist-wrapper {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: .375rem;
            padding: .5rem;
            background: #fff;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            padding: 4px 6px;
            border-radius: 4px;
            cursor: pointer;
            gap: 8px;
        }

        .checklist-item:hover {
            background: #f0f5ff;
        }

        .checklist-item input[type=checkbox] {
            accent-color: #696cff;
            flex-shrink: 0;
        }

        .checklist-item label {
            margin: 0;
            cursor: pointer;
            font-size: .85rem;
        }

        .badge-selection {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 32px;
            padding: 6px;
            border: 1px dashed #c7c9d9;
            border-radius: .375rem;
            background: #f8f9ff;
        }

        .badge-selection .badge {
            font-size: .75rem;
            cursor: pointer;
        }

        .badge-selection .badge:hover {
            opacity: .7;
        }

        /* ---- Compliance colours ---- */
        .compliance-overload {
            color: #dc3545;
            font-weight: 700;
        }

        .compliance-underload {
            color: #dc3545;
            font-weight: 700;
        }

        .compliance-ok {
            color: #28a745;
            font-weight: 700;
        }

        .summary-row {
            background: #f8f9fa;
            font-weight: 600;
        }

        .summary-row td {
            border-top: 2px solid #dee2e6;
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h4 class="mb-1"><i class="ri ri-book-open-line me-2"></i>Teaching Load Management</h4>
                <p class="text-muted mb-0">Allocate courses to lecturers and track teaching loads per semester.</p>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="ri ri-download-line me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportExcel()"><i
                                    class="ri ri-file-excel-line me-2"></i>Excel (.xlsx)</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportPDF()"><i
                                    class="ri ri-file-pdf-line me-2"></i>PDF</a></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-primary" onclick="openNewAssignment()" data-bs-toggle="modal"
                    data-bs-target="#assignModal">
                    <i class="ri ri-add-line me-1"></i> New Assignment
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-start border-primary border-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-label-primary rounded"><i class="ri ri-file-list-line"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="statAssignments">0</h5><small class="text-muted">Assignments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-start border-success border-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-label-success rounded"><i class="ri ri-group-line"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="statLecturers">0</h5><small class="text-muted">Lecturers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-start border-info border-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-label-info rounded"><i class="ri ri-book-2-line"></i></div>
                            <div>
                                <h5 class="mb-0" id="statCourses">0</h5><small class="text-muted">Courses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-start border-warning border-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3 bg-label-warning rounded"><i class="ri ri-time-line"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="statHours">0</h5><small class="text-muted">Total Hrs/Wk</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4 no-print">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Academic Year</label>
                        <select id="fAcademicYear" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All</option>
                            @foreach ($academicSemesters->pluck('year')->unique()->sort() as $ay)
                                <option value="{{ $ay }}" {{ $ay == $defaultYear ? 'selected' : '' }}>
                                    {{ $ay }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Semester</label>
                        <select id="fSemester" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All</option>
                            <option value="1" {{ $defaultSem == '1' ? 'selected' : '' }}>1</option>
                            <option value="2" {{ $defaultSem == '2' ? 'selected' : '' }}>2</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Campus</label>
                        <select id="fCampus" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All Campuses</option>
                            @foreach ($campuses as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Faculty</label>
                        <select id="fFaculty" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All Faculties</option>
                            @foreach ($faculties as $f)
                                <option value="{{ $f->id }}">{{ $f->code ?? $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Department</label>
                        <select id="fDepartment" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All Depts</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->code }}">{{ $d->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Program</label>
                        <select id="fProgram" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->code }}">{{ $p->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small mb-0">Year</label>
                        <select id="fYear" class="form-select form-select-sm" onchange="applyFilters()">
                            <option value="">All</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-1">
                            <input type="text" id="fSearch" class="form-control form-control-sm flex-grow-1"
                                placeholder="🔍 Search..." oninput="applyFilters()">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetFilters()"><i
                                    class="ri ri-refresh-line"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-0 no-print" id="loadTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabAssignments"><i
                        class="ri ri-list-check me-1"></i>Assignments</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSummary"><i
                        class="ri ri-bar-chart-grouped-line me-1"></i>Lecturer Load Summary</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCourseSummary"><i
                        class="ri ri-book-line me-1"></i>Course Load Summary</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabProgramSummary"><i
                        class="ri ri-building-line me-1"></i>Program Load Summary</a></li>
        </ul>

        <div class="tab-content">
            {{-- TAB 1: Assignments --}}
            <div class="tab-pane fade show active" id="tabAssignments">
                <div class="card border-top-0 rounded-top-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="tlTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>LECTURER</th>
                                    <th>COURSE</th>
                                    <th>PROGRAM</th>
                                    <th class="text-center">GRP</th>
                                    <th class="text-center">YR</th>
                                    <th class="text-center">SEM</th>
                                    <th class="text-center">HRS/WK</th>
                                    <th>ACADEMIC YR</th>
                                    <th>DEPT</th>
                                    <th>FACULTY</th>
                                    <th>CAMPUS</th>
                                    <th class="text-end no-print">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assignments as $i => $a)
                                    <tr data-search="{{ strtolower(($a->lecturer_title ?? '') . ' ' . $a->lecturer_name . ' ' . ($a->designation ?? '') . ' ' . $a->course_code . ' ' . $a->course_name) }}"
                                        data-sem="{{ $a->semester ?? '' }}" data-dept="{{ $a->department_code ?? '' }}"
                                        data-faculty="{{ $a->faculty_id ?? '' }}"
                                        data-campus="{{ $a->campus_id ?? '' }}" data-prog="{{ $a->program_code ?? '' }}"
                                        data-yr="{{ $a->year_of_study ?? '' }}" data-grp="{{ $a->study_group ?? '' }}"
                                        data-ay="{{ $a->academic_year ?? '' }}" data-hrs="{{ $a->hours_per_week ?? 0 }}"
                                        data-lid="{{ $a->lecturer_id }}" data-lname="{{ $a->lecturer_name }}"
                                        data-ltitle="{{ $a->lecturer_title ?? '' }}"
                                        data-ldesig="{{ $a->designation ?? '' }}" data-cid="{{ $a->course_id }}"
                                        data-ccode="{{ $a->course_code }}" data-cname="{{ $a->course_name }}">
                                        <td class="row-num text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <span class="text-muted small">{{ $a->lecturer_title ?? '' }}</span>
                                            <strong>{{ $a->lecturer_name }}</strong>
                                            @if ($a->designation)
                                                <br><small class="text-muted">{{ $a->designation }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $a->course_code }}</strong>
                                            <br><small
                                                class="text-muted">{{ \Illuminate\Support\Str::limit($a->course_name, 35) }}</small>
                                        </td>
                                        <td><span class="badge bg-label-primary">{{ $a->program_code ?? '—' }}</span></td>
                                        <td class="text-center">{{ $a->study_group ?? '—' }}</td>
                                        <td class="text-center">{{ $a->year_of_study ?? '—' }}</td>
                                        <td class="text-center">{{ $a->semester ?? '—' }}</td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-{{ ($a->hours_per_week ?? 0) >= 5 ? 'warning' : 'primary' }}">{{ $a->hours_per_week ?? '—' }}</span>
                                        </td>
                                        <td><small>{{ $a->academic_year ?? '—' }}</small></td>
                                        <td>
                                            @if ($a->department_code)
                                                <span class="badge bg-label-info">{{ $a->department_code }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $a->faculty_code ?? '—' }}</small></td>
                                        <td><small class="text-muted">{{ $a->campus_name ?? '—' }}</small></td>
                                        <td class="text-end text-nowrap no-print">
                                            <button class="btn btn-sm btn-icon btn-outline-primary"
                                                onclick="editAssignment({{ json_encode([
                                                    'id' => $a->assignment_id,
                                                    'lecturer_id' => $a->lecturer_id,
                                                    'course_id' => $a->course_id,
                                                    'academic_year' => $a->academic_year,
                                                    'semester' => $a->semester,
                                                    'program_code' => $a->program_code,
                                                    'study_group' => $a->study_group,
                                                    'year_of_study' => $a->year_of_study,
                                                    'hours_per_week' => $a->hours_per_week,
                                                ]) }})"
                                                title="Edit"><i class="ri ri-edit-line"></i></button>
                                            <form
                                                action="{{ route('admin.course-lecturers.destroy', $a->assignment_id) }}"
                                                method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-outline-danger js-delete-btn"
                                                    data-name="{{ $a->lecturer_name }} → {{ $a->course_code }}">
                                                    <i class="ri ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center py-5 text-muted">No teaching assignments
                                            found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 2: Lecturer Load Summary --}}
            <div class="tab-pane fade" id="tabSummary">
                <div class="card border-top-0 rounded-top-0">
                    <div class="table-responsive">
                        <table class="table table-sm" id="summaryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>NAME</th>
                                    <th>DESIGNATION</th>
                                    <th>COURSES TAUGHT</th>
                                    <th>PROGRAMME</th>
                                    <th class="text-center">GRP</th>
                                    <th class="text-center">HRS/WK</th>
                                    <th class="text-center">HRS/SEM</th>
                                    <th class="text-center">MD HRS</th>
                                    <th class="text-center">COMPLIANCE</th>
                                </tr>
                            </thead>
                            <tbody id="summaryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Course Load Summary --}}
            <div class="tab-pane fade" id="tabCourseSummary">
                <div class="card border-top-0 rounded-top-0">
                    <div class="table-responsive">
                        <table class="table table-sm" id="courseSummaryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>COURSE CODE</th>
                                    <th>COURSE NAME</th>
                                    <th>LECTURERS ASSIGNED</th>
                                    <th>PROGRAMME</th>
                                    <th class="text-center">GRP</th>
                                    <th class="text-center">HRS/WK</th>
                                </tr>
                            </thead>
                            <tbody id="courseSummaryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 4: Program Load Summary --}}
            <div class="tab-pane fade" id="tabProgramSummary">
                <div class="card border-top-0 rounded-top-0">
                    <div class="table-responsive">
                        <table class="table table-sm" id="programSummaryTable">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>PROGRAMME</th>
                                    <th>YEAR</th>
                                    <th>COURSES</th>
                                    <th>LECTURERS</th>
                                    <th class="text-center">TOTAL HRS/WK</th>
                                </tr>
                            </thead>
                            <tbody id="programSummaryBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
         ASSIGN / EDIT MODAL
         ============================================================ --}}
        <div class="modal fade" id="assignModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                {{-- NEW ASSIGNMENT form (multi-select, bulk) --}}
                <form id="bulkAssignForm" method="POST" action="{{ route('admin.course-lecturers.store') }}">
                    @csrf
                    <div class="modal-content" id="bulkModalContent">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="ri ri-add-circle-line me-2"></i>New Teaching Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">

                            {{-- Step 1: Assignment Context (Campus → Faculty → Program → Year → Group → Acad Year / Semester) --}}
                            <div class="p-3 mb-3 rounded" style="background:#f0f5ff;border:1px solid #c7d7ff;">
                                <p class="fw-semibold small text-primary mb-2"><i class="ri ri-focus-3-line me-1"></i>Step
                                    1 — Define the Assignment Context</p>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Academic Year <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="academic_year"
                                            id="b_academic_year" required>
                                            @foreach ($academicSemesters->pluck('year')->unique()->sortDesc() as $ay)
                                                <option value="{{ $ay }}"
                                                    {{ $ay == $defaultYear ? 'selected' : '' }}>{{ $ay }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Semester <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="semester" id="b_semester"
                                            required>
                                            <option value="1" {{ $defaultSem == '1' ? 'selected' : '' }}>Semester 1
                                            </option>
                                            <option value="2" {{ $defaultSem == '2' ? 'selected' : '' }}>Semester 2
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Campus</label>
                                        <select class="form-select form-select-sm" id="b_ctx_campus"
                                            onchange="onCtxCampusChange()">
                                            <option value="">— All Campuses —</option>
                                            @foreach ($campuses as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Faculty</label>
                                        <select class="form-select form-select-sm" id="b_ctx_faculty"
                                            onchange="onCtxFacultyChange()">
                                            <option value="">— All Faculties —</option>
                                            @foreach ($faculties as $f)
                                                <option value="{{ $f->id }}"
                                                    data-campus="{{ implode(',', $f->campuses->pluck('id')->toArray()) }}">
                                                    {{ $f->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Program</label>
                                        <select class="form-select form-select-sm" name="program_code"
                                            id="b_program_code">
                                            <option value="">— Any Program —</option>
                                            @foreach ($programs as $p)
                                                <option value="{{ $p->code }}"
                                                    data-faculty="{{ optional($p->faculty)->id }}"
                                                    data-fid="{{ optional($p->faculty)->id }}">{{ $p->code }} —
                                                    {{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Year of Study</label>
                                        <select class="form-select form-select-sm" name="year_of_study"
                                            id="b_year_of_study">
                                            <option value="">— Any Year —</option>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <option value="{{ $i }}">Year {{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Study Group</label>
                                        <select class="form-select form-select-sm" name="study_group" id="b_study_group">
                                            <option value="">— Any Group —</option>
                                            @foreach ($groups as $g)
                                                <option value="{{ $g->name }}">{{ $g->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm">Hours / Week</label>
                                        <input type="number" class="form-control form-control-sm" name="hours_per_week"
                                            id="b_hours_per_week" min="0" max="30" step="0.5"
                                            placeholder="e.g. 4">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Lecturer & Course picker side by side --}}
                            <div class="row g-3">
                                {{-- LEFT: Lecturers --}}
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-2"><i class="ri ri-user-star-line me-1"></i>Select Lecturers
                                        <span class="text-danger">*</span>
                                    </h6>

                                    {{-- Campus / Faculty / Department filter for lecturers --}}
                                    <div class="row g-2 mb-2">
                                        <div class="col-4">
                                            <select class="form-select form-select-sm" id="bl_campus"
                                                onchange="filterBulkLecturers()">
                                                <option value="">All Campuses</option>
                                                @foreach ($campuses as $c)
                                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select form-select-sm" id="bl_faculty"
                                                onchange="filterBulkLecturers()">
                                                <option value="">All Faculties</option>
                                                @foreach ($faculties as $f)
                                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select form-select-sm" id="bl_dept"
                                                onchange="filterBulkLecturers()">
                                                <option value="">All Departments</option>
                                                @foreach ($departments as $d)
                                                    <option value="{{ $d->id }}"
                                                        data-faculty="{{ $d->faculty_id }}">{{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2" id="bl_search"
                                        placeholder="🔍 Search lecturers..." oninput="filterBulkLecturers()">
                                    <div class="checklist-wrapper" id="lecturerChecklist">
                                        @foreach ($lecturers as $l)
                                            <div class="checklist-item lec-item"
                                                data-name="{{ strtolower(($l->title ?? '') . ' ' . optional($l->user)->name . ' ' . ($l->designation ?? '')) }}"
                                                data-fid="{{ optional(optional($l->department)->faculty)->id ?? '' }}"
                                                data-dept="{{ optional($l->department)->id ?? '' }}">
                                                <input type="checkbox" name="lecturer_ids[]" value="{{ $l->id }}"
                                                    id="lc_{{ $l->id }}"
                                                    onchange="updateBadges('lec', {{ $l->id }}, '{{ addslashes(($l->title ?? '') . ' ' . optional($l->user)->name) }}')">
                                                <label for="lc_{{ $l->id }}">
                                                    <strong>{{ $l->title ?? '' }}
                                                        {{ optional($l->user)->name ?? 'Lecturer #' . $l->id }}</strong>
                                                    @if ($l->designation)
                                                        <br><small class="text-muted">{{ $l->designation }}</small>
                                                    @endif
                                                    @if (optional($l->department)->name)
                                                        <br><small
                                                            class="text-muted">{{ optional($l->department)->name }}</small>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Selected lecturers <em>(click to
                                                remove)</em>:</small>
                                        <div class="badge-selection" id="lecBadges"><span class="text-muted small">None
                                                selected</span></div>
                                    </div>
                                </div>

                                {{-- RIGHT: Courses --}}
                                <div class="col-lg-6">
                                    <h6 class="fw-semibold mb-2"><i class="ri ri-book-2-line me-1"></i>Select Courses
                                        <span class="text-danger">*</span>
                                    </h6>

                                    {{-- Faculty / Department filter for courses --}}
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <select class="form-select form-select-sm" id="bc_faculty"
                                                onchange="filterBulkCourses()">
                                                <option value="">All Faculties</option>
                                                @foreach ($faculties as $f)
                                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-sm" id="bc_dept"
                                                onchange="filterBulkCourses()">
                                                <option value="">All Departments</option>
                                                @foreach ($departments as $d)
                                                    <option value="{{ $d->id }}"
                                                        data-faculty="{{ $d->faculty_id }}">{{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <input type="text" class="form-control form-control-sm mb-2" id="bc_search"
                                        placeholder="🔍 Search courses..." oninput="filterBulkCourses()">
                                    <div class="checklist-wrapper" id="courseChecklist">
                                        @foreach ($courses as $c)
                                            @php
                                                $cFacultyIds = $c->programs
                                                    ->pluck('faculty.id')
                                                    ->filter()
                                                    ->unique()
                                                    ->implode(',');
                                                $cDeptIds = $c->programs
                                                    ->pluck('department.id')
                                                    ->filter()
                                                    ->unique()
                                                    ->implode(',');
                                            @endphp
                                            <div class="checklist-item crs-item"
                                                data-name="{{ strtolower($c->code . ' ' . $c->name) }}"
                                                data-fids="{{ $cFacultyIds }}" data-dids="{{ $cDeptIds }}">
                                                <input type="checkbox" name="course_ids[]" value="{{ $c->id }}"
                                                    id="cc_{{ $c->id }}"
                                                    onchange="updateBadges('crs', {{ $c->id }}, '{{ addslashes($c->code . ' — ' . $c->name) }}')">
                                                <label for="cc_{{ $c->id }}">
                                                    <strong>{{ $c->code }}</strong> —
                                                    {{ \Illuminate\Support\Str::limit($c->name, 45) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Selected courses <em>(click to
                                                remove)</em>:</small>
                                        <div class="badge-selection" id="crsBadges"><span class="text-muted small">None
                                                selected</span></div>
                                    </div>
                                </div>
                            </div>

                        </div>{{-- /modal-body --}}
                        <div class="modal-footer">
                            <small class="text-muted me-auto" id="bulkSummaryLine">Select lecturers and courses
                                above</small>
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri ri-check-line me-1"></i> Create Assignments
                            </button>
                        </div>
                    </div>
                </form>

                {{-- EDIT form (single, hidden initially) --}}
                <form id="editAssignForm" method="POST" style="display:none;">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-content">
                        <div class="modal-header bg-warning-subtle">
                            <h5 class="modal-title"><i class="ri ri-edit-line me-2"></i>Edit Teaching Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Lecturer <span class="text-danger">*</span></label>
                                    <select class="form-select" name="lecturer_id" id="e_lecturer_id" required>
                                        @foreach ($lecturers as $l)
                                            <option value="{{ $l->id }}">{{ $l->title ?? '' }}
                                                {{ optional($l->user)->name ?? 'Lecturer #' . $l->id }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Course <span class="text-danger">*</span></label>
                                    <select class="form-select" name="course_id" id="e_course_id" required>
                                        @foreach ($courses as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }} —
                                                {{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-select" name="academic_year" id="e_academic_year" required>
                                        @foreach ($academicSemesters->pluck('year')->unique()->sortDesc() as $ay)
                                            <option value="{{ $ay }}">{{ $ay }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" name="semester" id="e_semester" required>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Program</label>
                                    <select class="form-select" name="program_code" id="e_program_code">
                                        <option value="">— Any —</option>
                                        @foreach ($programs as $p)
                                            <option value="{{ $p->code }}">{{ $p->code }} —
                                                {{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Study Group</label>
                                    <select class="form-select" name="study_group" id="e_study_group">
                                        <option value="">—</option>
                                        @foreach ($groups as $g)
                                            <option value="{{ $g->name }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="year_of_study" id="e_year_of_study">
                                        <option value="">—</option>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}">Year {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Hrs/Wk</label>
                                    <input type="number" class="form-control" name="hours_per_week"
                                        id="e_hours_per_week" min="0" max="30" step="0.5">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" onclick="switchToBulk()">← Back to
                                New</button>
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning"><i
                                    class="ri ri-save-line me-1"></i>Update</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        {{-- Hidden print area for PDF export --}}
        <div id="printArea" style="display:none;"></div>

    </div>
@endsection

@section('page-script')
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
        var MANDATORY_HRS = {{ $mandatoryHrsPerSem }};
        var WEEKS_PER_SEM = 13;

        // ─── State for multi-select badges ───
        var selectedLecturers = {}; // id → label
        var selectedCourses = {}; // id → label

        // ─── Filter bar ───
        function applyFilters() {
            var search = (document.getElementById('fSearch').value || '').toLowerCase();
            var ay = document.getElementById('fAcademicYear').value;
            var sem = document.getElementById('fSemester').value;
            var campus = document.getElementById('fCampus').value;
            var fac = document.getElementById('fFaculty').value;
            var dept = document.getElementById('fDepartment').value;
            var prog = document.getElementById('fProgram').value;
            var yr = document.getElementById('fYear').value;

            var rows = document.querySelectorAll('#tlTable tbody tr[data-search]');
            var visible = 0,
                lecturerSet = {},
                courseSet = {},
                totalHrs = 0;

            rows.forEach(function(row) {
                var ok = true;
                if (search && row.getAttribute('data-search').indexOf(search) === -1) ok = false;
                if (ay && row.getAttribute('data-ay') !== ay) ok = false;
                if (sem && row.getAttribute('data-sem') !== sem) ok = false;
                if (campus && row.getAttribute('data-campus') !== campus) ok = false;
                if (fac && row.getAttribute('data-faculty') !== fac) ok = false;
                if (dept && row.getAttribute('data-dept') !== dept) ok = false;
                if (prog && row.getAttribute('data-prog') !== prog) ok = false;
                if (yr && row.getAttribute('data-yr') !== yr) ok = false;

                row.style.display = ok ? '' : 'none';
                if (ok) {
                    visible++;
                    lecturerSet[row.getAttribute('data-lid')] = 1;
                    courseSet[row.getAttribute('data-cid')] = 1;
                    totalHrs += parseFloat(row.getAttribute('data-hrs')) || 0;
                }
            });

            // Renumber
            var num = 0;
            rows.forEach(function(row) {
                if (row.style.display !== 'none') {
                    num++;
                    var rn = row.querySelector('.row-num');
                    if (rn) rn.textContent = num;
                }
            });

            document.getElementById('statAssignments').textContent = visible;
            document.getElementById('statLecturers').textContent = Object.keys(lecturerSet).length;
            document.getElementById('statCourses').textContent = Object.keys(courseSet).length;
            document.getElementById('statHours').textContent = totalHrs.toFixed(1);

            buildLecturerSummary(rows);
            buildCourseSummary(rows);
            buildProgramSummary(rows);
        }

        function resetFilters() {
            ['fSearch', 'fAcademicYear', 'fSemester', 'fCampus', 'fFaculty', 'fDepartment', 'fProgram', 'fYear'].forEach(
                function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.value = id === 'fAcademicYear' ? '{{ $defaultYear }}' : id === 'fSemester' ?
                            '{{ $defaultSem }}' : '';
                    }
                });
            applyFilters();
        }

        // ─── Lecturer Load Summary ───
        function buildLecturerSummary(rows) {
            var lecturers = {};
            rows.forEach(function(row) {
                if (row.style.display === 'none') return;
                var lid = row.getAttribute('data-lid');
                if (!lecturers[lid]) lecturers[lid] = {
                    title: row.getAttribute('data-ltitle') || '',
                    name: row.getAttribute('data-lname') || '',
                    designation: row.getAttribute('data-ldesig') || '',
                    courses: [],
                    totalHrsWk: 0
                };
                var cells = row.querySelectorAll('td');
                lecturers[lid].courses.push({
                    course: cells[2] ? cells[2].textContent.trim() : '',
                    program: (row.getAttribute('data-prog') || '') + (row.getAttribute('data-yr') ? ' ' +
                        row.getAttribute('data-yr') : ''),
                    group: row.getAttribute('data-grp') || '',
                    hrsWk: parseFloat(row.getAttribute('data-hrs')) || 0
                });
                lecturers[lid].totalHrsWk += parseFloat(row.getAttribute('data-hrs')) || 0;
            });
            var tbody = document.getElementById('summaryBody');
            tbody.innerHTML = '';
            var n = 0;
            Object.keys(lecturers).forEach(function(lid) {
                var lec = lecturers[lid];
                n++;
                var hrsSem = lec.totalHrsWk * WEEKS_PER_SEM;
                var diff = hrsSem - MANDATORY_HRS;
                var compClass = diff > 0 ? 'compliance-overload' : diff < 0 ? 'compliance-underload' :
                    'compliance-ok';
                var compLabel = diff > 0 ? '+' + diff.toFixed(0) : diff.toFixed(0);
                lec.courses.forEach(function(c, ci) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (ci === 0 ? n : '') + '</td><td>' + (ci === 0 ? '<strong>' + lec
                            .title + ' ' + lec.name + '</strong>' : '') + '</td><td>' + (ci === 0 ? lec
                            .designation : '') + '</td><td>' + c.course + '</td><td>' + c.program +
                        '</td><td class="text-center">' + c.group + '</td><td class="text-center">' + c
                        .hrsWk + '</td><td></td><td></td><td></td>';
                    tbody.appendChild(tr);
                });
                var sr = document.createElement('tr');
                sr.className = 'summary-row';
                sr.innerHTML =
                    '<td></td><td></td><td></td><td></td><td></td><td></td><td class="text-center"><strong>' + lec
                    .totalHrsWk + '</strong></td><td class="text-center"><strong>' + hrsSem.toFixed(1) +
                    '</strong></td><td class="text-center"><strong>' + MANDATORY_HRS +
                    '</strong></td><td class="text-center ' + compClass + '">' + compLabel + '</td>';
                tbody.appendChild(sr);
            });
        }

        // ─── Course Load Summary ───
        function buildCourseSummary(rows) {
            var courses = {};
            rows.forEach(function(row) {
                if (row.style.display === 'none') return;
                var cid = row.getAttribute('data-cid');
                if (!courses[cid]) courses[cid] = {
                    code: row.getAttribute('data-ccode') || '',
                    name: row.getAttribute('data-cname') || '',
                    totalHrsWk: 0,
                    assignments: []
                };
                courses[cid].assignments.push({
                    lecturer: (row.getAttribute('data-ltitle') || '') + (row.getAttribute('data-ltitle') ?
                        ' ' : '') + row.getAttribute('data-lname'),
                    program: (row.getAttribute('data-prog') || '') + (row.getAttribute('data-yr') ? ' ' +
                        row.getAttribute('data-yr') : ''),
                    group: row.getAttribute('data-grp') || '',
                    hrsWk: parseFloat(row.getAttribute('data-hrs')) || 0
                });
                courses[cid].totalHrsWk += parseFloat(row.getAttribute('data-hrs')) || 0;
            });
            var tbody = document.getElementById('courseSummaryBody');
            tbody.innerHTML = '';
            var n = 0;
            Object.values(courses).forEach(function(course) {
                n++;
                course.assignments.forEach(function(a, ai) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (ai === 0 ? n : '') + '</td><td>' + (ai === 0 ? '<strong>' +
                            course.code + '</strong>' : '') + '</td><td>' + (ai === 0 ? course.name : '') +
                        '</td><td>' + a.lecturer + '</td><td>' + a.program +
                        '</td><td class="text-center">' + a.group + '</td><td class="text-center">' + a
                        .hrsWk + '</td>';
                    tbody.appendChild(tr);
                });
                var sr = document.createElement('tr');
                sr.className = 'summary-row';
                sr.innerHTML =
                    '<td colspan="6" class="text-end"><strong>Total:</strong></td><td class="text-center"><strong>' +
                    course.totalHrsWk + '</strong></td>';
                tbody.appendChild(sr);
            });
        }

        // ─── Program Load Summary ───
        function buildProgramSummary(rows) {
            var programs = {};
            rows.forEach(function(row) {
                if (row.style.display === 'none') return;
                var pcode = row.getAttribute('data-prog') || 'Unassigned';
                var yr = row.getAttribute('data-yr') || '—';
                var pid = pcode + '_' + yr;
                if (!programs[pid]) programs[pid] = {
                    code: pcode,
                    year: yr,
                    totalHrsWk: 0,
                    assignments: []
                };
                programs[pid].assignments.push({
                    course: row.getAttribute('data-ccode') + ' - ' + row.getAttribute('data-cname'),
                    lecturer: (row.getAttribute('data-ltitle') || '') + (row.getAttribute('data-ltitle') ?
                        ' ' : '') + row.getAttribute('data-lname'),
                    hrsWk: parseFloat(row.getAttribute('data-hrs')) || 0
                });
                programs[pid].totalHrsWk += parseFloat(row.getAttribute('data-hrs')) || 0;
            });
            var tbody = document.getElementById('programSummaryBody');
            tbody.innerHTML = '';
            var n = 0;
            Object.values(programs).forEach(function(prog) {
                n++;
                prog.assignments.forEach(function(a, ai) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (ai === 0 ? n : '') + '</td><td>' + (ai === 0 ? '<strong>' +
                            prog.code + '</strong>' : '') + '</td><td>' + (ai === 0 ? prog.year : '') +
                        '</td><td>' + a.course + '</td><td>' + a.lecturer +
                        '</td><td class="text-center">' + a.hrsWk + '</td>';
                    tbody.appendChild(tr);
                });
                var sr = document.createElement('tr');
                sr.className = 'summary-row';
                sr.innerHTML =
                    '<td colspan="5" class="text-end"><strong>Total:</strong></td><td class="text-center"><strong>' +
                    prog.totalHrsWk + '</strong></td>';
                tbody.appendChild(sr);
            });
        }

        // ─── Excel Export ───
        function exportExcel() {
            var activeTab = document.querySelector('#loadTabs .nav-link.active');
            var table, sheetName = 'Teaching Load';
            if (activeTab && activeTab.getAttribute('href') === '#tabSummary') {
                table = document.getElementById('summaryTable');
                sheetName = 'Lecturer Load Summary';
            } else if (activeTab && activeTab.getAttribute('href') === '#tabCourseSummary') {
                table = document.getElementById('courseSummaryTable');
                sheetName = 'Course Load Summary';
            } else if (activeTab && activeTab.getAttribute('href') === '#tabProgramSummary') {
                table = document.getElementById('programSummaryTable');
                sheetName = 'Program Load Summary';
            } else {
                table = document.getElementById('tlTable');
            }

            var clone = table.cloneNode(true);
            clone.querySelectorAll('tr[style*="display: none"]').forEach(function(r) {
                r.remove();
            });
            clone.querySelectorAll('.no-print').forEach(function(el) {
                el.remove();
            });

            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(clone);

            // Set column widths
            ws['!cols'] = Array(20).fill({
                wch: 18
            });

            XLSX.utils.book_append_sheet(wb, ws, sheetName);

            var ay = document.getElementById('fAcademicYear').value || 'All';
            var sem = document.getElementById('fSemester').value || 'All';
            XLSX.writeFile(wb, 'TeachingLoad_' + ay.replace('/', '_') + '_Sem' + sem + '_' + sheetName.replace(/ /g, '_') +
                '.xlsx');
        }

        // ─── PDF Export (standalone formatted document) ───
        function exportPDF() {
            var activeTab = document.querySelector('#loadTabs .nav-link.active');
            var table;
            var tabTitle = 'Teaching Load — Assignments';
            if (activeTab && activeTab.getAttribute('href') === '#tabSummary') {
                table = document.getElementById('summaryTable');
                tabTitle = 'Teaching Load — Lecturer Load Summary';
            } else if (activeTab && activeTab.getAttribute('href') === '#tabCourseSummary') {
                table = document.getElementById('courseSummaryTable');
                tabTitle = 'Teaching Load — Course Load Summary';
            } else if (activeTab && activeTab.getAttribute('href') === '#tabProgramSummary') {
                table = document.getElementById('programSummaryTable');
                tabTitle = 'Teaching Load — Program Load Summary';
            } else {
                table = document.getElementById('tlTable');
            }

            var clone = table.cloneNode(true);
            clone.querySelectorAll('tr[style*="display: none"]').forEach(function(r) {
                r.remove();
            });
            clone.querySelectorAll('.no-print').forEach(function(el) {
                el.remove();
            });

            var ay = document.getElementById('fAcademicYear').value || 'All Academic Years';
            var sem = document.getElementById('fSemester').value;
            var semTxt = sem ? 'Semester ' + sem : 'All Semesters';
            var campusEl = document.getElementById('fCampus');
            var campusTxt = campusEl && campusEl.selectedIndex > 0 ? campusEl.options[campusEl.selectedIndex].text : '';

            var tableHtml = clone.outerHTML;

            var html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Teaching Load</title>
<style>
  @page { size: A4 landscape; margin: 15mm; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #111; }
  .header { text-align:center; margin-bottom:16px; border-bottom:2px solid #333; padding-bottom:10px; }
  .header h2 { margin:0 0 4px; font-size:14pt; text-transform:uppercase; }
  .header h3 { margin:0 0 4px; font-size:12pt; color:#444; }
  .header p  { margin:0; font-size:9pt; color:#666; }
  table { width:100%; border-collapse:collapse; margin-top:10px; }
  thead { background:#f0f0f0; }
  th, td { border:1px solid #bbb; padding:4px 6px; font-size:9pt; vertical-align:top; }
  th { font-weight:700; text-transform:uppercase; font-size:8pt; }
  tr:nth-child(even) { background:#fafafa; }
  .summary-row { background:#e8e8e8 !important; font-weight:700; border-top:2px solid #888; }
  .footer { text-align:center; margin-top:20px; font-size:8pt; color:#888; }
</style>
</head>
<body>
<div class="header">
  <h2>Makerere University Business School</h2>
  <h3>${tabTitle}</h3>
  <p>Academic Year: <strong>${ay}</strong> &nbsp;|&nbsp; ${semTxt}${campusTxt ? ' &nbsp;|&nbsp; Campus: <strong>'+campusTxt+'</strong>' : ''}</p>
  <p>Generated: ${new Date().toLocaleString()}</p>
</div>
${tableHtml}
<div class="footer">Makerere University Business School &mdash; Teaching Load Report &mdash; Confidential</div>
</body>
</html>`;

            var win = window.open('', '_blank');
            win.document.write(html);
            win.document.close();
            win.focus();
            setTimeout(function() {
                win.print();
            }, 500);
        }

        // ─── Multi-select badge management ───
        function updateBadges(type, id, label) {
            var map = type === 'lec' ? selectedLecturers : selectedCourses;
            var cbEl = document.getElementById((type === 'lec' ? 'lc_' : 'cc_') + id);
            if (cbEl && cbEl.checked) {
                map[id] = label;
            } else {
                delete map[id];
            }
            renderBadges(type);
            updateBulkSummary();
        }

        function renderBadges(type) {
            var map = type === 'lec' ? selectedLecturers : selectedCourses;
            var container = document.getElementById(type === 'lec' ? 'lecBadges' : 'crsBadges');
            container.innerHTML = '';
            var keys = Object.keys(map);
            if (!keys.length) {
                container.innerHTML = '<span class="text-muted small">None selected</span>';
                return;
            }
            keys.forEach(function(id) {
                var badge = document.createElement('span');
                badge.className = 'badge ' + (type === 'lec' ? 'bg-primary' : 'bg-success');
                badge.title = 'Click to remove';
                badge.textContent = map[id] + ' ×';
                badge.onclick = function() {
                    var cb = document.getElementById((type === 'lec' ? 'lc_' : 'cc_') + id);
                    if (cb) {
                        cb.checked = false;
                    }
                    delete map[id];
                    renderBadges(type);
                    updateBulkSummary();
                };
                container.appendChild(badge);
            });
        }

        function updateBulkSummary() {
            var lc = Object.keys(selectedLecturers).length;
            var cc = Object.keys(selectedCourses).length;
            var el = document.getElementById('bulkSummaryLine');
            if (el) {
                if (lc && cc) el.textContent = lc + ' lecturer(s) × ' + cc + ' course(s) = ' + (lc * cc) +
                    ' assignment(s) will be created.';
                else el.textContent = 'Select lecturers and courses above.';
            }
        }

        // ─── Lecturer list filter (modal) ───
        function filterBulkLecturers() {
            var term = (document.getElementById('bl_search').value || '').toLowerCase();
            var facVal = document.getElementById('bl_faculty').value;
            var deptVal = document.getElementById('bl_dept').value;
            document.querySelectorAll('#lecturerChecklist .lec-item').forEach(function(el) {
                var nameOk = !term || el.getAttribute('data-name').indexOf(term) !== -1;
                var facOk = !facVal || el.getAttribute('data-fid') === facVal;
                var deptOk = !deptVal || el.getAttribute('data-dept') === deptVal;
                el.style.display = (nameOk && facOk && deptOk) ? '' : 'none';
            });
        }

        // ─── Course list filter (modal) ───
        function filterBulkCourses() {
            var term = (document.getElementById('bc_search').value || '').toLowerCase();
            var facVal = document.getElementById('bc_faculty').value;
            var deptVal = document.getElementById('bc_dept').value;
            document.querySelectorAll('#courseChecklist .crs-item').forEach(function(el) {
                var nameOk = !term || el.getAttribute('data-name').indexOf(term) !== -1;
                // data-fids is a comma-separated list of faculty IDs the course belongs to via programs
                var fids = (el.getAttribute('data-fids') || '').split(',');
                var dids = (el.getAttribute('data-dids') || '').split(',');
                var facOk = !facVal || fids.indexOf(facVal) !== -1;
                var deptOk = !deptVal || dids.indexOf(deptVal) !== -1;
                el.style.display = (nameOk && facOk && deptOk) ? '' : 'none';
            });
        }

        // ─── Context section cascade helpers ───
        // Campus change: filter Faculty dropdown to campuses containing that faculty
        function onCtxCampusChange() {
            var campusId = document.getElementById('b_ctx_campus').value;
            var facSel = document.getElementById('b_ctx_faculty');
            Array.from(facSel.options).forEach(function(opt) {
                if (!opt.value) return; // keep 'All' option
                var campuses = (opt.getAttribute('data-campus') || '').split(',');
                opt.style.display = (!campusId || campuses.indexOf(campusId) !== -1) ? '' : 'none';
            });
            // If selected faculty is now hidden, reset it
            var selOpt = facSel.options[facSel.selectedIndex];
            if (selOpt && selOpt.style.display === 'none') {
                facSel.value = '';
            }
            onCtxFacultyChange();
            // Mirror to lecturer campus filter
            document.getElementById('bl_campus').value = campusId;
            filterBulkLecturers();
        }

        // Faculty change: filter Program dropdown to those belonging to that faculty
        function onCtxFacultyChange() {
            var facId = document.getElementById('b_ctx_faculty').value;
            var progSel = document.getElementById('b_program_code');
            Array.from(progSel.options).forEach(function(opt) {
                if (!opt.value) return;
                var pFacId = opt.getAttribute('data-fid') || '';
                opt.style.display = (!facId || pFacId === facId) ? '' : 'none';
            });
            // Reset program if now hidden
            var selProg = progSel.options[progSel.selectedIndex];
            if (selProg && selProg.value && selProg.style.display === 'none') {
                progSel.value = '';
            }
            // Mirror faculty to lecturer/course faculty filters and re-run them
            document.getElementById('bl_faculty').value = facId;
            document.getElementById('bc_faculty').value = facId;
            filterBulkLecturers();
            filterBulkCourses();
        }

        // ─── Open modal in new-assignment mode ───
        function openNewAssignment() {
            document.getElementById('bulkAssignForm').style.display = '';
            document.getElementById('bulkModalContent').style.display = '';
            document.getElementById('editAssignForm').style.display = 'none';
            // Reset checkboxes and badges
            document.querySelectorAll('#lecturerChecklist input[type=checkbox]').forEach(function(cb) {
                cb.checked = false;
            });
            document.querySelectorAll('#courseChecklist input[type=checkbox]').forEach(function(cb) {
                cb.checked = false;
            });
            selectedLecturers = {};
            selectedCourses = {};
            renderBadges('lec');
            renderBadges('crs');
            updateBulkSummary();
            // Reset all filter / context fields
            ['bl_search', 'bc_search'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            ['b_ctx_campus', 'b_ctx_faculty', 'bl_campus', 'bl_faculty', 'bl_dept', 'bc_faculty', 'bc_dept'].forEach(
                function(id) {
                    var el = document.getElementById(id);
                    if (el) el.value = '';
                });
            // Show all options in faculty/program ctx dropdowns
            ['b_ctx_faculty', 'b_program_code'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) Array.from(el.options).forEach(function(o) {
                    o.style.display = '';
                });
            });
            filterBulkLecturers();
            filterBulkCourses();
        }

        // ─── Open modal in edit mode ───
        function editAssignment(data) {
            document.getElementById('bulkAssignForm').style.display = 'none';
            document.getElementById('bulkModalContent').style.display = 'none';
            document.getElementById('editAssignForm').style.display = '';

            var form = document.getElementById('editAssignForm');
            form.action = '/admin/course-lecturers/' + data.id;

            document.getElementById('e_lecturer_id').value = data.lecturer_id || '';
            document.getElementById('e_course_id').value = data.course_id || '';
            document.getElementById('e_academic_year').value = data.academic_year || '{{ $defaultYear }}';
            document.getElementById('e_semester').value = data.semester || '{{ $defaultSem }}';
            document.getElementById('e_program_code').value = data.program_code || '';
            document.getElementById('e_study_group').value = data.study_group || '';
            document.getElementById('e_year_of_study').value = data.year_of_study || '';
            document.getElementById('e_hours_per_week').value = data.hours_per_week || '';

            new bootstrap.Modal(document.getElementById('assignModal')).show();
        }

        function switchToBulk() {
            document.getElementById('bulkAssignForm').style.display = '';
            document.getElementById('bulkModalContent').style.display = '';
            document.getElementById('editAssignForm').style.display = 'none';
        }

        // ─── Init ───
        document.addEventListener('DOMContentLoaded', function() {
            applyFilters();

            document.querySelectorAll('.js-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var form = this.closest('form');
                    var name = this.dataset.name || 'this assignment';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                                title: 'Delete?',
                                text: name,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, delete'
                            })
                            .then(function(r) {
                                if (r.isConfirmed) form.submit();
                            });
                    } else {
                        if (confirm('Delete ' + name + '?')) form.submit();
                    }
                });
            });

            @if (session('success'))
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'success',
                    title: @json(session('success')),
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            @endif
            @if (session('error'))
                if (typeof Swal !== 'undefined') Swal.fire({
                    icon: 'error',
                    title: @json(session('error')),
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            @endif
        });
    </script>
@endsection
