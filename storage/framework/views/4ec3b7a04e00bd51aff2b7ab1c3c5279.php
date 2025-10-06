<?php $__env->startSection('title', 'Add Program'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
  <div class="card-header"><h5 class="mb-0">Create Program</h5></div>
  <div class="card-body">
    <form method="POST" action="<?php echo e(route('admin.programs.store')); ?>">
      <?php echo csrf_field(); ?>
      <div class="mb-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required>
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
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" value="<?php echo e(old('code')); ?>" required>
        <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><small class="text-danger"><?php echo e($message); ?></small><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>
      <button class="btn btn-primary" type="submit">Save</button>
      <a href="<?php echo e(route('admin.programs.index')); ?>" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\admin\programs\create.blade.php ENDPATH**/ ?>