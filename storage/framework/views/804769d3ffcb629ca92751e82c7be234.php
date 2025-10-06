<?php
  $configData = Helper::appClasses();
  $isFront = true;
?>

<?php $__env->startSection('layoutContent'); ?>
  

  <?php echo $__env->make('layouts/sections/navbar/navbar-front', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <!-- Sections:Start -->
  <?php echo $__env->yieldContent('content'); ?>
  <!-- / Sections:End -->

  <?php echo $__env->make('layouts/sections/footer/footer-front', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/commonMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\layoutFront.blade.php ENDPATH**/ ?>