<?php $__env->startSection('title', 'Edit Group'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
  <div class="card-header"><h5 class="mb-0">Edit Group</h5></div>
  <div class="card-body">
    <form method="POST" action="<?php echo e(route('admin.groups.update', $group)); ?>">
      <?php echo csrf_field(); ?>
      <?php echo method_field('PUT'); ?>
      <div class="mb-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $group->name)); ?>" required>
        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small class="text-danger"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <div class="mb-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select" required>
          <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($program->id); ?>" <?php if(old('program_id', $group->program_id)===$program->id): echo 'selected'; endif; ?>><?php echo e($program->name); ?> (<?php echo e($program->code); ?>)</option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['program_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small class="text-danger"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <button class="btn btn-primary" type="submit">Update</button>
      <a href="<?php echo e(route('admin.groups.index')); ?>" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\groups\edit.blade.php ENDPATH**/ ?>