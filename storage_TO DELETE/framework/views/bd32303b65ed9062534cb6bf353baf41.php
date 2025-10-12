<?php $__env->startSection('title', 'Edit Lecturer'); ?>

<?php $__env->startSection('content'); ?>
<h4 class="mb-4">Edit Lecturer</h4>
<div class="card p-4">
  <form method="POST" action="<?php echo e(route('admin.lecturers.update', $lecturer)); ?>">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $lecturer->name)); ?>" required>
      <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $lecturer->email)); ?>">
      <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
<div class="mb-3">
  <label class="form-label">Phone</label>
  <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $lecturer->phone)); ?>">
  <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<div class="mb-3">
  <label class="form-label">New Password (optional)</label>
  <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep current password">
  <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  <div class="form-text">If provided, the lecturer's password will be updated.</div>
  </div>
<div class="mb-3">
  <label class="form-label">Confirm New Password</label>
  <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
  </div>
<div class="form-check mb-3">
  <input type="checkbox" name="must_change_password" id="mustChangePassword" class="form-check-input" value="1" <?php echo e(old('must_change_password', optional($lecturer->user)->must_change_password) ? 'checked' : ''); ?>>
  <label class="form-check-label" for="mustChangePassword">Require password change on next login</label>
  </div>
<button class="btn btn-primary">Update</button>
<a href="<?php echo e(route('admin.lecturers.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/admin/lecturers/edit.blade.php ENDPATH**/ ?>