<?php
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
?>



<?php $__env->startSection('title', 'Register Basic - Pages'); ?>

<?php $__env->startSection('vendor-style'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/@form-validation/form-validation.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/pages/page-auth.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/pages-auth.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <div class="position-relative">
    <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
      <div class="authentication-inner py-6">
        <!-- Register Card -->
        <div class="card p-md-7 p-1">
          <!-- Logo -->
          <div class="app-brand justify-content-center mt-5">
            <a href="<?php echo e(url('/')); ?>" class="app-brand-link gap-2">
              <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
              <span class="app-brand-text demo text-heading fw-semibold"><?php echo e(config('variables.templateName')); ?></span>
            </a>
          </div>
          <!-- /Logo -->
          <div class="card-body mt-1">
            <h4 class="mb-1">Adventure starts here 🚀</h4>
            <p class="mb-5">Make your app management easy and fun!</p>

            <form id="formAuthentication" class="mb-5" action="<?php echo e(route('register.post')); ?>" method="POST">
              <?php echo csrf_field(); ?>
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control" id="name" name="name"
                  placeholder="Enter your full name" autofocus required />
                <label for="name">Full Name</label>
              </div>
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required />
                <label for="email">Email</label>
              </div>
              <div class="mb-5 form-password-toggle form-control-validation">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="password" class="form-control" name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" required />
                      <label for="password">Password</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i
                      class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                  </div>
                </div>

              <div class="mb-5 form-control-validation">
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" />
                  <label class="form-check-label" for="terms-conditions">
                    I agree to
                    <a href="javascript:void(0);">privacy policy & terms</a>
                  </label>
                </div>
              </div>
              <button class="btn btn-primary d-grid w-100 mb-5">Sign up</button>
            </form>

            <p class="text-center mb-5">
              <span>Already have an account?</span>
              <a href="<?php echo e(route('login')); ?>">
                <span>Sign in instead</span>
              </a>
            </p>

            <div class="divider my-5">
              <div class="divider-text">or</div>
            </div>

            <div class="d-flex justify-content-center gap-2">
              <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook">
                <i class="icon-base ri ri-facebook-fill icon-18px"></i>
              </a>

              <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter">
                <i class="icon-base ri ri-twitter-fill icon-18px"></i>
              </a>

              <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github">
                <i class="icon-base ri ri-github-fill icon-18px"></i>
              </a>

              <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">
                <i class="icon-base ri ri-google-fill icon-18px"></i>
              </a>
            </div>
          </div>
        </div>
        <!-- Register Card -->
        <img alt="mask"
          src="<?php echo e(asset('assets/img/illustrations/auth-basic-register-mask-' . $configData['theme'] . '.png')); ?>"
          class="authentication-image d-none d-lg-block"
          data-app-light-img="illustrations/auth-basic-register-mask-light.png"
          data-app-dark-img="illustrations/auth-basic-register-mask-dark.png" />
      </div>
    </div>
  </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\content\authentications\auth-register-basic.blade.php ENDPATH**/ ?>