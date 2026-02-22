<?php $__env->startSection('title', 'Course Leader Dashboard'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/select2/select2.scss']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/flatpickr/flatpickr.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/select2/select2.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/flatpickr/flatpickr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <style>
        .flatpickr-calendar {
            z-index: 9999 !important;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-md-12">
            <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">Dashboard /</span> Course Leader Panel
            </h4>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">My Assigned Cohorts</h5>
                            <?php $__currentLoopData = $cohorts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cohort): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="badge bg-label-primary me-2 mb-2">
                                    <?php echo e($cohort->program->name); ?> | Year <?php echo e($cohort->year_of_study); ?> |
                                    <?php echo e($cohort->group->name); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                $scheduleGroups = [
                    "Today's Classes" => $todaySchedules,
                    'This Week' => $thisWeekSchedules,
                    'Upcoming Classes' => $upcomingSchedules,
                ];
            ?>

            <?php $__currentLoopData = $scheduleGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $title => $schedulesGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card mb-4">
                    <h5 class="card-header border-bottom"><?php echo e($title); ?></h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date / Time</th>
                                    <th>Course</th>
                                    <th>Lecturer</th>
                                    <th>Venue / Mode</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php $__empty_1 = true; $__currentLoopData = $schedulesGroup; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($schedule->start_at->format('M d, Y')); ?></strong><br>
                                            <small><?php echo e($schedule->start_at->format('H:i')); ?> -
                                                <?php echo e($schedule->end_at->format('H:i')); ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?php echo e($schedule->course->name); ?></span><br>
                                            <small class="text-muted"><?php echo e($schedule->group->name); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                                $names = null;
                                                $hasPivot = \Illuminate\Support\Facades\Schema::hasTable(
                                                    'lecturer_schedule',
                                                );
                                                if (
                                                    $hasPivot &&
                                                    $schedule->relationLoaded('lecturers') &&
                                                    $schedule->lecturers->isNotEmpty()
                                                ) {
                                                    $names = $schedule->lecturers->pluck('name')->implode(', ');
                                                }
                                                if (empty($names) && $schedule->lecturer) {
                                                    $names =
                                                        $schedule->lecturer->user?->name ?? $schedule->lecturer->name;
                                                }
                                                if (
                                                    empty($names) &&
                                                    $schedule->course &&
                                                    $schedule->course->lecturers->isNotEmpty()
                                                ) {
                                                    $names = $schedule->course->lecturers
                                                        ->map(fn($l) => $l->user?->name ?? $l->name)
                                                        ->implode(', ');
                                                }
                                            ?>
                                            <?php echo e($names ?: 'Not Assigned'); ?>

                                        </td>
                                        <td>
                                            <?php if($schedule->is_online): ?>
                                                <span class="badge bg-label-info mb-1"><i class="ri-global-line me-1"></i>
                                                    Online</span><br>
                                                <small class="user-select-all">Code: <?php echo e($schedule->access_code); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-label-secondary mb-1"><i
                                                        class="ri-building-line me-1"></i> Physical</span><br>
                                                <small><?php echo e($schedule->venue?->name ?? ($schedule->location ?? 'TBA')); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $status = $schedule->is_cancelled
                                                    ? 'cancelled'
                                                    : $schedule->attendance_status ?? 'scheduled';
                                                $statusCtx = [
                                                    'open' => 'success',
                                                    'late' => 'warning',
                                                    'closed' => 'danger',
                                                    'cancelled' => 'danger',
                                                    'scheduled' => 'secondary',
                                                ];
                                            ?>
                                            <span
                                                class="badge bg-label-<?php echo e($statusCtx[$status] ?? 'secondary'); ?>"><?php echo e(ucfirst($status)); ?></span>
                                        </td>
                                        <td>
                                            <?php if(!$schedule->is_cancelled): ?>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="ri ri-more-2-line"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <!-- Change Venue -->
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            onclick="openVenueModal('<?php echo e(route('student.course-leader.schedules.venue', $schedule)); ?>', '<?php echo e($schedule->venue_id); ?>')">
                                                            <i class="ri-map-pin-line me-1"></i> Change Venue
                                                        </a>

                                                        <!-- Toggle Mode -->
                                                        <form
                                                            action="<?php echo e(route('student.course-leader.schedules.mode', $schedule)); ?>"
                                                            method="POST" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="is_online"
                                                                value="<?php echo e($schedule->is_online ? 0 : 1); ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <i
                                                                    class="ri-<?php echo e($schedule->is_online ? 'building' : 'global'); ?>-line me-1"></i>
                                                                Switch to
                                                                <?php echo e($schedule->is_online ? 'Physical' : 'Online'); ?>

                                                            </button>
                                                        </form>

                                                        <!-- Log Actuals -->
                                                        <?php
                                                            $startD =
                                                                $schedule->actual_start_at?->format('Y-m-d') ??
                                                                $schedule->start_at->format('Y-m-d');
                                                            $startT =
                                                                $schedule->actual_start_at?->format('H:i') ??
                                                                $schedule->start_at->format('H:i');
                                                            $endD =
                                                                $schedule->actual_end_at?->format('Y-m-d') ??
                                                                $schedule->end_at->format('Y-m-d');
                                                            $endT =
                                                                $schedule->actual_end_at?->format('H:i') ??
                                                                $schedule->end_at->format('H:i');
                                                            $lectId =
                                                                $schedule->actual_lecturer_id ??
                                                                ($schedule->lecturer_id ?? '');
                                                        ?>
                                                        <a class="dropdown-item" href="javascript:void(0);"
                                                            onclick="openActualsModal('<?php echo e(route('student.course-leader.schedules.actuals', $schedule)); ?>', '<?php echo e($startD); ?>', '<?php echo e($startT); ?>', '<?php echo e($endD); ?>', '<?php echo e($endT); ?>', '<?php echo e($lectId); ?>')">
                                                            <i class="ri-time-line me-1"></i> Log Actual Time/Lecturer
                                                        </a>

                                                        <div class="dropdown-divider"></div>

                                                        <!-- Cancel Class -->
                                                        <form
                                                            action="<?php echo e(route('student.course-leader.schedules.status', $schedule)); ?>"
                                                            method="POST" class="d-inline log-cancel-form">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="status" value="cancelled">
                                                            <button type="submit"
                                                                class="dropdown-item text-danger log-cancel-btn">
                                                                <i class="ri-close-circle-line me-1"></i> Mark Not Taught
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No <?php echo e(strtolower($title)); ?> found for your
                                            assigned
                                            cohorts.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <!-- Past Classes -->
            <div class="card mb-4 opacity-75">
                <h5 class="card-header border-bottom">Past Classes (Recent 20)</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Course</th>
                                <th>Lecturer</th>
                                <th>Venue / Mode</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php $__empty_1 = true; $__currentLoopData = $pastSchedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($schedule->start_at->format('M d, Y')); ?></strong><br>
                                        <small><?php echo e($schedule->start_at->format('H:i')); ?> -
                                            <?php echo e($schedule->end_at->format('H:i')); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-medium"><?php echo e($schedule->course->name); ?></span><br>
                                        <small class="text-muted"><?php echo e($schedule->group->name); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $names = null;
                                            $hasPivot = \Illuminate\Support\Facades\Schema::hasTable(
                                                'lecturer_schedule',
                                            );
                                            if (
                                                $hasPivot &&
                                                $schedule->relationLoaded('lecturers') &&
                                                $schedule->lecturers->isNotEmpty()
                                            ) {
                                                $names = $schedule->lecturers->pluck('name')->implode(', ');
                                            }
                                            if (empty($names) && $schedule->lecturer) {
                                                $names = $schedule->lecturer->user?->name ?? $schedule->lecturer->name;
                                            }
                                            if (
                                                empty($names) &&
                                                $schedule->course &&
                                                $schedule->course->lecturers->isNotEmpty()
                                            ) {
                                                $names = $schedule->course->lecturers
                                                    ->map(fn($l) => $l->user?->name ?? $l->name)
                                                    ->implode(', ');
                                            }
                                        ?>
                                        <?php echo e($names ?: 'Not Assigned'); ?>

                                    </td>
                                    <td>
                                        <?php if($schedule->is_online): ?>
                                            <span class="badge bg-label-info mb-1"><i class="ri-global-line me-1"></i>
                                                Online</span><br>
                                            <small class="user-select-all">Code: <?php echo e($schedule->access_code); ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-label-secondary mb-1"><i
                                                    class="ri-building-line me-1"></i> Physical</span><br>
                                            <small><?php echo e($schedule->venue?->name ?? ($schedule->location ?? 'TBA')); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $status = $schedule->is_cancelled
                                                ? 'cancelled'
                                                : $schedule->attendance_status ?? 'scheduled';
                                            $statusCtx = [
                                                'open' => 'success',
                                                'late' => 'warning',
                                                'closed' => 'danger',
                                                'cancelled' => 'danger',
                                                'scheduled' => 'secondary',
                                            ];
                                        ?>
                                        <span
                                            class="badge bg-label-<?php echo e($statusCtx[$status] ?? 'secondary'); ?>"><?php echo e(ucfirst($status)); ?></span>
                                    </td>
                                    <td>
                                        <?php if(!$schedule->is_cancelled && $status !== 'closed'): ?>
                                            <div class="dropdown">
                                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="ri ri-more-2-line"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <!-- Change Venue -->
                                                    <a class="dropdown-item" href="javascript:void(0);"
                                                        onclick="openVenueModal('<?php echo e(route('student.course-leader.schedules.venue', $schedule)); ?>', '<?php echo e($schedule->venue_id); ?>')">
                                                        <i class="ri-map-pin-line me-1"></i> Change Venue
                                                    </a>

                                                    <!-- Toggle Mode -->
                                                    <form
                                                        action="<?php echo e(route('student.course-leader.schedules.mode', $schedule)); ?>"
                                                        method="POST" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="is_online"
                                                            value="<?php echo e($schedule->is_online ? 0 : 1); ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i
                                                                class="ri-<?php echo e($schedule->is_online ? 'building' : 'global'); ?>-line me-1"></i>
                                                            Switch to <?php echo e($schedule->is_online ? 'Physical' : 'Online'); ?>

                                                        </button>
                                                    </form>

                                                    <div class="dropdown-divider"></div>

                                                    <!-- Cancel Class -->
                                                    <form
                                                        action="<?php echo e(route('student.course-leader.schedules.status', $schedule)); ?>"
                                                        method="POST" class="d-inline log-cancel-form">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit"
                                                            class="dropdown-item text-danger log-cancel-btn">
                                                            <i class="ri-close-circle-line me-1"></i> Mark Not Taught
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No past classes found for your
                                        assigned cohorts.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Global Venue Modal -->
    <div class="modal fade" id="globalVenueModal" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Venue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="globalVenueForm" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <?php echo $__env->make('components.venue-dropdown', [
                                    'selectedVenue' => null,
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Actuals Modal -->
    <div class="modal fade" id="globalActualsModal" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Actual Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="globalActualsForm" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Actual Start</label>
                                <div class="input-group">
                                    <input type="text" class="form-control flatpickr-date bg-white"
                                        id="actual_start_date" name="actual_start_date" required>
                                    <input type="text" class="form-control flatpickr-time bg-white"
                                        id="actual_start_time" name="actual_start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Actual End</label>
                                <div class="input-group">
                                    <input type="text" class="form-control flatpickr-date bg-white"
                                        id="actual_end_date" name="actual_end_date" required>
                                    <input type="text" class="form-control flatpickr-time bg-white"
                                        id="actual_end_time" name="actual_end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Actual Lecturer</label>
                                <select id="actual_lecturer_id" name="actual_lecturer_id"
                                    class="form-select select2-lecturer" required>
                                    <option value="">Select Lecturer</option>
                                    <?php $__currentLoopData = $lecturers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lecturer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($lecturer->id); ?>">
                                            <?php echo e($lecturer->user?->name ?? $lecturer->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Move global modals to body to prevent stacking context z-index issues on mobile
            const globalVenueModal = document.getElementById('globalVenueModal');
            if (globalVenueModal) document.body.appendChild(globalVenueModal);

            const globalActualsModal = document.getElementById('globalActualsModal');
            if (globalActualsModal) document.body.appendChild(globalActualsModal);

            // Initialize Select2
            if (typeof $ !== 'undefined' && $.fn.select2) {
                // Initialize Lecturer select2 normally
                $('.select2-lecturer').each(function() {
                    $(this).select2({
                        dropdownParent: $(this).closest('.modal'),
                        width: '100%'
                    });
                });

                // Initialize Venue select2
                $('.select2-venue').each(function() {
                    $(this).select2({
                        dropdownParent: $(this).closest('.modal'),
                        width: '100%'
                    });
                });
            }

            // Bind cancel buttons
            document.querySelectorAll('.log-cancel-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    let form = this.closest('form');
                    Swal.fire({
                        title: 'Mark Class Cancelled?',
                        text: "This action indicates the class was not taught and will close attendance.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, mark cancelled'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Initialize flatpickr instances
            var dateInputs = document.querySelectorAll('.flatpickr-date');
            if (dateInputs) {
                dateInputs.forEach(function(input) {
                    flatpickr(input, {
                        altInput: true,
                        altFormat: 'Y-m-d',
                        dateFormat: 'Y-m-d',
                        disableMobile: true
                    });
                });
            }

            var timeInputs = document.querySelectorAll('.flatpickr-time');
            if (timeInputs) {
                timeInputs.forEach(function(input) {
                    flatpickr(input, {
                        enableTime: true,
                        noCalendar: true,
                        time_24hr: true,
                        disableMobile: true
                    });
                });
            }

            <?php if(session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo e(session('success')); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            <?php endif; ?>

            <?php if(session('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?php echo e(session('error')); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            <?php endif; ?>

            <?php if($errors->any()): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: `<?php echo implode('<br>', $errors->all()); ?>`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000
                });
            <?php endif; ?>
        });

        // Global Modal Functions
        function openVenueModal(actionUrl, currentVenueId) {
            var form = document.getElementById('globalVenueForm');
            form.action = actionUrl;

            if (typeof $ !== 'undefined') {
                var venueSelect = $('#venue-select');
                venueSelect.val(currentVenueId).trigger('change');
            }

            var modal = new bootstrap.Modal(document.getElementById('globalVenueModal'));
            modal.show();
        }

        function openActualsModal(actionUrl, startDate, startTime, endDate, endTime, lecturerId) {
            var form = document.getElementById('globalActualsForm');
            form.action = actionUrl;

            document.getElementById('actual_start_date').value = startDate;
            document.getElementById('actual_start_time').value = startTime;
            document.getElementById('actual_end_date').value = endDate;
            document.getElementById('actual_end_time').value = endTime;

            // Update flatpickr instances if they exist
            if (document.getElementById('actual_start_date')._flatpickr) {
                document.getElementById('actual_start_date')._flatpickr.setDate(startDate);
            }
            if (document.getElementById('actual_start_time')._flatpickr) {
                document.getElementById('actual_start_time')._flatpickr.setDate(startTime);
            }
            if (document.getElementById('actual_end_date')._flatpickr) {
                document.getElementById('actual_end_date')._flatpickr.setDate(endDate);
            }
            if (document.getElementById('actual_end_time')._flatpickr) {
                document.getElementById('actual_end_time')._flatpickr.setDate(endTime);
            }

            if (typeof $ !== 'undefined') {
                var lecturerSelect = $('#actual_lecturer_id');
                lecturerSelect.val(lecturerId).trigger('change');
            }

            var modal = new bootstrap.Modal(document.getElementById('globalActualsModal'));
            modal.show();
        }
    </script>
<?php $__env->stopSection(); ?>
```

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/katusome.ssendi.dev/resources/views/student/course-leader/dashboard.blade.php ENDPATH**/ ?>