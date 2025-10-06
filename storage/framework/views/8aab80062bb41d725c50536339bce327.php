<?php $__env->startSection('title', 'Lecturer Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Today’s Classes</h4>
</div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Course</th>
          <th>Group</th>
          <th>Location</th>
          <th>Start</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><?php echo e(optional($schedule->course)->name); ?></td>
            <td><?php echo e(optional($schedule->group)->name); ?></td>
            <td><?php echo e($schedule->location); ?></td>
            <td><?php echo e($schedule->start_at->format('Y-m-d H:i')); ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-primary" href="<?php echo e(route('lecturer.attendance.edit', $schedule)); ?>">Mark Attendance</a>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="5" class="text-center">No classes scheduled for today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($schedules->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\lecturer\attendance\index.blade.php ENDPATH**/ ?>