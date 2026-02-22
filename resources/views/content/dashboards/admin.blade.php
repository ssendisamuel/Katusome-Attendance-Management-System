@php($configData = Helper::appClasses())

@extends('layouts/layoutMaster')

@section('title', 'Admin Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Attendance Trend Chart
            const attendanceChartEl = document.querySelector('#attendanceTrendChart');
            if (attendanceChartEl) {
                const chartConfig = {
                    chart: {
                        height: 300,
                        type: 'area',
                        parentHeightOffset: 0,
                        toolbar: {
                            show: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: 2,
                        curve: 'smooth'
                    },
                    series: [{
                            name: 'Present',
                            data: @json($seriesPresent)
                        },
                        {
                            name: 'Absent',
                            data: @json($seriesAbsent)
                        },
                        {
                            name: 'Late',
                            data: @json($seriesLate)
                        }
                    ],
                    xaxis: {
                        categories: @json($chartDates),
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(val) {
                                return val.toFixed(0);
                            }
                        }
                    },
                    colors: [config.colors.success, config.colors.danger, config.colors.warning],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.9,
                            stops: [0, 90, 100]
                        }
                    },
                    grid: {
                        borderColor: config.colors.borderColor,
                        strokeDashArray: 3,
                        xaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + " Students"
                            }
                        }
                    }
                };
                const attendanceChart = new ApexCharts(attendanceChartEl, chartConfig);
                attendanceChart.render();
            }
        });
    </script>
@endsection

@section('content')
    <div class="row gy-6">

        <!-- Welcome / Stats Cards -->
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="mb-0">Overview</h4>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Students -->
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('admin.students.index') }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-4">
                                    <span class="badge bg-label-primary rounded p-2">
                                        <span class="icon-base ri ri-group-line icon-24px"></span>
                                    </span>
                                    <div class="content-right">
                                        <h5 class="mb-0 text-heading">{{ $studentsCount }}</h5>
                                        <small class="text-muted">Total Students</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Lecturers -->
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('admin.lecturers.index') }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-4">
                                    <span class="badge bg-label-warning rounded p-2">
                                        <span class="icon-base ri ri-user-star-line icon-24px"></span>
                                    </span>
                                    <div class="content-right">
                                        <h5 class="mb-0 text-heading">{{ $lecturersCount }}</h5>
                                        <small class="text-muted">Active Lecturers</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Courses -->
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('admin.courses.index') }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-4">
                                    <span class="badge bg-label-info rounded p-2">
                                        <span class="icon-base ri ri-book-2-line icon-24px"></span>
                                    </span>
                                    <div class="content-right">
                                        <h5 class="mb-0 text-heading">{{ $coursesCount }}</h5>
                                        <small class="text-muted">Courses</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Programs -->
                        <div class="col-sm-6 col-xl-3">
                            <a href="{{ route('admin.programs.index') }}" class="text-decoration-none">
                                <div class="d-flex align-items-center gap-4">
                                    <span class="badge bg-label-success rounded p-2">
                                        <span class="icon-base ri ri-apps-2-line icon-24px"></span>
                                    </span>
                                    <div class="content-right">
                                        <h5 class="mb-0 text-heading">{{ $programsCount }}</h5>
                                        <small class="text-muted">Programs</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Reports Shortcut -->
                        <div class="col-12 mt-4 pt-2 border-top">
                            <a href="{{ route('admin.reports.daily') }}" class="btn btn-label-primary w-100">
                                <span class="icon-base ri ri-file-chart-line me-1"></span> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Trend Chart & Today's Stats -->
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0">Attendance Trends (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <div id="attendanceTrendChart"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Today's Activity</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.schedules.index', ['date' => now()->format('Y-m-d')]) }}"
                        class="text-decoration-none">
                        <div class="d-flex justify-content-between align-items-center mb-6">
                            <h6 class="mb-0 text-heading">Classes Scheduled</h6>
                            <h4 class="mb-0 text-primary">{{ $todaysClasses }}</h4>
                        </div>
                    </a>
                    <a href="{{ route('admin.schedules.index', ['date' => now()->format('Y-m-d'), 'attendance_filter' => 'pending']) }}"
                        class="text-decoration-none">
                        <div class="d-flex justify-content-between align-items-center mb-6">
                            <h6 class="mb-0 text-heading">Pending Attendance</h6>
                            <h4 class="mb-0 text-danger">{{ $pendingAttendance }}</h4>
                        </div>
                    </a>
                    <hr>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar avatar-sm">
                            <div class="avatar-initial rounded bg-label-success">
                                <span class="icon-base ri ri-check-line icon-18px"></span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Present</h6>
                            <small>Marked Today</small>
                        </div>
                        <h5 class="mb-0 text-success">{{ $presentToday }}</h5>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar avatar-sm">
                            <div class="avatar-initial rounded bg-label-danger">
                                <span class="icon-base ri ri-close-line icon-18px"></span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Absent</h6>
                            <small>Marked Today</small>
                        </div>
                        <h5 class="mb-0 text-danger">{{ $absentToday }}</h5>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar avatar-sm">
                            <div class="avatar-initial rounded bg-label-warning">
                                <span class="icon-base ri ri-time-line icon-18px"></span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">Late</h6>
                            <small>Marked Today</small>
                        </div>
                        <h5 class="mb-0 text-warning">{{ $lateToday }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- NEW: At-Risk Students Row -->
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">At-Risk Students</h5>
                    <small class="text-muted">Lowest Attendance Rates</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Present</th>
                                <th>Total</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atRiskStudents as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-heading">{{ $student->name }}</span>
                                            <small class="text-muted">{{ $student->student_no }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $student->present_records }}</td>
                                    <td>{{ $student->total_records }}</td>
                                    <td>
                                        <span class="badge bg-label-danger">{{ $student->attendance_rate }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <p class="mb-0 text-muted">No at-risk students found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Classes</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Group</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentClasses as $schedule)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">{{ $schedule->course->name }}</span>
                                            <small class="text-muted">{{ $schedule->course->code }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $schedule->lecturer->user->name ?? 'N/A' }}</td>
                                    <td>{{ $schedule->group->name }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($schedule->start_at)->format('D, M d H:i') }}
                                        -
                                        {{ \Carbon\Carbon::parse($schedule->end_at)->format('H:i') }}
                                    </td>
                                    <td>
                                        @if ($schedule->is_cancelled)
                                            <span class="badge bg-label-secondary">Not Taught</span>
                                        @elseif ($schedule->attendanceRecords->count() > 0)
                                            <span class="badge bg-label-success">Marked</span>
                                        @elseif (\Carbon\Carbon::parse($schedule->end_at)->isPast())
                                            <span class="badge bg-label-danger">Unmarked</span>
                                        @elseif (\Carbon\Carbon::parse($schedule->start_at)->isFuture())
                                            <span class="badge bg-label-warning">Upcoming</span>
                                        @else
                                            <span class="badge bg-label-primary">Ongoing</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No recent classes found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
