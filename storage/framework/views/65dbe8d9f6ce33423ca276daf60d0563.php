<?php $__env->startSection('title', 'Mark Attendance'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Mark Attendance — <?php echo e(optional($schedule->course)->name); ?> (<?php echo e(optional($schedule->group)->name); ?>)</h4>
  <span class="text-muted"><?php echo e($schedule->start_at->format('Y-m-d H:i')); ?> @ <?php echo e($schedule->location); ?></span>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="<?php echo e(route('lecturer.attendance.update', $schedule)); ?>" class="table-responsive">
      <?php echo csrf_field(); ?>
      <table class="table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($existingRec = $existing[$student->id] ?? null); ?>
            <tr>
              <td><?php echo e($student->name); ?></td>
              <td>
                <select name="statuses[<?php echo e($student->id); ?>]" class="form-select">
                  <?php ($current = $existingRec?->status); ?>
                  <option value="present" <?php echo e($current === 'present' ? 'selected' : ''); ?>>Present</option>
                  <option value="late" <?php echo e($current === 'late' ? 'selected' : ''); ?>>Late</option>
                  <option value="absent" <?php echo e($current === 'absent' ? 'selected' : ''); ?>>Absent</option>
                </select>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
      </table>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\lecturer\attendance\edit.blade.php ENDPATH**/ ?>