<!-- BEGIN: Vendor JS-->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/js/dropdown-hover.js', 'resources/assets/vendor/js/mega-dropdown.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js', 'resources/assets/vendor/libs/node-waves/node-waves.js']); ?>

<?php if($configData['hasCustomizer']): ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/pickr/pickr.js']); ?>
<?php endif; ?>

<?php echo $__env->yieldContent('vendor-script'); ?>
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/front-main.js']); ?>
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
<?php echo $__env->yieldPushContent('pricing-script'); ?>
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
<?php echo $__env->yieldContent('page-script'); ?>
<!-- END: Page JS-->
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\scriptsFront.blade.php ENDPATH**/ ?>