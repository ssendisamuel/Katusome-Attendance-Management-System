@extends('layouts/layoutMaster')

@section('title', 'Program Attendance Report')

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
                    <h5 class="mb-0">Program Report</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.program') }}" class="row g-3">
                        <!-- Form preserved -->
                        <div class="col-md-4">
                            <label class="form-label">Program</label>
                            <select name="program_id" class="form-select select2" required>
                                <option value="">Select Program</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}"
                                        {{ isset($selectedProgram) && $selectedProgram->id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year of Study</label>
                            <select name="year" class="form-select">
                                <option value="">All Years</option>
                                @foreach (range(1, 5) as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                        Year {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
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

        @if ($selectedProgram)
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Group Performance</h5>
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
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Total Classes</th>
                                    <th>Attendance Rate</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupStats as $row)
                                    <tr>
                                        <td>{{ $row['group']->name }}</td>
                                        <td>{{ $row['classes'] }}</td>
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
                                        <td colspan="4" class="text-center text-muted">No data found for this
                                            program/semester.</td>
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
