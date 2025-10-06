<?php ($configData = Helper::appClasses()); ?>



<?php $__env->startSection('title', 'Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="row gy-6">
  <!-- Top stats cards styled like academy -->
  <div class="col-12">
    <div class="card bg-transparent shadow-none border-0 mb-6">
      <div class="card-body row g-6 p-0 pb-5">
        <div class="col-12 col-md-8 card-separator">
          <h5 class="mb-2">Attendance Overview</h5>
          <div class="row g-4 me-12">
            <div class="col-12 col-sm-6 col-lg-6">
              <div class="card h-100 bg-primary-subtle">
                <div class="card-body d-flex align-items-center gap-4">
                  <div class="avatar avatar-lg">
                    <div class="avatar-initial rounded bg-white">
                      <span class="icon-base ri ri-group-line icon-28px text-primary"></span>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-1 fw-medium text-primary text-nowrap">Students</p>
                    <span class="text-primary mb-0 h5"><?php echo e($studentsCount ?? '—'); ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-6">
              <div class="card h-100 bg-info-subtle">
                <div class="card-body d-flex align-items-center gap-4">
                  <div class="avatar avatar-lg">
                    <div class="avatar-initial rounded bg-white">
                      <span class="icon-base ri ri-book-2-line icon-28px text-info"></span>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-1 fw-medium text-info text-nowrap">Courses</p>
                    <span class="text-info mb-0 h5"><?php echo e($coursesCount ?? '—'); ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-6">
              <div class="card h-100 bg-success-subtle">
                <div class="card-body d-flex align-items-center gap-4">
                  <div class="avatar avatar-lg">
                    <div class="avatar-initial rounded bg-white">
                      <span class="icon-base ri ri-calendar-check-line icon-28px text-success"></span>
                    </div>
                  </div>
                  <div class="content-right">
                    <p class="mb-1 fw-medium text-success text-nowrap">Classes Today</p>
                    <span class="text-success mb-0 h5"><?php echo e($todaysClasses ?? '—'); ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4 ps-md-4 ps-lg-6">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div>
                <h5 class="mb-1">Attendance Rate</h5>
                <p class="mb-9">Today</p>
              </div>
              <div class="time-spending-chart">
                <h5 class="mb-2"><?php echo e($attendanceRateToday); ?><span class="text-body"></span></h5>
                <span class="badge bg-success rounded-pill">Overall <?php echo e($attendanceRateOverall); ?></span>
              </div>
            </div>
            <div>
              <span class="icon-base ri ri-bar-chart-2-line icon-32px text-success"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Metrics tiles -->
  <div class="row mb-6 g-6">
    <!-- Entities full-width -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="card-title m-0 me-2">Entities</h5>
        </div>
        <div class="card-body row g-4">
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="card h-100 bg-primary-subtle">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <span class="icon-base ri ri-layout-2-line icon-22px text-primary"></span>
                  </div>
                </div>
                <div>
                  <p class="mb-0 text-nowrap text-primary">Programs</p>
                  <h4 class="mb-0 text-primary"><?php echo e($programsCount ?? '—'); ?></h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="card h-100 bg-info-subtle">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <span class="icon-base ri ri-team-line icon-22px text-info"></span>
                  </div>
                </div>
                <div>
                  <p class="mb-0 text-nowrap text-info">Groups</p>
                  <h4 class="mb-0 text-info"><?php echo e($groupsCount ?? '—'); ?></h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="card h-100 bg-warning-subtle">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <span class="icon-base ri ri-user-star-line icon-22px text-warning"></span>
                  </div>
                </div>
                <div>
                  <p class="mb-0 text-nowrap text-warning">Lecturers</p>
                  <h4 class="mb-0 text-warning"><?php echo e($lecturersCount ?? '—'); ?></h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-6">
            <div class="card h-100 bg-danger-subtle">
              <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar avatar-md">
                  <div class="avatar-initial rounded bg-white">
                    <span class="icon-base ri ri-time-line icon-22px text-danger"></span>
                  </div>
                </div>
                <div>
                  <p class="mb-0 text-nowrap text-danger">Pending Attendance</p>
                  <h4 class="mb-0 text-danger"><?php echo e($pendingAttendance ?? '—'); ?></h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today Status full-width -->
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
          <div class="card-title mb-0">
            <h5 class="m-0 me-2">Today Status</h5>
          </div>
        </div>
        <div class="px-5 py-4 border border-start-0 border-end-0">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fs-xsmall text-uppercase fw-normal">Status</h6>
            <h6 class="mb-0 fs-xsmall text-uppercase fw-normal">Count</h6>
          </div>
        </div>
        <div class="card-body pt-5">
          <div class="d-flex justify-content-between align-items-center mb-6">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm">
                <div class="avatar-initial rounded bg-success-subtle">
                  <span class="icon-base ri ri-check-line icon-18px text-success"></span>
                </div>
              </div>
              <div>
                <h6 class="mb-0 text-truncate">Present</h6>
                <small class="text-truncate">Marked today</small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-success"><?php echo e($presentToday); ?></span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-6">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm">
                <div class="avatar-initial rounded bg-danger-subtle">
                  <span class="icon-base ri ri-close-line icon-18px text-danger"></span>
                </div>
              </div>
              <div>
                <h6 class="mb-0 text-truncate">Absent</h6>
                <small class="text-truncate">Marked today</small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-danger"><?php echo e($absentToday); ?></span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-6">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm">
                <div class="avatar-initial rounded bg-warning-subtle">
                  <span class="icon-base ri ri-time-line icon-18px text-warning"></span>
                </div>
              </div>
              <div>
                <h6 class="mb-0 text-truncate">Late</h6>
                <small class="text-truncate">Marked today</small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-warning"><?php echo e($lateToday); ?></span>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm">
                <div class="avatar-initial rounded bg-secondary-subtle">
                  <span class="icon-base ri ri-question-line icon-18px text-secondary"></span>
                </div>
              </div>
              <div>
                <h6 class="mb-0 text-truncate">Unmarked</h6>
                <small class="text-truncate">For today's classes</small>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-secondary"><?php echo e($unmarkedToday); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\content\dashboards\admin.blade.php ENDPATH**/ ?>