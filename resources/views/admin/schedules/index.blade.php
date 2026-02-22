@extends('layouts/layoutMaster')

@section('title', 'Schedules')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Schedules</h4>
        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
            <span class="ri ri-add-line me-1"></span> Add Schedule
        </a>
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div id="schedulesFiltersWrap">
                @include('admin.schedules.partials.filters')
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar (hidden by default, shown when items selected) --}}
    <div id="bulk-toolbar" class="card mb-3" style="display: none;">
        <div class="card-body py-2 d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary rounded-pill fs-6" id="selected-count-badge">0 selected</span>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success btn-sm" id="bulk-online-on" title="Set Online">
                    <span class="ri ri-global-line me-1"></span> Online
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-online-off" title="Set Physical">
                    <span class="ri ri-building-line me-1"></span> Physical
                </button>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-warning btn-sm" id="bulk-clockout-on"
                    title="Require Clock Out">
                    <span class="ri ri-time-line me-1"></span> Clock Out ON
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-clockout-off" title="No Clock Out">
                    <span class="ri ri-timer-fill me-1"></span> Clock Out OFF
                </button>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown"
                    title="Change Status">
                    <span class="ri ri-settings-3-line me-1"></span> Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item bulk-status-item" href="#" data-status="scheduled">Scheduled</a></li>
                    <li><a class="dropdown-item bulk-status-item" href="#" data-status="open">Open</a></li>
                    <li><a class="dropdown-item bulk-status-item" href="#" data-status="late">Late</a></li>
                    <li><a class="dropdown-item bulk-status-item" href="#" data-status="closed">Closed</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item bulk-status-item text-danger" href="#"
                            data-status="cancelled">Cancelled</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-danger btn-sm" id="bulk-delete-btn">
                <span class="ri ri-delete-bin-line me-1"></span> Delete Selected
            </button>
        </div>
    </div>

    {{-- Hidden forms for bulk actions --}}
    <form id="bulk-delete-form" action="{{ route('admin.schedules.bulk-destroy') }}" method="POST" style="display:none;">
        @csrf @method('DELETE')
    </form>
    <form id="bulk-update-form" action="{{ route('admin.schedules.bulk-update') }}" method="POST" style="display:none;">
        @csrf @method('PATCH')
    </form>

    {{-- Table Card --}}
    <div class="card">
        <div id="schedule-table-container">
            @include('admin.schedules.partials.table')
        </div>
    </div>

    {{-- Manage Attendance Modal --}}
    <div class="modal fade" id="manageAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="manageAttendanceForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Manage Attendance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-alert" class="alert alert-secondary mb-3">
                            Current Status: <strong id="modal-status-text"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select name="attendance_status" id="modal-status-select" class="form-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="open">Open (Ongoing)</option>
                                <option value="late">Late (Ongoing)</option>
                                <option value="closed">Closed (Ended)</option>
                                <option value="cancelled">Cancelled (Not Taught)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="late-minutes-div" style="display: none;">
                            <label class="form-label">Mark as Late After (Minutes from NOW)</label>
                            <input type="number" name="late_at_minutes" class="form-control" value="15">
                            <small class="text-muted">Students checking in after this duration will be marked
                                'Late'.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ── Toast Notifications ──
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: @json(session('success')),
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
                    text: @json(session('error')),
                    toast: true,
                    position: 'top-end'
                });
            @endif

            // ── DOM References ──
            const filtersWrap = document.getElementById('schedulesFiltersWrap');
            const tableWrap = document.getElementById('schedule-table-container');
            const bulkToolbar = document.getElementById('bulk-toolbar');
            const countBadge = document.getElementById('selected-count-badge');
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            const bulkDeleteForm = document.getElementById('bulk-delete-form');
            const bulkUpdateForm = document.getElementById('bulk-update-form');
            let form = filtersWrap.querySelector('#scheduleFilters');
            let debounceTimer;

            // ── Helpers ──
            function getFilterUrl() {
                if (!form) return window.location.href;
                const params = new URLSearchParams(new FormData(form));
                return form.action + '?' + params.toString();
            }

            function getSelectedIds() {
                return Array.from(document.querySelectorAll('.schedule-checkbox:checked')).map(cb => cb.value);
            }

            // ── Bulk UI ──
            function updateBulkUI() {
                const ids = getSelectedIds();
                const n = ids.length;
                if (bulkToolbar) bulkToolbar.style.display = n > 0 ? 'block' : 'none';
                if (countBadge) countBadge.textContent = n + ' selected';
            }

            // ── Bulk Action Handlers (bound once via event delegation) ──
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function() {
                    const ids = getSelectedIds();
                    if (!ids.length) return;
                    Swal.fire({
                        title: 'Delete ' + ids.length + ' schedules?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete!'
                    }).then(r => {
                        if (r.isConfirmed) {
                            bulkDeleteForm.innerHTML = '@csrf @method('DELETE')';
                            ids.forEach(id => {
                                const inp = document.createElement('input');
                                inp.type = 'hidden';
                                inp.name = 'ids[]';
                                inp.value = id;
                                bulkDeleteForm.appendChild(inp);
                            });
                            bulkDeleteForm.submit();
                        }
                    });
                });
            }

            // Bulk update helper
            function doBulkUpdate(field, value, label) {
                const ids = getSelectedIds();
                if (!ids.length) return;
                Swal.fire({
                    title: 'Update ' + ids.length + ' schedules?',
                    text: 'Set ' + label,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update!'
                }).then(r => {
                    if (r.isConfirmed) {
                        bulkUpdateForm.innerHTML = '@csrf @method('PATCH')';
                        ids.forEach(id => {
                            const inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'ids[]';
                            inp.value = id;
                            bulkUpdateForm.appendChild(inp);
                        });
                        const fld = document.createElement('input');
                        fld.type = 'hidden';
                        fld.name = 'field';
                        fld.value = field;
                        bulkUpdateForm.appendChild(fld);
                        const val = document.createElement('input');
                        val.type = 'hidden';
                        val.name = 'value';
                        val.value = value;
                        bulkUpdateForm.appendChild(val);
                        bulkUpdateForm.submit();
                    }
                });
            }

            document.getElementById('bulk-online-on')?.addEventListener('click', () => doBulkUpdate('is_online',
                '1', 'Online'));
            document.getElementById('bulk-online-off')?.addEventListener('click', () => doBulkUpdate('is_online',
                '0', 'Physical'));
            document.getElementById('bulk-clockout-on')?.addEventListener('click', () => doBulkUpdate(
                'requires_clock_out', '1', 'Clock Out ON'));
            document.getElementById('bulk-clockout-off')?.addEventListener('click', () => doBulkUpdate(
                'requires_clock_out', '0', 'Clock Out OFF'));

            // Bulk status dropdown
            document.addEventListener('click', function(e) {
                const item = e.target.closest('.bulk-status-item');
                if (!item) return;
                e.preventDefault();
                const status = item.dataset.status;
                doBulkUpdate('attendance_status', status, 'Status → ' + status.charAt(0).toUpperCase() +
                    status.slice(1));
            });

            // ── Event Delegation for checkboxes & delete (bound once, works after AJAX) ──
            document.addEventListener('change', function(e) {
                if (e.target.matches('#select-all-schedules')) {
                    document.querySelectorAll('.schedule-checkbox').forEach(cb => cb.checked = e.target
                        .checked);
                    updateBulkUI();
                }
                if (e.target.matches('.schedule-checkbox')) {
                    updateBulkUI();
                }
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.js-delete-schedule');
                if (!btn) return;
                e.preventDefault();
                const delForm = btn.closest('form');
                const name = btn.dataset.name || 'this schedule';
                Swal.fire({
                    title: 'Delete ' + name + '?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete'
                }).then(r => {
                    if (r.isConfirmed) delForm.submit();
                });
            });

            // ── AJAX: Update Table ──
            function updateTable(url) {
                const target = url || getFilterUrl();
                fetch(target, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP error ' + r.status);
                        return r.text();
                    })
                    .then(html => {
                        tableWrap.innerHTML = html;
                        attachPagination();
                        updateBulkUI();
                        history.replaceState(null, '', target);
                    })
                    .catch(err => console.error('updateTable error:', err));
            }

            function attachPagination() {
                tableWrap.querySelectorAll('.pagination a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        updateTable(this.href);
                    });
                });
            }
            attachPagination();

            // ── AJAX: Refresh Filter Dropdowns (returns Promise) ──
            function refreshFilters(urlOverride) {
                const targetUrl = urlOverride || getFilterUrl();
                const urlObj = new URL(targetUrl, window.location.origin);
                urlObj.searchParams.set('fragment', 'filters');

                return fetch(urlObj.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP error ' + r.status);
                        return r.text();
                    })
                    .then(html => {
                        filtersWrap.innerHTML = html;
                        let newForm = filtersWrap.querySelector('#scheduleFilters');
                        if (newForm) {
                            form = newForm;
                            bindFilterEvents();
                        } else {
                            console.error('refreshFilters: form not found in response');
                        }
                    })
                    .catch(err => console.error('refreshFilters error:', err));
            }

            // ── Filter Event Binding ──
            function bindFilterEvents() {
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    updateTable();
                });

                // High-level (cascading) filters: refresh dropdowns + table
                ['academic_semester_id', 'program_id', 'year_of_study', 'course_id'].forEach(name => {
                    const el = form.querySelector('[name="' + name + '"]');
                    if (el) {
                        el.addEventListener('change', function() {
                            refreshFilters().then(() => updateTable());
                        });
                    }
                });

                // Low-level filters: just update table
                ['group_id', 'lecturer_id', 'date_from', 'date_to', 'per_page'].forEach(name => {
                    const el = form.querySelector('[name="' + name + '"]');
                    if (el) {
                        el.addEventListener('change', function() {
                            updateTable();
                        });
                    }
                });

                // Search with debounce
                const searchInput = document.getElementById('scheduleSearch');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => updateTable(), 400);
                    });
                }

                // Reset link
                const resetLink = form.querySelector('a.btn-outline-secondary');
                if (resetLink) {
                    resetLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.href;
                        form.reset();
                        refreshFilters(url).then(() => updateTable(url));
                    });
                }
            }
            bindFilterEvents();

            // ── Modal Handler ──
            var manageModal = document.getElementById('manageAttendanceModal');
            var modalForm = document.getElementById('manageAttendanceForm');
            var statusSelect = document.getElementById('modal-status-select');
            var lateDiv = document.getElementById('late-minutes-div');

            function toggleLateInput() {
                if (lateDiv) lateDiv.style.display = statusSelect && statusSelect.value === 'open' ? 'block' :
                    'none';
            }
            if (statusSelect) statusSelect.addEventListener('change', toggleLateInput);

            if (manageModal) {
                manageModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var course = button.getAttribute('data-course');
                    var status = button.getAttribute('data-status');
                    var actionUrl = button.getAttribute('data-action-url');

                    document.getElementById('modalTitle').textContent = 'Manage: ' + (course || 'Schedule');
                    document.getElementById('modal-status-text').textContent = status ? status.charAt(0)
                        .toUpperCase() + status.slice(1) : 'Unknown';

                    var alertBox = document.getElementById('modal-alert');
                    alertBox.className = 'alert mb-3 ' +
                        (status === 'open' ? 'alert-success' : status === 'closed' ? 'alert-danger' :
                            status === 'late' ? 'alert-warning' : 'alert-secondary');

                    modalForm.action = actionUrl;
                    statusSelect.value = (status === 'scheduled') ? 'open' : status;
                    toggleLateInput();
                });
            }

        }); // DOMContentLoaded
    </script>
@endsection
@endsection
