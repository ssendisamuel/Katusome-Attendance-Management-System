<div class="modal fade" id="attendanceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="attendanceEditForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editAttendanceId">

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="editAttendanceStatus" required>
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="excused">Excused</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Clock In Time</label>
                    <input type="datetime-local" class="form-control" name="marked_at" id="editAttendanceMarkedAt"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Clock Out Time</label>
                    <input type="datetime-local" class="form-control" name="clock_out_time" id="editAttendanceClockOut">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
