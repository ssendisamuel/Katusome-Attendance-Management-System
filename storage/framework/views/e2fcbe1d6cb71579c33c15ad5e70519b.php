<?php $__env->startSection('title', 'Courses'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Courses</h4>
  <a href="<?php echo e(route('admin.courses.create')); ?>" class="btn btn-primary">Add Course</a>
</div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Program</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($course->code); ?></td>
            <td><?php echo e($course->name); ?></td>
            <td><?php echo e(optional($course->program)->name); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.courses.edit', $course)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="<?php echo e(route('admin.courses.destroy', $course)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this course?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($courses->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\courses\index.blade.php ENDPATH**/ ?>