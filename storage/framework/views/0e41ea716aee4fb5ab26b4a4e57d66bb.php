<?php $__env->startSection('title', 'Groups'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Groups</h4>
  <a href="<?php echo e(route('admin.groups.create')); ?>" class="btn btn-primary">Add Group</a>
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
          <th>Program</th>
          <th>Students</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <tr>
            <td><?php echo e($group->name); ?></td>
            <td><?php echo e($group->program->name); ?></td>
            <td><?php echo e($group->students_count); ?></td>
            <td class="text-end">
              <a href="<?php echo e(route('admin.groups.edit', $group)); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="<?php echo e(route('admin.groups.destroy', $group)); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this group?')">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?php echo e($groups->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\groups\index.blade.php ENDPATH**/ ?>