<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Group</th>
                <th>Location</th>
                <th>Status</th>
                <th>Start</th>
                <th>End</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedules as $schedule)
                <tr>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">{{ optional($schedule->course)->name }}</span>
                            <small class="text-muted">{{ optional($schedule->course)->code }}</small>
                        </div>
                    </td>
                    <td>{{ optional($schedule->group)->name }}</td>
                    <td>{{ $schedule->location }}
                        @if ($schedule->is_online)
                            <span class="badge bg-label-info ms-1">Online</span>
                        @endif
                    </td>
                    <td>
                        <?php
                        // Status logic
                        $status = $schedule->is_cancelled ? 'cancelled' : $schedule->attendance_status ?? 'scheduled';
                        $statusCtx = ['open' => 'success', 'late' => 'warning', 'closed' => 'danger', 'cancelled' => 'danger', 'scheduled' => 'secondary'];
                        ?>
                        <span
                            class="badge bg-label-{{ $statusCtx[$status] ?? 'secondary' }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>{{ $schedule->start_at?->format('D, M d H:i') }}</td>
                    <td>{{ $schedule->end_at?->format('H:i') }}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-info me-1 js-manage-status"
                            data-bs-toggle="modal" data-bs-target="#manageAttendanceModal" data-id="{{ $schedule->id }}"
                            data-course="{{ optional($schedule->course)->name }}" data-status="{{ $status }}"
                            data-action-url="{{ route('lecturer.schedules.status', $schedule->id) }}">
                            Status
                        </button>

                        <a href="{{ route('lecturer.attendance.edit', $schedule->id) }}"
                            class="btn btn-sm btn-outline-success me-1">Mark</a>

                        <a href="{{ route('lecturer.schedules.edit', $schedule) }}"
                            class="btn btn-sm btn-outline-primary me-1">Edit</a>

                        <form action="{{ route('lecturer.schedules.destroy', $schedule) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger js-delete-schedule"
                                data-name="{{ optional($schedule->course)->name ?? 'this schedule' }}"
                                onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="card-footer">{{ $schedules->appends(request()->query())->links() }}</div>
