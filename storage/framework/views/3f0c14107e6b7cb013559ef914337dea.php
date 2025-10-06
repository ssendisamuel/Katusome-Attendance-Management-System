<?php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
?>



<?php $__env->startSection('title', 'Error - Pages'); ?>

<?php $__env->startSection('page-style'); ?>
  <!-- Page -->
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/pages/page-misc.scss']); ?>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>
  <!-- Error -->
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="font-size: 6rem;line-height: 6rem;">404</h1>
    <h4 class="mb-2">Page Not Found ⚠️</h4>
    <p class="mb-6 mx-2">we couldn't find the page you are looking for</p>
    <div class="d-flex justify-content-center mt-9">
      <img src="<?php echo e(asset('assets/img/illustrations/misc-error-object.png')); ?>" alt="misc-error"
        class="img-fluid misc-object d-none d-lg-inline-block" width="160" />
      <img src="<?php echo e(asset('assets/img/illustrations/misc-bg-' . $configData['theme'] . '.png')); ?>" alt="misc-error"
        class="misc-bg d-none d-lg-inline-block" data-app-light-img="illustrations/misc-bg-light.png"
        data-app-dark-img="illustrations/misc-bg-dark.png" />
      <div class="d-flex flex-column align-items-center">
        <img src="<?php echo e(asset('assets/img/illustrations/misc-error-illustration.png')); ?>" alt="misc-error"
          class="img-fluid z-1" width="190" />
        <div>
          <a href="<?php echo e(url('/')); ?>" class="btn btn-primary text-center my-10">Back to home</a>
        </div>
      </div>
    </div>
  </div>
  <!-- /Error -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\content\pages\pages-misc-error.blade.php ENDPATH**/ ?>