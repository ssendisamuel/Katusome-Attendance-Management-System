<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />

<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/fonts/iconify/iconify.css']); ?>

<?php if($configData['hasCustomizer']): ?>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/pickr/pickr-themes.scss']); ?>
<?php endif; ?>

<!-- Vendor Styles -->
<?php echo $__env->yieldContent('vendor-style'); ?>
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/node-waves/node-waves.scss']); ?>

<!-- Core CSS -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/core.scss', 'resources/assets/css/demo.css', 'resources/assets/vendor/scss/pages/front-page.scss']); ?>

<!-- Page Styles -->
<?php echo $__env->yieldContent('page-style'); ?>
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\stylesFront.blade.php ENDPATH**/ ?>