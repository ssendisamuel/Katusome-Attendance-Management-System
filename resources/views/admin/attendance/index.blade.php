@extends('layouts/layoutMaster')

@section('title', 'Attendance')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Attendance Records</h4>
        <a href="{{ route('admin.attendance.create') }}" class="btn btn-primary"><span
                class="icon-base ri ri-add-line me-1"></span>Add Attendance</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <div class="card-body">
            <div id="attendanceFiltersWrap">
                @include('admin.attendance.partials.filters')
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-export="print"><span
                            class="icon-base ri ri-printer-line me-1"></span>Print</button>
                    <button type="button" class="btn btn-outline-danger" data-export="pdf"
                        data-export-target="#attendancesTableEl" data-title="Attendance Records"
                        data-filename="Attendance_Records_{{ now()->toDateString() }}.pdf" data-header="Katusome Institute"
                        data-footer-left="Katusome • Attendance"
                        data-json-url="{{ route('admin.attendance.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span
                            class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
                    <button type="button" class="btn btn-outline-success" data-export="excel"
                        data-export-target="#attendancesTableEl" data-title="Attendance Records"
                        data-filename="Attendance_Records_{{ now()->toDateString() }}.xlsx"
                        data-json-url="{{ route('admin.attendance.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span
                            class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
                </div>
            </div>
            <!-- Bulk Actions Toolbar (Moved) -->
            <div id="bulkActionsToolbar"
                class="d-none mt-3 p-3 bg-light rounded border d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="fw-bold me-2"><span id="selectedCount">0</span> selected</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="clearSelection">Clear</button>
                </div>
                <form id="bulkActionForm" action="{{ route('admin.attendance.bulk-action') }}" method="POST"
                    class="d-flex align-items-center gap-2">
                    @csrf
                    <input type="hidden" name="ids" id="bulkActionIds">
                    <select name="action" class="form-select form-select-sm w-auto" required>
                        <option value="" selected disabled>Select Action</option>
                        <option value="delete">Delete Selected</option>
                        <option value="mark_present">Mark Present</option>
                        <option value="mark_late">Mark Late</option>
                        <option value="mark_absent">Mark Absent</option>
                        <option value="mark_excused">Mark Excused</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </form>
            </div>
        </div>
        <div id="attendancesTable">
            @include('admin.attendance.partials.table')
        </div>
    </div>
    @include('admin.attendance.partials.edit-modal')
    <!-- Photo Preview Modal -->
    <div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="photoPreviewImg" src="" alt="Attendance photo" class="img-fluid" />
                </div>
                <div class="modal-footer justify-content-center">
                    <a id="photoDownloadBtn" href="#" class="btn btn-sm btn-primary" download>Download</a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentDetailsModalBody">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Loading details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            const filtersWrap = document.getElementById('attendanceFiltersWrap');
            const tableWrap = document.getElementById('attendancesTable');
            const photoModalEl = document.getElementById('photoPreviewModal');
            const photoModalImg = document.getElementById('photoPreviewImg');
            const photoModalDownloadBtn = document.getElementById('photoDownloadBtn');

            const studentModalEl = document.getElementById('studentDetailsModal');
            const studentModalBody = document.getElementById('studentDetailsModalBody');

            let bootstrapPhotoModal, bootstrapStudentModal;
            let form = filtersWrap.querySelector('#attendanceFilters');
            let debounceTimer;

            function urlFromForm() {
                const params = new URLSearchParams(new FormData(form));
                return form.action + (params.toString() ? ('?' + params.toString()) : '');
            }

            function attachPagination() {
                tableWrap.querySelectorAll('.pagination a').forEach(a => {
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        updateTable(this.href);
                    });
                });

                // Bind photo thumbnail clicks
                tableWrap.querySelectorAll('[data-photo-url]').forEach(el => {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.getAttribute('data-photo-url');
                        if (!url) return;
                        photoModalImg.src = url;
                        if (photoModalDownloadBtn) {
                            photoModalDownloadBtn.href = url;
                            const filename = (url.split('?')[0].split('/').pop()) || 'photo.jpg';
                            photoModalDownloadBtn.setAttribute('download', filename);
                        }
                        if (!bootstrapPhotoModal) {
                            bootstrapPhotoModal = new(window.bootstrap?.Modal || function() {})(
                                photoModalEl);
                        }
                        if (bootstrapPhotoModal && bootstrapPhotoModal.show) {
                            bootstrapPhotoModal.show();
                        } else {
                            photoModalEl.classList.add('show');
                            photoModalEl.style.display = 'block';
                        }
                    });
                });

                // Bind Student Name Clicks
                tableWrap.querySelectorAll('.view-student-details').forEach(el => {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.dataset.url;
                        if (!url) return;

                        // Show Modal with loading
                        studentModalBody.innerHTML =
                            '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading details...</p></div>';

                        if (!bootstrapStudentModal) {
                            bootstrapStudentModal = new(window.bootstrap?.Modal || function() {})(
                                studentModalEl);
                        }
                        if (bootstrapStudentModal && bootstrapStudentModal.show) {
                            bootstrapStudentModal.show();
                        }

                        // Fetch Content
                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(r => r.text())
                            .then(html => {
                                studentModalBody.innerHTML = html;
                            })
                            .catch(e => {
                                console.error(e);
                                studentModalBody.innerHTML =
                                    '<div class="text-danger text-center p-3">Failed to load details.</div>';
                            });
                    });
                });

                // Bind Edit Buttons
                tableWrap.querySelectorAll('.edit-attendance-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const data = JSON.parse(this.dataset.attendance);
                        const modalEl = document.getElementById('attendanceEditModal');
                        const form = document.getElementById('attendanceEditForm');

                        // Set form action - Assuming a route like admin.attendance.update exists or using a generic one
                        // Since AttendanceController is a resource controller, we use the update route: /admin/attendance/{id}
                        form.action = `/admin/attendance/${data.id}`;

                        document.getElementById('editAttendanceId').value = data.id;
                        document.getElementById('editAttendanceStatus').value = data.status;
                        document.getElementById('editAttendanceMarkedAt').value = data.marked_at;
                        document.getElementById('editAttendanceClockOut').value = data.clock_out_time;

                        const modal = new(window.bootstrap?.Modal || function() {})(modalEl);
                        modal.show();
                    });
                });
            }

            function updateTable(url) {
                const target = url || urlFromForm();
                fetch(target, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        tableWrap.innerHTML = html;
                        attachPagination();
                        history.replaceState(null, '', target);
                    })
                    .catch(console.error);
            }

            // ... rest of logic ... (keeping it standard)
            function refreshFilters() {
                const params = new URLSearchParams(new FormData(form));
                params.set('fragment', 'filters');
                const target = form.action + '?' + params.toString();
                fetch(target, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        filtersWrap.innerHTML = html;
                        form = filtersWrap.querySelector('#attendanceFilters');
                        bindFilterEvents();
                    })
                    .catch(console.error);
            }

            attachPagination();

            function bindFilterEvents() {
                const searchInput = document.getElementById('attendanceSearch');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    updateTable();
                });

                // Fields that affect other dropdowns (Cascading Filters)
                ['academic_semester_id', 'program_id', 'course_id'].forEach(name => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) el.addEventListener('change', () => {
                        refreshFilters();
                        updateTable();
                    });
                });

                // Fields that just filter the table
                ['group_id', 'lecturer_id', 'status', 'date', 'per_page'].forEach(name => {
                    const el = form.querySelector(`[name="${name}"]`);
                    if (el) el.addEventListener('change', () => updateTable());
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(function() {
                            updateTable();
                        }, 400);
                    });
                }

                const resetLink = form.querySelector('a.btn.btn-outline-secondary');
                if (resetLink) {
                    resetLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        form.reset();
                        refreshFilters();
                        updateTable(this.href);
                    });
                }
            }

            bindFilterEvents();

            // Bulk Action Logic
            const selectAllCb = document.getElementById('selectAllAttendance');
            const toolbar = document.getElementById('bulkActionsToolbar');
            const selectedCountSpan = document.getElementById('selectedCount');
            const bulkIdsInput = document.getElementById('bulkActionIds');
            const bulkForm = document.getElementById('bulkActionForm');

            function updateBulkToolbar() {
                const checkboxes = document.querySelectorAll('.attendance-checkbox:checked');
                const count = checkboxes.length;
                selectedCountSpan.textContent = count;

                if (count > 0) {
                    toolbar.classList.remove('d-none');
                    // Ensure d-flex is present for layout if it was conditionally removed (though it shouldn't be)
                    toolbar.classList.add('d-flex');
                    const ids = Array.from(checkboxes).map(cb => cb.value);
                    bulkIdsInput.value = JSON.stringify(ids);
                } else {
                    toolbar.classList.add('d-none');
                    toolbar.classList.remove('d-flex'); // detailed hiding
                    bulkIdsInput.value = '';
                }
            }

            // We need to re-bind events after table update via AJAX
            function bindCheckboxEvents() {
                const selectAll = document.getElementById('selectAllAttendance');
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.attendance-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                        updateBulkToolbar();
                    });
                }

                const checkboxes = document.querySelectorAll('.attendance-checkbox');
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', updateBulkToolbar);
                });
            }

            // Initial bind
            bindCheckboxEvents();

            // Clear selection
            document.getElementById('clearSelection')?.addEventListener('click', function() {
                if (selectAllCb) selectAllCb.checked = false;
                document.querySelectorAll('.attendance-checkbox').forEach(cb => cb.checked = false);
                updateBulkToolbar();
            });

            // Hook into existing updateTable to re-bind checkboxes
            const originalUpdateTable = updateTable;
            updateTable = function(url) {
                // We can't easily hook the promise here without rewriting updateTable,
                // but we can assume tableWrap innerHTML changes.
                // Let's redefine updateTable locally to include re-binding.
                const target = url || urlFromForm();
                fetch(target, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        tableWrap.innerHTML = html;
                        attachPagination();
                        bindCheckboxEvents(); // Re-bind checkboxes
                        updateBulkToolbar(); // Update toolbar state after table refresh
                        history.replaceState(null, '', target);
                    })
                    .catch(console.error);
            };

            // Handle Bulk Submit with SweetAlert
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You are about to update " + document.getElementById('selectedCount')
                            .textContent + " records.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, proceed!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                });
            }

            // Expose a global delete handler for Single Delete buttons
            window.confirmDelete = function(e, btn) {
                e.preventDefault();
                const form = btn.closest('form');
                Swal.fire({
                    title: 'Delete Attendance?',
                    text: "This action cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            };

        })();
    </script>
    @vite(['resources/assets/js/report-export.js'])
@endsection
