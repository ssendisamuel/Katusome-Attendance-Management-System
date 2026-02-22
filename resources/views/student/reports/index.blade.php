@extends('layouts/layoutMaster')

@section('title', 'My Attendance Reports')

@section('content')
    <div class="row g-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">My Attendance History</h4>
        </div>

        <!-- Filters -->
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Filter Report</h5>
                </div>
                <div class="card-body mt-4">
                    <form method="GET" action="{{ route('student.reports.index') }}" class="row g-3">
                        <div class="col-12 col-md-2">
                            <label class="form-label">Semester</label>
                            <select name="semester_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                @foreach ($semesters as $sem)
                                    <option value="{{ $sem->id }}"
                                        {{ request('semester_id') == $sem->id ? 'selected' : '' }}>
                                        {{ $sem->year }} {{ $sem->semester }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Year of Study</label>
                            <select name="year_of_study" class="form-select" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}"
                                        {{ request('year_of_study') == $year ? 'selected' : '' }}>
                                        Year {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Courses</option>
                                @foreach ($allCourses as $course)
                                    <option value="{{ $course->id }}"
                                        {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present
                                </option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent
                                </option>
                                <option value="excused" {{ request('status') == 'excused' ? 'selected' : '' }}>Excused
                                </option>
                                <option value="incomplete" {{ request('status') == 'incomplete' ? 'selected' : '' }}>
                                    Incomplete</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"
                                onchange="this.form.submit()">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="col-12">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card bg-success-subtle h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded bg-label-success"><i
                                            class="ri-user-follow-line"></i></span>
                                </div>
                                <h5 class="mb-0 text-success">{{ $records->where('status', 'present')->count() }}</h5>
                            </div>
                            <p class="mb-0">Present</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card bg-warning-subtle h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded bg-label-warning"><i
                                            class="ri-time-line"></i></span>
                                </div>
                                <h5 class="mb-0 text-warning">{{ $records->where('status', 'late')->count() }}</h5>
                            </div>
                            <p class="mb-0">Late</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card bg-danger-subtle h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded bg-label-danger"><i
                                            class="ri-user-unfollow-line"></i></span>
                                </div>
                                <h5 class="mb-0 text-danger">{{ $records->where('status', 'absent')->count() }}</h5>
                            </div>
                            <p class="mb-0">Absent</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <!-- Export / Email Actions -->
                    <div class="card bg-primary-subtle h-100">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                            <h6 class="mb-2 text-primary">Export Report</h6>
                            <p class="small mb-3 text-muted">Email this filtered report to yourself</p>
                            <form action="{{ route('student.reports.email') }}" method="POST" class="w-100">
                                @csrf
                                <!-- Pass current filters -->
                                <input type="hidden" name="course_id" value="{{ request('course_id') }}">
                                <input type="hidden" name="semester_id" value="{{ request('semester_id') }}">
                                <input type="hidden" name="status" value="{{ request('status') }}">
                                <input type="hidden" name="year_of_study" value="{{ request('year_of_study') }}">
                                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                                <input type="hidden" name="end_date" value="{{ request('end_date') }}">

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-mail-send-line me-1"></i> Send via Email
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="col-12">
            <div class="card">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td>{{ $record->marked_at->format('d M Y') }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ optional($record->schedule->course)->name }}</span>
                                            <small
                                                class="text-muted">{{ optional($record->schedule->course)->code }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $record->marked_at->format('H:i') }}
                                        @if ($record->clock_out_time)
                                            <small class="text-muted d-block">Out:
                                                {{ $record->clock_out_time->format('H:i') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($record->status)
                                            @case('present')
                                                <span class="badge bg-label-success">Present</span>
                                            @break

                                            @case('late')
                                                <span class="badge bg-label-warning">Late</span>
                                            @break

                                            @case('absent')
                                                <span class="badge bg-label-danger">Absent</span>
                                            @break

                                            @case('excused')
                                                <span class="badge bg-label-info">Excused</span>
                                            @break

                                            @case('incomplete')
                                                <span class="badge bg-label-warning">Incomplete</span>
                                            @break

                                            @default
                                                <span class="badge bg-label-secondary">{{ ucfirst($record->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <form action="{{ route('attendance.email', $record->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="tooltip" title="Email Record">
                                                <i class="ri-mail-line me-1"></i> Email
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No attendance records found matching your
                                            filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    @if ($records->hasPages())
                        <div class="card-footer">
                            {{ $records->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endsection
