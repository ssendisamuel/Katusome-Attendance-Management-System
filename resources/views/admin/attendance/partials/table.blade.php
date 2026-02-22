<div class="table-responsive">
    <table id="attendancesTableEl" class="table table-hover">
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" class="form-check-input" id="selectAllAttendance">
                </th>
                <th>Student</th>
                <th>Course</th>
                <th>Status</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th>Location</th>
                <th>Device</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $attendance)
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input attendance-checkbox" value="{{ $attendance->id }}">
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            @if ($attendance->student)
                                <a href="javascript:void(0)"
                                    class="view-student-details fw-bold text-body text-truncate"
                                    data-url="{{ route('admin.students.show', $attendance->student) }}"
                                    style="max-width: 150px;">{{ $attendance->student->name }}</a>
                                <small class="text-muted">
                                    {{ optional($attendance->student->program)->code ?? '—' }}-{{ $attendance->student->year_of_study ?? '?' }}-{{ optional($attendance->schedule->group)->name }}
                                </small>
                            @else
                                <span class="text-muted">Unknown</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">{{ optional($attendance->schedule->course)->name }}</span>
                            <small class="text-muted">
                                @php($sch = $attendance->schedule)
                                @php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
                                @php($names = $hasPivot && $sch && $sch->relationLoaded('lecturers') && $sch->lecturers && $sch->lecturers->count() ? $sch->lecturers->pluck('name')->implode(', ') : optional($sch->lecturer)->name)
                                {{ $names ?: '—' }}
                            </small>
                        </div>
                    </td>
                    <td>
                        @php($statusColor = $attendance->status === 'present' ? 'success' : ($attendance->status === 'late' ? 'warning' : 'danger'))
                        <div class="d-flex flex-column align-items-start">
                            <span class="badge bg-label-{{ $statusColor }}">{{ ucfirst($attendance->status) }}</span>
                            @if ($attendance->is_auto_clocked_out)
                                <span class="badge bg-warning mt-1" style="font-size: 0.65em;">Auto-Closed</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            @if ($attendance->selfie_path)
                                @php($photoUrl = \Illuminate\Support\Facades\Storage::url($attendance->selfie_path))
                                <a href="#" data-photo-url="{{ $photoUrl }}" title="View In-Photo"
                                    class="me-2">
                                    <div class="avatar avatar-md">
                                        <img src="{{ $photoUrl }}" alt="In"
                                            class="rounded-circle border border-2 border-white shadow-sm"
                                            style="object-fit: cover;">
                                    </div>
                                </a>
                            @endif
                            <div>
                                <div class="fw-medium">{{ $attendance->marked_at?->format('H:i') }}</div>
                                <small class="text-muted">{{ $attendance->marked_at?->format('M d') }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if ($attendance->schedule->requires_clock_out)
                            <div class="d-flex align-items-center">
                                @if ($attendance->clock_out_selfie_path)
                                    @php($outPhotoUrl = \Illuminate\Support\Facades\Storage::url($attendance->clock_out_selfie_path))
                                    <a href="#" data-photo-url="{{ $outPhotoUrl }}" title="View Out-Photo"
                                        class="me-2">
                                        <div class="avatar avatar-md">
                                            <img src="{{ $outPhotoUrl }}" alt="Out"
                                                class="rounded-circle border border-2 border-white shadow-sm"
                                                style="object-fit: cover;">
                                        </div>
                                    </a>
                                @endif
                                <div>
                                    @if ($attendance->clock_out_time)
                                        <div class="fw-medium">{{ $attendance->clock_out_time->format('H:i') }}</div>
                                        @if ($attendance->is_auto_clocked_out)
                                            <span
                                                class="badge badge-center rounded-pill bg-label-warning w-px-20 h-px-20"
                                                data-bs-toggle="tooltip" title="Auto Clocked Out (Incomplete)">!</span>
                                        @endif
                                    @else
                                        <span class="badge bg-label-secondary">Pending</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <span class="text-muted small">N/A</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex flex-column small">
                            @if ($attendance->lat && $attendance->lng)
                                <a href="https://maps.google.com/?q={{ $attendance->lat }},{{ $attendance->lng }}"
                                    target="_blank" class="text-body">
                                    <span class="icon-base ri ri-map-pin-line text-primary"></span> In
                                </a>
                            @endif
                            @if ($attendance->clock_out_lat && $attendance->clock_out_lng)
                                <a href="https://maps.google.com/?q={{ $attendance->clock_out_lat }},{{ $attendance->clock_out_lng }}"
                                    target="_blank" class="text-body">
                                    <span class="icon-base ri ri-map-pin-line text-warning"></span> Out
                                </a>
                            @endif
                            @if (!$attendance->lat && !$attendance->clock_out_lat)
                                —
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column small">
                            <span class="text-truncate" style="max-width: 100px;"
                                title="{{ $attendance->ip_address }}">{{ $attendance->ip_address ?? '—' }}</span>
                            <span
                                class="text-muted">{{ $attendance->platform ? ucfirst($attendance->platform) : 'Web' }}</span>
                        </div>
                    </td>
                    <td class="text-end">
                        <form action="{{ route('admin.attendance.destroy', $attendance) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(event, this)" title="Delete">
                                <span class="icon-base ri ri-delete-bin-line"></span>
                            </button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-1 edit-attendance-btn"
                            data-attendance="{{ json_encode([
                                'id' => $attendance->id,
                                'status' => $attendance->status,
                                'marked_at' => $attendance->marked_at ? $attendance->marked_at->format('Y-m-d\TH:i') : '',
                                'clock_out_time' => $attendance->clock_out_time ? $attendance->clock_out_time->format('Y-m-d\TH:i') : '',
                            ]) }}"
                            title="Edit">
                            <span class="icon-base ri ri-pencil-line"></span>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="card-footer border-top">{{ $attendances->appends(request()->query())->links() }}</div>
