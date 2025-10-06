<?php
$configData = Helper::appClasses();
?>



<?php $__env->startSection('title', 'Home'); ?>

<?php $__env->startSection('content'); ?>
<h4>Home Page</h4>
<p>For more layout options refer <a href="<?php echo e(config('variables.documentation') ? config('variables.documentation').'/laravel-introduction.html' : '#'); ?>" target="_blank" rel="noopener noreferrer">documentation</a>.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\content\pages\pages-home.blade.php ENDPATH**/ ?>