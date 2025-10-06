<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Course</th>
        <th>Group</th>
        <th>Lecturer</th>
        <th>Status</th>
        <th>Marked At</th>
        <th>Location</th>
        <th>Selfie</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $attendances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attendance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td><?php echo e(optional($attendance->student)->name); ?></td>
          <td><?php echo e(optional($attendance->schedule->course)->name); ?></td>
          <td><?php echo e(optional($attendance->schedule->group)->name); ?></td>
          <td><?php echo e(optional($attendance->schedule->lecturer)->name); ?></td>
          <td><?php echo e(ucfirst($attendance->status)); ?></td>
          <td><?php echo e($attendance->marked_at?->format('Y-m-d H:i')); ?></td>
          <td><?php echo e($attendance->lat && $attendance->lng ? $attendance->lat . ', ' . $attendance->lng : '—'); ?></td>
          <td>
            <?php if($attendance->selfie_path): ?>
              <a href="<?php echo e(Storage::url($attendance->selfie_path)); ?>" target="_blank">View</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td class="text-end">
            <form action="<?php echo e(route('admin.attendance.destroy', $attendance)); ?>" method="POST" class="d-inline">
              <?php echo csrf_field(); ?>
              <?php echo method_field('DELETE'); ?>
              <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this attendance?')">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
</div>
<div class="card-footer"><?php echo e($attendances->appends(request()->query())->links()); ?></div><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\attendance\partials\table.blade.php ENDPATH**/ ?>