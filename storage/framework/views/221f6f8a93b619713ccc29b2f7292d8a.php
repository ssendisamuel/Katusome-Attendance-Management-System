<?php $__env->startSection('title', 'Programs'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Programs</h4>
  <a href="<?php echo e(route('admin.programs.create')); ?>" class="btn btn-primary">Add Program</a>
  </div>
<?php if(session('success')): ?>
  <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Code</th>
          <th>Groups</th>
          <th>Courses</th>
          <th>Students</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($program->name); ?></td>
            <td><?php echo e($program->code); ?></td>
            <td><?php echo e($program->groups_count); ?></td>
            <td><?php echo e($program->courses_count); ?></td>
            <td><?php echo e($program->students_count); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.programs.edit', $program)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="<?php echo e(route('admin.programs.destroy', $program)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this program?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($programs->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\programs\index.blade.php ENDPATH**/ ?>