@extends('layouts/layoutMaster')

@section('title', 'Schedule Series')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Schedule Series</h4>
        <div>
            <button type="button" class="btn btn-danger me-2" id="bulk-delete-btn" style="display: none;">
                <i class="ri-delete-bin-line me-1"></i> Delete Selected (<span id="selected-count">0</span>)
            </button>
            <button type="button" class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                <i class="ri-calendar-check-line me-1"></i> Bulk Generate
            </button>
            <a href="{{ route('admin.series.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i> New Series
            </a>
        </div>
    </div>

    <!-- Bulk Delete Form -->
    <form id="bulk-delete-form" action="{{ route('admin.series.bulk-destroy') }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="ids[]" id="bulk-delete-ids">
    </form>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="select-all">
                            </div>
                        </th>
                        <th>Name</th>
                        <th>Course / Group</th>
                        <th>Schedule</th>
                        <th>Details</th>
                        <th>Generated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($series as $s)
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input series-checkbox" type="checkbox"
                                        value="{{ $s->id }}">
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium">{{ $s->name }}</span>
                                <small class="d-block text-muted">{{ optional($s->lecturer)->name ?? '—' }}</small>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-label-primary mb-1">{{ optional($s->course)->code }}</span>
                                    <small>{{ optional($s->group)->name }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <small><i class="ri-calendar-line me-1"></i>
                                        {{ \Carbon\Carbon::parse($s->start_date)->format('M d') }} -
                                        {{ $s->end_date ? \Carbon\Carbon::parse($s->end_date)->format('M d, Y') : 'Indefinite' }}
                                    </small>
                                    <small><i class="ri-time-line me-1"></i>
                                        {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                                    </small>
                                    <small>
                                        @foreach ($s->days_of_week ?? [] as $day)
                                            <span class="badge bg-label-secondary">{{ strtoupper($day) }}</span>
                                        @endforeach
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small class="text-truncate" style="max-width: 150px;"
                                        title="{{ $s->location }}">{{ $s->location }}</small>
                                    @if ($s->is_online)
                                        <span class="badge bg-label-success mt-1">Online</span>
                                    @endif
                                    @if ($s->is_recurring)
                                        <span class="badge bg-label-info mt-1">Recurring</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">
                                    {{ $s->schedules_count ?? $s->schedules()->count() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal"
                                    data-bs-target="#generateModal{{ $s->id }}" title="Generate Schedules">
                                    Generate
                                </button>
                                <a href="{{ route('admin.series.edit', $s) }}" class="btn btn-sm btn-info me-1"
                                    title="Edit">
                                    Edit
                                </a>
                                <form action="{{ route('admin.series.destroy', $s) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger js-delete-series" title="Delete"
                                        data-name="{{ $s->name }}">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Generate Modal -->
                        <div class="modal fade" id="generateModal{{ $s->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.series.generate-schedules', $s) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Generate Schedules for "{{ $s->name }}"</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>This will create individual schedule entries based on the series
                                                configuration.</p>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="overwrite"
                                                    value="1" id="overwrite{{ $s->id }}">
                                                <label class="form-check-label" for="overwrite{{ $s->id }}">
                                                    Overwrite existing schedules for this series?
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="skip_overlaps"
                                                    value="1" id="skip{{ $s->id }}" checked>
                                                <label class="form-check-label" for="skip{{ $s->id }}">
                                                    Skip if overlaps with existing schedules for this group?
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Generate</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No schedule series found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $series->links() }}
        </div>
    </div>

    <!-- Bulk Selection & SweetAlert Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "{{ session('success') }}",
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ session('error') }}",
                    toast: true,
                    position: 'top-end'
                });
            @endif

            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.series-checkbox');
            const bulkBtn = document.getElementById('bulk-delete-btn');
            const selectedCount = document.getElementById('selected-count');
            const bulkForm = document.getElementById('bulk-delete-form');
            const bulkIdsInput = document.getElementById('bulk-delete-ids');

            function updateBulkUI() {
                const checked = document.querySelectorAll('.series-checkbox:checked');
                selectedCount.textContent = checked.length;
                bulkBtn.style.display = checked.length > 0 ? 'inline-block' : 'none';
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateBulkUI();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateBulkUI);
            });

            if (bulkBtn) {
                bulkBtn.addEventListener('click', function() {
                    const count = selectedCount.textContent;
                    Swal.fire({
                        title: 'Delete ' + count + ' items?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete selected!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const ids = Array.from(document.querySelectorAll(
                                '.series-checkbox:checked')).map(cb => cb.value);
                            // Clear and repopulate form
                            bulkForm.innerHTML = '@csrf @method('DELETE')';
                            ids.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'ids[]';
                                input.value = id;
                                bulkForm.appendChild(input);
                            });
                            bulkForm.submit();
                        }
                    });
                });
            }
        });
    </script>

    <!-- Bulk Generate Modal -->
    <div class="modal fade" id="bulkGenerateModal" tabindex="-1" aria-labelledby="bulkGenerateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkGenerateModalLabel">Generate All Series (Current Term)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.series.generate-all') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="bulkOverwrite"
                                name="overwrite">
                            <label class="form-check-label" for="bulkOverwrite">Overwrite existing schedules for affected
                                series</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="bulkSkipOverlaps"
                                name="skip_overlaps">
                            <label class="form-check-label" for="bulkSkipOverlaps">Skip if overlapping schedule exists for
                                same group</label>
                        </div>
                        <div class="alert alert-info mt-3">
                            Only series covering today are processed. Uses each series' days/time and date range.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-label-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success px-2">Generate All</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @isset($audits)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Generation Audit Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Admin</th>
                                <th>Series</th>
                                <th>Overwrite</th>
                                <th>Skip Overlaps</th>
                                <th>Generated Dates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audits as $audit)
                                <tr>
                                    <td>{{ $audit->created_at }}</td>
                                    <td>{{ optional($audit->user)->name ?? 'Unknown' }}</td>
                                    <td>{{ optional($audit->series)->name ?? '#' }}</td>
                                    <td>{{ $audit->overwrite ? 'Yes' : 'No' }}</td>
                                    <td>{{ $audit->skip_overlaps ? 'Yes' : 'No' }}</td>
                                    <td>
                                        @php $dates = $audit->generated_dates ?? []; @endphp
                                        {{ is_array($dates) ? implode(', ', $dates) : $dates }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No generation activity yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $audits->links() }}</div>
        </div>
    @endisset
@endsection

@section('page-script')
    <script>
        (function() {
            document.querySelectorAll('.js-delete-series').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('form');
                    const name = this.dataset.name || 'this series';
                    if (window.Swal && window.Swal.fire) {
                        window.Swal.fire({
                            title: 'Delete ' + name + '?',
                            text: 'This action cannot be undone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete',
                            cancelButtonText: 'Cancel'
                        }).then(function(result) {
                            if (result.isConfirmed) form.submit();
                        });
                    } else {
                        if (confirm('Delete ' + name + '?')) form.submit();
                    }
                });
            });
        })();
    </script>
@endsection
