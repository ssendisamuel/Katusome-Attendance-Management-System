<?php
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
?>



<?php $__env->startSection('title', 'Forgot Password'); ?>

<?php $__env->startSection('page-style'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/pages/page-auth.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/pages-auth.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
      <div class="card p-md-7 p-1">
        <div class="card-body mt-1">
          <h4 class="mb-1">Reset your password</h4>
          <p class="mb-5">Enter your email to receive a reset link.</p>

          <?php if(session('status')): ?>
            <div class="alert alert-success" role="alert"><?php echo e(session('status')); ?></div>
          <?php endif; ?>

          <form method="POST" action="<?php echo e(route('password.email')); ?>" class="mb-5">
            <?php echo csrf_field(); ?>
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?php echo e(old('email')); ?>" required autofocus />
              <label for="email">Email</label>
              <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-2"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <button class="btn btn-primary d-grid w-100" type="submit">Send Reset Link</button>
          </form>

          <p class="text-center mb-1">
            <a href="<?php echo e(route('login')); ?>">Back to Login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\content\authentications\passwords\email.blade.php ENDPATH**/ ?>