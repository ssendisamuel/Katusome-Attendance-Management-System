<?php
  $configData = Helper::appClasses();
?>



<?php $__env->startSection('title', 'Change Password'); ?>

<?php $__env->startSection('page-style'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/pages/page-auth.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/pages-auth.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-8 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Change Password</h5>
        </div>
        <div class="card-body">
          <?php if(session('success')): ?>
            <div class="alert alert-success" role="alert"><?php echo e(session('success')); ?></div>
          <?php endif; ?>

          <form method="POST" action="<?php echo e(route('password.change.update')); ?>" class="mb-3">
            <?php echo csrf_field(); ?>

            <div class="mb-4 form-control-validation">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password" />
              <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-2"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-4 form-control-validation">
              <label for="password" class="form-label">New Password</label>
              <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" />
              <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-2"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="mb-4 form-control-validation">
              <label for="password_confirmation" class="form-label">Confirm New Password</label>
              <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password" />
            </div>

            <button type="submit" class="btn btn-primary">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/content/account/change-password.blade.php ENDPATH**/ ?>