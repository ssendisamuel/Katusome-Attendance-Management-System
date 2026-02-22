@extends('layouts/layoutMaster')

@section('title', 'Session Attendance Report')

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
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start no-print">
                        <div></div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                                <span class="icon-base ri ri-printer-line me-1"></span> Print
                            </button>
                            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                                class="btn btn-sm btn-outline-primary">
                                <span class="icon-base ri ri-download-line me-1"></span> Export CSV
                            </a>
                        </div>
                    </div>
                    <h5 class="mb-1">{{ $schedule->course->name }} ({{ $schedule->course->code }})</h5>
                    <div class="d-flex flex-wrap gap-2 text-muted mb-3">
                        <span class="badge bg-label-primary">{{ $schedule->group->name ?? 'Mixed Group' }}</span>
                        <span><span class="icon-base ri ri-calendar-line"></span>
                            {{ \Carbon\Carbon::parse($schedule->start_at)->format('D, d M Y H:i') }}</span>
                        <span><span class="icon-base ri ri-user-voice-line"></span>
                            {{ $schedule->lecturer->name ?? 'N/A' }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-sm-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="h4 mb-0 text-success">{{ $stats['present'] }}</span>
                                <span class="small text-muted">Present</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="h4 mb-0 text-danger">{{ $stats['absent'] }}</span>
                                <span class="small text-muted">Absent</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="h4 mb-0 text-warning">{{ $stats['late'] }}</span>
                                <span class="small text-muted">Late</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="d-flex flex-column align-items-center">
                                <span class="h4 mb-0">{{ $stats['rate'] }}%</span>
                                <span class="small text-muted">Rate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Attendance Log</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Reg No</th>
                                <th>Time In</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedule->attendanceRecords as $att)
                                <tr>
                                    <td>{{ $att->student->name }}</td>
                                    <td>{{ $att->student->reg_no }}</td>
                                    <td>{{ $att->marked_at ? \Carbon\Carbon::parse($att->marked_at)->format('H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $att->status === 'present' ? 'bg-label-success' : ($att->status === 'absent' ? 'bg-label-danger' : 'bg-label-warning') }}">
                                            {{ ucfirst($att->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($att->lat && $att->lng)
                                            <small class="text-muted"><span class="icon-base ri ri-map-pin-2-line"></span>
                                                {{ number_format($att->lat, 4) }},
                                                {{ number_format($att->lng, 4) }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
