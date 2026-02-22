@extends('layouts/layoutMaster')

@section('title', 'Attendance Dashboard')

@section('content')
    <div class="row gy-4">
        @if (session('success'))
            <script>
                window.Toast && window.Toast.fire({
                    icon: 'success',
                    title: @json(session('success'))
                });
            </script>
            <div class="col-12">
                <div class="alert alert-success">{{ session('success') }}</div>
            </div>
        @endif

        <!-- Enrollment Info -->
        <div class="col-12">
            <div class="card bg-label-primary shadow-none border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h5 class="card-title mb-1 text-primary">Current Enrollment</h5>
                            <div class="d-flex flex-wrap gap-4 mt-3">
                                <div>
                                    <span class="d-block text-body fw-medium">Program</span>
                                    <span class="text-primary">{{ optional($enrollment->program)->name }}
                                        ({{ optional($enrollment->program)->code }})</span>
                                </div>
                                <div>
                                    <span class="d-block text-body fw-medium">Year</span>
                                    <span class="text-primary">Year {{ $enrollment->year_of_study }}</span>
                                </div>
                                <div>
                                    <span class="d-block text-body fw-medium">Semester</span>
                                    <span
                                        class="text-primary">{{ optional($enrollment->academicSemester)->display_name }}</span>
                                </div>
                                <div>
                                    <span class="d-block text-body fw-medium">Group</span>
                                    <span class="text-primary">{{ optional($enrollment->group)->name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="avatar avatar-lg">
                            <span class="avatar-initial rounded bg-white text-primary">
                                <span class="icon-base ri ri-graduation-cap-line icon-24px"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Overview Stats -->
        <div class="col-12">
            <div class="card bg-transparent shadow-none border-0">
                <div class="card-body row g-4 p-0">
                    <div class="col-12 col-md-8">
                        <h5 class="mb-2">Your Attendance Overview</h5>
                        <div class="row g-4">
                            <div class="col-12 col-sm-6">
                                <div class="card h-100 bg-primary-subtle">
                                    <div class="card-body d-flex align-items-center gap-4">
                                        <div class="avatar avatar-lg">
                                            <div class="avatar-initial rounded bg-white">
                                                <span
                                                    class="icon-base ri ri-checkbox-circle-line icon-28px text-primary"></span>
                                            </div>
                                        </div>
                                        <div class="content-right">
                                            <p class="mb-1 fw-medium text-primary text-nowrap">Present Today</p>
                                            <span
                                                class="text-primary mb-0 h5">{{ isset($metrics['presentToday']) ? $metrics['presentToday'] : '—' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="card h-100 bg-warning-subtle">
                                    <div class="card-body d-flex align-items-center gap-4">
                                        <div class="avatar avatar-lg">
                                            <div class="avatar-initial rounded bg-white">
                                                <span class="icon-base ri ri-time-line icon-28px text-warning"></span>
                                            </div>
                                        </div>
                                        <div class="content-right">
                                            <p class="mb-1 fw-medium text-warning text-nowrap">Late</p>
                                            <span
                                                class="text-warning mb-0 h5">{{ isset($metrics['lateToday']) ? $metrics['lateToday'] : '—' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card h-100 border-0 shadow-none">
                            <div class="d-flex align-items-center gap-4 h-100 ps-4">
                                <span class="icon-base ri ri-bar-chart-2-line icon-32px text-success"></span>
                                <div>
                                    <p class="mb-0 fw-medium">Time Spent</p>
                                    <h4 class="mb-0 text-success">
                                        {{ isset($metrics['timeSpentTotalHours']) ? $metrics['timeSpentTotalHours'] . ' Hours' : '0' }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses You Are Taking Today -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Courses You Are Taking Today</h5>
                    <span class="text-muted">{{ \Carbon\Carbon::today()->format('D, M j') }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Time</th>
                                    <th class="text-end">Actions</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($schedules) && count($schedules) > 0)
                                    @foreach ($schedules as $schedule)
                                        @php
                                            $att = $attendanceBySchedule[$schedule->id] ?? null;
                                            $now = now();
                                            $status = $schedule->attendance_status ?? 'scheduled';
                                            $isClosed = $status === 'closed';
                                            $isCancelled = $schedule->is_cancelled || $status === 'cancelled';
                                            $manualActive = $status === 'open' || $status === 'late';
                                            $withinTime = $now->between($schedule->start_at, $schedule->end_at);
                                            $canRecord =
                                                !$att && !$isClosed && !$isCancelled && ($manualActive || $withinTime);

                                            $statusBadge = '';
                                            if ($isCancelled) {
                                                $statusBadge = '<span class="badge bg-secondary">Not Taught</span>';
                                            } elseif ($isClosed) {
                                                $statusBadge = '<span class="badge bg-danger">Disabled</span>';
                                            } elseif (!$att && !$canRecord) {
                                                if ($now->lt($schedule->start_at)) {
                                                    $statusBadge =
                                                        '<span class="badge bg-label-primary">Upcoming</span>';
                                                } elseif ($now->gt($schedule->end_at)) {
                                                    $statusBadge = '<span class="badge bg-label-danger">Missed</span>';
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ optional($schedule->course)->name }}</td>
                                            <td>{{ $schedule->start_at->format('h:i A') }} –
                                                {{ $schedule->end_at->format('h:i A') }}</td>
                                            <td class="text-end">
                                                @if ($att)
                                                    @if ($att->status === 'absent')
                                                        <span class="badge bg-danger">Absent</span>
                                                    @else
                                                        <div class="d-flex flex-column align-items-end gap-1">
                                                            <span class="badge bg-label-success">
                                                                Clocked In: {{ $att->marked_at->format('h:i A') }}
                                                            </span>
                                                            @if ($schedule->requires_clock_out)
                                                                @if (!$att->clock_out_time)
                                                                    <a href="{{ route('attendance.clockout.show', $att) }}"
                                                                        class="btn btn-sm btn-warning">Clock Out</a>
                                                                @else
                                                                    <span class="badge bg-success">
                                                                        Clocked Out:
                                                                        {{ $att->clock_out_time->format('h:i A') }}
                                                                    </span>
                                                                @endif
                                                            @endif
                                                            <form action="{{ route('attendance.email', $att->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                    data-bs-toggle="tooltip" title="Email Record">
                                                                    <i class="ri-mail-send-line"></i>
                                                                </button>
                                                            </form>
                                                    @endif
                                                @elseif ($canRecord)
                                                    <a href="{{ route('attendance.checkin.show', $schedule) }}"
                                                        class="btn btn-sm btn-primary">
                                                        Record Attendance
                                                    </a>
                                                    @if ($status === 'late' || ($schedule->late_at && $now->gt($schedule->late_at)))
                                                        <div class="small text-warning mt-1">Marked as Late</div>
                                                    @endif
                                                @else
                                                    {!! $statusBadge !!}
                                                @endif
                    </div>
                    </td>
                    <td>
                        @if ($att)
                            <span
                                class="badge bg-label-{{ $att->status === 'present' ? 'success' : ($att->status === 'late' ? 'warning' : 'danger') }}">
                                {{ ucfirst($att->status) }}
                            </span>
                        @else
                            @if ($isCancelled)
                                <span class="badge bg-label-secondary">Cancelled</span>
                            @elseif ($isClosed)
                                <span class="badge bg-label-danger">Closed</span>
                            @else
                                <span class="badge bg-label-secondary">{{ ucfirst($status) }}</span>
                            @endif
                        @endif
                    </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" class="text-center py-4">No classes scheduled for today.</td>
                    </tr>
                    @endif
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly & Monthly Summaries Row -->
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">This Week</h5>
                <span class="text-muted">{{ $metrics['weeklyLabel'] ?? '' }}</span>
            </div>
            <div class="card-body">
                @php($weekly = $metrics['weeklyAttended'] ?? [])
                @if (!empty($weekly))
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($weekly as $w)
                                    <tr>
                                        <td>{{ $w['name'] }}</td>
                                        <td>{{ $w['date'] }}</td>
                                        <td>{{ $w['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">No attended classes recorded this week.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">This Month</h5>
                <span class="text-muted">{{ \Carbon\Carbon::now()->format('F Y') }}</span>
            </div>
            <div class="card-body">
                @php($summary = $metrics['monthlySummary'] ?? ['attended' => 0, 'missed' => 0, 'upcoming' => 0])
                <div class="row g-4">
                    <div class="col-12 col-sm-4">
                        <div class="d-flex flex-column align-items-center p-3 bg-success-subtle rounded">
                            <span class="icon-base ri ri-checkbox-circle-line icon-28px text-success mb-2"></span>
                            <span class="h5 mb-0 text-success">{{ $summary['attended'] }}</span>
                            <small class="text-success">Attended</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="d-flex flex-column align-items-center p-3 bg-danger-subtle rounded">
                            <span class="icon-base ri ri-close-circle-line icon-28px text-danger mb-2"></span>
                            <span class="h5 mb-0 text-danger">{{ $summary['missed'] }}</span>
                            <small class="text-danger">Missed</small>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="d-flex flex-column align-items-center p-3 bg-info-subtle rounded">
                            <span class="icon-base ri ri-calendar-line icon-28px text-info mb-2"></span>
                            <span class="h5 mb-0 text-info">{{ $summary['upcoming'] }}</span>
                            <small class="text-info">Upcoming</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Track -->
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Attendance Track</h5>
            </div>
            <div class="card-body">
                @php($trackTable = $metrics['attendanceTrackTable'] ?? [])
                @if (!empty($trackTable))
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Progress</th>
                                    <th>Attended/Taught</th>
                                    <th>Missed</th>
                                    <th>Not Taught</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trackTable as $t)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $t['name'] }}</span>
                                        </td>
                                        <td style="min-width: 120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-heading">{{ $t['progress'] }}%</span>
                                                <div class="progress w-100" style="height: 6px;">
                                                    <div class="progress-bar {{ $t['progress'] >= 75 ? 'bg-success' : ($t['progress'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                        role="progressbar" style="width: {{ $t['progress'] }}%"
                                                        aria-valuenow="{{ $t['progress'] }}" aria-valuemin="0"
                                                        aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $t['attended'] }} / {{ $t['taught'] }}</td>
                                        <td>
                                            @if ($t['missed'] > 0)
                                                <span class="badge bg-label-danger">{{ $t['missed'] }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($t['cancelled'] > 0)
                                                <span class="badge bg-label-secondary">{{ $t['cancelled'] }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $t['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">No course progress to display.</div>
                @endif
            </div>
        </div>
    </div>
    </div>
@endsection
