<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Course</th>
        <th>Group</th>
        <th>Lecturer</th>
        <th>Series</th>
        <th>Location</th>
        <th>Start</th>
        <th>End</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $schedules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td><?php echo e(optional($schedule->course)->name); ?></td>
          <td><?php echo e(optional($schedule->group)->name); ?></td>
          <td><?php echo e(optional($schedule->lecturer)->name); ?></td>
          <td><?php echo e(optional($schedule->series)->name); ?></td>
          <td><?php echo e($schedule->location); ?></td>
          <td><?php echo e($schedule->start_at?->format('Y-m-d H:i')); ?></td>
          <td><?php echo e($schedule->end_at?->format('Y-m-d H:i')); ?></td>
          <td class="text-end">
            <a href="<?php echo e(route('admin.schedules.edit', $schedule)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <form action="<?php echo e(route('admin.schedules.destroy', $schedule)); ?>" method="POST" class="d-inline">
              <?php echo csrf_field(); ?>
              <?php echo method_field('DELETE'); ?>
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this schedule?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
</div>
<div class="card-footer"><?php echo e($schedules->appends(request()->query())->links()); ?></div><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\schedules\partials\table.blade.php ENDPATH**/ ?>