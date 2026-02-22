@extends('layouts/layoutMaster')

@section('title', 'Attendance')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Attendance History</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('lecturer.attendance.today') }}" class="btn btn-outline-primary">Today's Classes</a>
            <a href="{{ route('lecturer.attendance.create') }}" class="btn btn-primary"><span
                    class="icon-base ri ri-add-line me-1"></span>Take Attendance</a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <div class="card-body">
            <div id="attendanceFiltersWrap">
                @include('lecturer.attendance.partials.filters')
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div></div>
                {{-- Exports can be re-enabled if implemented for lecturer --}}
            </div>
        </div>
        <div id="attendancesTable">
            @include('lecturer.attendance.partials.table')
        </div>
    </div>
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
                // Ensure we hit the lecturer route, not admin
                let action = form.action;
                if (!action.includes('lecturer/attendance')) {
                    action = "{{ route('lecturer.attendance.index') }}";
                }
                return action + (params.toString() ? ('?' + params.toString()) : '');
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
                ['group_id', 'lecturer_id', 'status', 'date'].forEach(name => {
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
        })();
    </script>
    @vite(['resources/assets/js/report-export.js'])
@endsection
