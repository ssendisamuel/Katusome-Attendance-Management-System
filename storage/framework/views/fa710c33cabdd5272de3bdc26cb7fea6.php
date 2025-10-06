<?php
  $width = $width ?? '38';
  $height = $height ?? '38';
?>

<img src="<?php echo e(asset('storage/mubslogo.png')); ?>" alt="MUBS Logo" width="<?php echo e($width); ?>" height="<?php echo e($height); ?>" class="app-brand-img"
  onerror="this.onerror=null; this.src='<?php echo e(asset('favicon.ico')); ?>'" />
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\_partials\macros.blade.php ENDPATH**/ ?>