@extends('layouts/layoutMaster')

@section('title', 'Course Attendance Report')

@section('content')
    <style>
        @media print {

            .layout-navbar,
            .layout-menu,
            .footer,
            .btn-primary,
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>

    <div class="row g-4">
        <div class="col-12 no-print">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Course Report</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.course') }}" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select select2" required>
                                <option value="">Select Course</option>
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}"
                                        {{ isset($selectedCourse) && $selectedCourse->id == $c->id ? 'selected' : '' }}>
                                        {{ $c->code }} - {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Academic Semester</label>
                            <select name="semester_id" class="form-select">
                                @foreach ($semesters as $s)
                                    <option value="{{ $s->id }}"
                                        {{ isset($semester) && $semester->id == $s->id ? 'selected' : '' }}>
                                        {{ $s->year }} {{ $s->semester }} {{ $s->is_active ? '(Active)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($selectedCourse)
            <div class="col-12">
                <div class="row g-4">
                    <!-- Stats Card -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Attendance Rate</h6>
                                <h2
                                    class="mb-2 {{ $stats['rate'] < 50 ? 'text-danger' : ($stats['rate'] < 75 ? 'text-warning' : 'text-success') }}">
                                    {{ $stats['rate'] }}%
                                </h2>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $stats['rate'] }}%"></div>
                                </div>
                                <p class="small text-muted mb-0">{{ $stats['present'] }} Present /
                                    {{ $stats['total_records'] }} Records
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Classes Held</h6>
                                <h2>{{ $stats['total_classes'] }}</h2>
                                <p class="small text-muted">In {{ $semester->year }} {{ $semester->semester }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Unexcused Absences</h6>
                                <h2 class="text-danger">{{ $stats['absent'] }}</h2>
                                <p class="small text-muted">Recorded Absences</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Performance by Group</h5>
                        <small class="text-muted">How different groups are performing in
                            {{ $selectedCourse->code }}</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Classes</th>
                                    <th>Present Count</th>
                                    <th>Attendance Rate</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($breakdown as $row)
                                    <tr>
                                        <td>{{ $row['group']->name }}</td>
                                        <td>{{ $row['classes'] }}</td>
                                        <td>{{ $row['present'] }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $row['rate'] >= 75 ? 'bg-label-success' : ($row['rate'] >= 50 ? 'bg-label-warning' : 'bg-label-danger') }}">
                                                {{ $row['rate'] }}%
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.reports.group', ['group_id' => $row['group']->id, 'semester_id' => $semester->id]) }}"
                                                class="btn btn-sm btn-icon btn-text-secondary">
                                                <span class="icon-base ri ri-arrow-right-line"></span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No classes recorded for this
                                            course/semester.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">All Student Attendance</h5>
                            <small class="text-muted">Individual student performance in this course</small>
                        </div>
                        <div class="no-print">
                            <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                                <span class="icon-base ri ri-printer-line me-1"></span> Print
                            </button>
                            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                                class="btn btn-outline-primary">
                                <span class="icon-base ri ri-download-line me-1"></span> Export CSV
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Reg No</th>
                                    <th>Marked in Classes</th>
                                    <th>Present</th>
                                    <th>Attendance Rate</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentStats ?? [] as $row)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span
                                                        class="avatar-initial rounded-circle bg-label-primary">{{ substr($row['student']->name, 0, 1) }}</span>
                                                </div>
                                                <div>{{ $row['student']->name }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $row['student']->reg_no }}</td>
                                        <td>{{ $row['total_records'] }}</td>
                                        <td>{{ $row['present'] }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $row['rate'] >= 75 ? 'bg-label-success' : ($row['rate'] >= 50 ? 'bg-label-warning' : 'bg-label-danger') }}">
                                                {{ $row['rate'] }}%
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.reports.individual', ['student_id' => $row['student']->id]) }}"
                                                class="btn btn-sm btn-icon btn-text-secondary">
                                                <span class="icon-base ri ri-user-search-line"></span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No attendance records found for
                                            this course.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
