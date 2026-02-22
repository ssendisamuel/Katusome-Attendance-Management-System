@extends('layouts/layoutMaster')

@section('title', 'My Schedules')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">My Schedules</h4>
        <a href="{{ route('lecturer.schedules.create') }}" class="btn btn-primary">Add Schedule</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div id="schedulesFiltersWrap">
                @include('lecturer.schedules.partials.filters')
            </div>
        </div>
        <div id="schedulesTable">
            @include('lecturer.schedules.partials.table')
        </div>
    </div>

    <!-- Manage Attendance Modal -->
    <div class="modal fade" id="manageAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Manage Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-alert" class="alert alert-secondary mb-3">
                        Current Status: <strong id="modal-status-text"></strong>
                    </div>

                    <form id="form-settings" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label d-block mb-2">Set Attendance Status</label>
                            <div class="btn-group w-100" role="group" aria-label="Status Toggle">
                                <input type="radio" class="btn-check" name="attendance_status" id="status_open"
                                    value="open" autocomplete="off">
                                <label class="btn btn-outline-success" for="status_open">Active (Open)</label>

                                <input type="radio" class="btn-check" name="attendance_status" id="status_late"
                                    value="late" autocomplete="off">
                                <label class="btn btn-outline-warning" for="status_late">Mark Late</label>

                                <input type="radio" class="btn-check" name="attendance_status" id="status_closed"
                                    value="closed" autocomplete="off">
                                <label class="btn btn-outline-danger" for="status_closed">Closed</label>

                                <input type="radio" class="btn-check" name="attendance_status" id="status_cancelled"
                                    value="cancelled" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="status_cancelled">Cancelled</label>
                            </div>
                        </div>

                        <div id="late-input-wrap">
                            <div class="mb-3">
                                <label class="form-label">Late Threshold (Minutes from NOW)</label>
                                <div class="input-group">
                                    <input type="number" name="late_at_minutes" class="form-control" placeholder="15"
                                        min="1" value="15">
                                    <span class="input-group-text">minutes</span>
                                </div>
                                <div class="form-text">
                                    Students checking in after this time will be marked <strong>LATE</strong>.
                                    <br>If updating an active session, this will extend/reduce the deadline from
                                    <em>now</em>.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Update Session Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusing Admin scripts for simplicity (simplified JS) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var manageModal = document.getElementById('manageAttendanceModal');
            var form = document.getElementById('form-settings');
            var lateWrap = document.getElementById('late-input-wrap');
            var radios = form.querySelectorAll('input[name="attendance_status"]');

            function toggleLateInput() {
                var val = form.querySelector('input[name="attendance_status"]:checked')?.value;
                if (val === 'open') {
                    lateWrap.classList.remove('d-none');
                } else {
                    lateWrap.classList.add('d-none');
                }
            }

            radios.forEach(r => r.addEventListener('change', toggleLateInput));

            manageModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var course = button.getAttribute('data-course');
                var status = button.getAttribute('data-status');
                var actionUrl = button.getAttribute('data-action-url');

                document.getElementById('modalTitle').textContent = 'Manage: ' + course;
                document.getElementById('modal-status-text').textContent = status.charAt(0).toUpperCase() +
                    status.slice(1);

                var alertBox = document.getElementById('modal-alert');
                alertBox.className = 'alert mb-3 ' +
                    (status === 'open' ? 'alert-success' :
                        (status === 'closed' ? 'alert-danger' :
                            (status === 'late' ? 'alert-warning' : 'alert-secondary')));

                form.action = actionUrl;

                var targetStatus = (status === 'scheduled') ? 'open' : status;
                var radio = form.querySelector('input[value="' + targetStatus + '"]');
                if (radio) radio.checked = true;

                toggleLateInput();
            });
        });
    </script>
@endsection
