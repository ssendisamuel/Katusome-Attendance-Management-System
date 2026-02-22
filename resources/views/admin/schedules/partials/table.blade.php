<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 40px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all-schedules">
                    </div>
                </th>
                <th>Course</th>
                <th>Group</th>
                <th>Lecturer</th>
                <th>Series</th>
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
                        <div class="form-check">
                            <input class="form-check-input schedule-checkbox" type="checkbox"
                                value="{{ $schedule->id }}">
                        </div>
                    </td>
                    <td>{{ optional($schedule->course)->name }}</td>
                    <td>
                        @php
                            $prog = optional($schedule->course)->programs->first();
                            $pCode = $prog ? $prog->code : '';
                            $pYear = $prog ? $prog->pivot->year_of_study : '';
                            $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
                            $yRoman = $pYear ? $romans[$pYear] ?? $pYear : '';
                            $groupName = optional($schedule->group)->name;
                        @endphp
                        @if ($pCode)
                            <span class="fw-bold">{{ $pCode }} {{ $yRoman }}</span> - {{ $groupName }}
                        @else
                            {{ $groupName }}
                        @endif
                    </td>
                    <td>
                        @php
                            $names = null;
                            $hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule');

                            // 1. Pivot (Direct many-to-many)
                            if (
                                $hasPivot &&
                                $schedule->relationLoaded('lecturers') &&
                                $schedule->lecturers->isNotEmpty()
                            ) {
                                $names = $schedule->lecturers->pluck('name')->implode(', ');
                            }
                            // 2. Direct Column (Single)
                            if (empty($names) && $schedule->lecturer) {
                                $names = $schedule->lecturer->name;
                            }
                            // 3. Fallback: Course Lecturers
                            if (empty($names) && $schedule->course && $schedule->course->lecturers->isNotEmpty()) {
                                $names = $schedule->course->lecturers->pluck('name')->implode(', ');
                            }
                        @endphp
                        {{ $names ?: '—' }}
                    </td>
                    <td>{{ optional($schedule->series)->name }}</td>
                    <td>
                        @if ($schedule->is_online)
                            <span class="badge bg-label-info">
                                <span class="ri ri-global-line me-1"></span>Online
                            </span>
                            @if ($schedule->access_code)
                                <br><small class="text-muted">Code:
                                    <strong>{{ $schedule->access_code }}</strong></small>
                            @endif
                        @else
                            <span class="badge bg-label-secondary">
                                <span class="ri ri-building-line me-1"></span>Physical
                            </span>
                            @if ($schedule->venue)
                                <br><small class="text-muted">{{ $schedule->venue->fullName() }}</small>
                            @elseif ($schedule->location)
                                <br><small class="text-muted">{{ $schedule->location }}</small>
                            @endif
                        @endif
                    </td>
                    <td>
                        <?php
                        $status = $schedule->is_cancelled ? 'cancelled' : $schedule->attendance_status ?? 'scheduled';
                        $statusCtx = ['open' => 'success', 'late' => 'warning', 'closed' => 'danger', 'cancelled' => 'danger', 'scheduled' => 'secondary'];
                        ?>
                        <span
                            class="badge bg-label-{{ $statusCtx[$status] ?? 'secondary' }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>{{ $schedule->start_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $schedule->end_at?->format('Y-m-d H:i') }}</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-info me-1 js-manage-status"
                            data-bs-toggle="modal" data-bs-target="#manageAttendanceModal"
                            data-id="{{ $schedule->id }}" data-course="{{ optional($schedule->course)->name }}"
                            data-status="{{ $status }}"
                            data-action-url="{{ route('admin.schedules.status', $schedule->id) }}"
                            title="Manage Status">
                            <span class="ri ri-settings-3-line"></span>
                        </button>
                        <a href="{{ route('admin.schedules.edit', $schedule) }}"
                            class="btn btn-sm btn-icon btn-outline-primary me-1" title="Edit">
                            <span class="ri ri-pencil-line"></span>
                        </a>
                        <form action="{{ route('admin.schedules.destroy', $schedule) }}" method="POST"
                            class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger js-delete-schedule"
                                data-name="{{ optional($schedule->course)->name ?? 'this schedule' }}" title="Delete">
                                <span class="ri ri-delete-bin-line"></span>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="card-footer">{{ $schedules->appends(request()->query())->links() }}</div>
