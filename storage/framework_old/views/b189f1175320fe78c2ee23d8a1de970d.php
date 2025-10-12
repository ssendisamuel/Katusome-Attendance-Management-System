<?php $__env->startSection('title', 'Daily Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-6">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <h4 class="mb-0">Daily Attendance — <?php echo e($date); ?></h4>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="card-body">
        <form class="row g-4" method="GET" action="<?php echo e(route('lecturer.reports.daily')); ?>">
          <div class="col-12 col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" value="<?php echo e($date); ?>" class="form-control" />
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select">
              <option value="">All Courses</option>
              <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($c->id); ?>" <?php if(request('course_id')==$c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-select">
              <option value="">All Groups</option>
              <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($g->id); ?>" <?php if(request('group_id')==$g->id): echo 'selected'; endif; ?>><?php echo e($g->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All</option>
              <option value="present" <?php if(request('status')==='present'): echo 'selected'; endif; ?>>Present</option>
              <option value="late" <?php if(request('status')==='late'): echo 'selected'; endif; ?>>Late</option>
              <option value="absent" <?php if(request('status')==='absent'): echo 'selected'; endif; ?>>Absent</option>
            </select>
          </div>

          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex gap-2">
                <a href="<?php echo e(route('lecturer.reports.daily')); ?>" class="btn btn-outline-secondary">Reset</a>
                <button class="btn btn-primary">Filter</button>
              </div>
              <div class="report-actions d-flex gap-2">
                <a href="<?php echo e(route('lecturer.reports.daily.export.csv', request()->query())); ?>" class="btn btn-outline-secondary">
                  <span class="icon-base ri ri-file-list-2-line me-2"></span> CSV
                </a>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="row g-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Expected</p>
            <h4 class="mb-0"><?php echo e($expected); ?></h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Present</p>
            <h4 class="mb-0 text-success"><?php echo e($present); ?></h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Absent</p>
            <h4 class="mb-0 text-danger"><?php echo e($absent); ?></h4>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <p class="mb-0 text-muted">Late</p>
            <h4 class="mb-0 text-warning"><?php echo e($late); ?></h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card h-100">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Group</th>
              <th>Course</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><?php echo e(optional($row->student->user)->name ?? optional($row->student)->name); ?></td>
                <td><?php echo e(optional($row->schedule->group)->name); ?></td>
                <td><?php echo e(optional($row->schedule->course)->name); ?></td>
                <td><?php echo e(optional($row->marked_at)?->format('H:i')); ?></td>
                <td><?php echo e(ucfirst($row->status)); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5" class="text-center">No Data Found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer"><?php echo e($attendances->withQueryString()->links()); ?></div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
<style>
  .report-actions .btn { padding: 0.5rem 1rem; line-height: 1.5; display: inline-flex; align-items: center; }
  .report-actions .btn .icon-base { line-height: 1; }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/lecturer/reports/daily.blade.php ENDPATH**/ ?>