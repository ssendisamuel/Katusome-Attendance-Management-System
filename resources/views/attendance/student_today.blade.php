@extends('layouts/layoutMaster')

@section('title', 'My Attendance')

@section('content')
    @if (session('success'))
        <script>
            window.Toast && window.Toast.fire({
                icon: 'success',
                title: @json(session('success'))
            });
        </script>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="icon-base ri ri-calendar-check-line me-2 text-primary"></span>My Attendance
            </h4>
            <p class="text-muted mb-0">{{ \Carbon\Carbon::today()->format('l, F j, Y') }}</p>
        </div>
        @isset($enrollment)
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-primary rounded-pill px-3 py-2">{{ optional($enrollment->program)->code }}</span>
                <span class="badge bg-label-info rounded-pill px-3 py-2">Year {{ $enrollment->year_of_study }}</span>
                <span class="badge bg-label-secondary rounded-pill px-3 py-2">{{ optional($enrollment->group)->name }}</span>
            </div>
        @endisset
    </div>

    @if (!empty($noEnrollment))
        <div class="alert alert-info d-flex align-items-center">
            <span class="icon-base ri ri-information-line me-2 icon-20px"></span>
            <span>You are not currently enrolled. Please enroll to access attendance features.</span>
        </div>
    @else
        {{-- Quick Stats Row --}}
        <div class="row g-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-start border-4 border-success h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block mb-1">Present Today</small>
                                <h3 class="mb-0 text-success">{{ $metrics['presentToday'] ?? 0 }}</h3>
                            </div>
                            <span class="icon-base ri ri-checkbox-circle-fill icon-28px text-success opacity-50"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-start border-4 border-warning h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block mb-1">Late Today</small>
                                <h3 class="mb-0 text-warning">{{ $metrics['lateToday'] ?? 0 }}</h3>
                            </div>
                            <span class="icon-base ri ri-time-fill icon-28px text-warning opacity-50"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-start border-4 border-info h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block mb-1">Total Hours</small>
                                <h3 class="mb-0 text-info">{{ $metrics['timeSpentTotalHours'] ?? 0 }}</h3>
                            </div>
                            <span class="icon-base ri ri-bar-chart-2-fill icon-28px text-info opacity-50"></span>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $summary = $metrics['monthlySummary'] ?? ['attended' => 0, 'missed' => 0, 'upcoming' => 0];
            @endphp
            <div class="col-6 col-lg-3">
                <div class="card border-start border-4 border-danger h-100">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block mb-1">Missed (Month)</small>
                                <h3 class="mb-0 text-danger">{{ $summary['missed'] }}</h3>
                            </div>
                            <span class="icon-base ri ri-close-circle-fill icon-28px text-danger opacity-50"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TODAY'S SESSIONS - Main Focus --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <span class="icon-base ri ri-calendar-todo-line me-2"></span>Today's Sessions
                    </h5>
                    <small class="text-muted">{{ \Carbon\Carbon::today()->format('D, M j') }} —
                        {{ isset($schedules) ? $schedules->count() : 0 }} session(s)</small>
                </div>
                <div>
                    @if (isset($schedules) && $schedules->where('attendance_status', 'open')->count() > 0)
                        <span class="badge bg-success-subtle text-success px-3 py-2">
                            <span
                                class="icon-base ri ri-radio-button-line me-1"></span>{{ $schedules->where('attendance_status', 'open')->count() }}
                            Open Now
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if (isset($schedules) && count($schedules) > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($schedules as $schedule)
                            @php
                                $att = $attendanceBySchedule[$schedule->id] ?? null;
                                $now = now();
                                $status = $schedule->attendance_status ?? 'scheduled';
                                $isClosed = $status === 'closed';
                                $isCancelled = $schedule->is_cancelled || $status === 'cancelled';
                                $manualActive = $status === 'open' || $status === 'late';
                                $withinTime = $now->between($schedule->start_at, $schedule->end_at);
                                $canRecord = !$att && !$isClosed && !$isCancelled && ($manualActive || $withinTime);
                                $isPast = $now->gt($schedule->end_at);
                                $isUpcoming = $now->lt($schedule->start_at);
                            @endphp
                            <div
                                class="list-group-item px-4 py-3 {{ $att ? 'bg-success-subtle' : ($canRecord ? 'bg-primary-subtle' : '') }}">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        {{-- Time Column --}}
                                        <div class="text-center" style="min-width: 80px;">
                                            <div class="fw-bold text-dark">{{ $schedule->start_at->format('h:i A') }}</div>
                                            <small class="text-muted">{{ $schedule->end_at->format('h:i A') }}</small>
                                        </div>

                                        {{-- Divider --}}
                                        <div class="border-start border-2 {{ $att ? 'border-success' : ($canRecord ? 'border-primary' : ($isCancelled ? 'border-secondary' : 'border-light')) }}"
                                            style="height: 40px;"></div>

                                        {{-- Course Info --}}
                                        <div>
                                            <h6 class="mb-0">{{ optional($schedule->course)->name }}</h6>
                                            <small class="text-muted">
                                                {{ optional($schedule->course)->code }}
                                                @if ($schedule->venue)
                                                    · {{ $schedule->venue->name }}
                                                @endif
                                                @if ($schedule->lecturer)
                                                    · {{ optional($schedule->lecturer->user)->name }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>

                                    {{-- Action / Status Column --}}
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($att && $att->status === 'absent')
                                            <span class="badge bg-danger rounded-pill">Absent</span>
                                        @elseif ($att)
                                            <div class="d-flex flex-column align-items-end gap-1">
                                                <span
                                                    class="badge bg-{{ $att->status === 'present' ? 'success' : 'warning' }} rounded-pill">
                                                    <span
                                                        class="icon-base ri ri-check-line me-1"></span>{{ ucfirst($att->status) }}
                                                    · {{ $att->marked_at->format('h:i A') }}
                                                </span>
                                                @if ($schedule->requires_clock_out)
                                                    @if (!$att->clock_out_time)
                                                        <a href="{{ route('attendance.clockout.show', $att) }}"
                                                            class="btn btn-sm btn-warning rounded-pill">
                                                            <span
                                                                class="icon-base ri ri-logout-box-r-line me-1"></span>Clock
                                                            Out
                                                        </a>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success rounded-pill">
                                                            <span class="icon-base ri ri-logout-box-r-line me-1"></span>Out:
                                                            {{ $att->clock_out_time->format('h:i A') }}
                                                        </span>
                                                    @endif
                                                @endif
                                                <form action="{{ route('attendance.email', $att->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-secondary rounded-pill"
                                                        data-bs-toggle="tooltip" title="Email Record">
                                                        <span class="icon-base ri ri-mail-send-line"></span>
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif ($canRecord)
                                            <a href="{{ route('attendance.checkin.show', $schedule) }}"
                                                class="btn btn-primary rounded-pill px-3">
                                                <span class="icon-base ri ri-qr-scan-line me-1"></span>Check In
                                            </a>
                                            @if ($status === 'late' || ($schedule->late_at && $now->gt($schedule->late_at)))
                                                <span class="badge bg-warning-subtle text-warning rounded-pill">Late
                                                    Window</span>
                                            @endif
                                        @elseif ($isCancelled)
                                            <span class="badge bg-secondary rounded-pill">Not Taught</span>
                                        @elseif ($isClosed)
                                            <span class="badge bg-danger-subtle text-danger rounded-pill">Closed</span>
                                        @elseif ($isUpcoming)
                                            <span class="badge bg-primary-subtle text-primary rounded-pill">
                                                <span class="icon-base ri ri-time-line me-1"></span>Upcoming
                                            </span>
                                        @elseif ($isPast)
                                            <span class="badge bg-danger-subtle text-danger rounded-pill">Missed</span>
                                        @else
                                            <span
                                                class="badge bg-secondary-subtle text-secondary rounded-pill">{{ ucfirst($status) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="icon-base ri ri-calendar-line icon-48px text-muted mb-3 d-block"></span>
                        <h6 class="text-muted">No sessions scheduled for today</h6>
                        <small class="text-muted">Your upcoming classes will appear here</small>
                    </div>
                @endif
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- This Week --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0">
                                <span class="icon-base ri ri-calendar-2-line me-2"></span>This Week
                            </h6>
                            <span class="badge bg-label-primary">{{ $metrics['weeklyLabel'] ?? '' }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @php $weekly = $metrics['weeklyAttended'] ?? []; @endphp
                        @if (!empty($weekly))
                            <div class="list-group list-group-flush">
                                @foreach ($weekly as $w)
                                    <div class="list-group-item d-flex align-items-center gap-3 py-2">
                                        <span class="icon-base ri ri-checkbox-circle-fill text-success"></span>
                                        <div class="flex-grow-1">
                                            <small class="fw-medium">{{ $w['name'] }}</small>
                                        </div>
                                        <small class="text-muted">{{ $w['date'] }} · {{ $w['time'] }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <small>No attendance recorded this week yet.</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Monthly Summary --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0">
                                <span class="icon-base ri ri-pie-chart-line me-2"></span>This Month
                            </h6>
                            <span class="badge bg-label-secondary">{{ \Carbon\Carbon::now()->format('F Y') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div class="rounded-3 bg-success-subtle p-3">
                                    <h4 class="mb-1 text-success">{{ $summary['attended'] }}</h4>
                                    <small class="text-success">Attended</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 bg-danger-subtle p-3">
                                    <h4 class="mb-1 text-danger">{{ $summary['missed'] }}</h4>
                                    <small class="text-danger">Missed</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="rounded-3 bg-info-subtle p-3">
                                    <h4 class="mb-1 text-info">{{ $summary['upcoming'] }}</h4>
                                    <small class="text-info">Upcoming</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Course Progress --}}
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <span class="icon-base ri ri-bar-chart-horizontal-line me-2"></span>Course Attendance Progress
                </h6>
            </div>
            <div class="card-body p-0">
                @php $trackTable = $metrics['attendanceTrackTable'] ?? []; @endphp
                @if (!empty($trackTable))
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Course</th>
                                    <th style="min-width:150px;">Progress</th>
                                    <th class="text-center">Attended</th>
                                    <th class="text-center">Missed</th>
                                    <th class="text-center">Cancelled</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trackTable as $t)
                                    <tr>
                                        <td><span class="fw-medium">{{ $t['name'] }}</span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar rounded-pill {{ $t['progress'] >= 75 ? 'bg-success' : ($t['progress'] >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                        role="progressbar" style="width: {{ $t['progress'] }}%"></div>
                                                </div>
                                                <small class="fw-medium text-nowrap">{{ $t['progress'] }}%</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-medium">{{ $t['attended'] }}</span><small
                                                class="text-muted">/{{ $t['taught'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if ($t['missed'] > 0)
                                                <span class="badge bg-danger rounded-pill">{{ $t['missed'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($t['cancelled'] > 0)
                                                <span class="badge bg-secondary rounded-pill">{{ $t['cancelled'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $t['time'] }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <small>No course attendance data available yet.</small>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
