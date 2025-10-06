<?php
  $containerFooter =
      isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
          ? 'container-xxl'
          : 'container-fluid';
?>

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="<?php echo e($containerFooter); ?>">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="mb-2 mb-md-0 d-flex align-items-center gap-3">
        <span>
          &#169;
          <script>
            document.write(new Date().getFullYear());
          </script>
          , made with ❤️ by <a href="<?php echo e(config('variables.creatorUrl') ?: url('/')); ?>" target="_blank" class="footer-link fw-medium"><?php echo e(config('variables.creatorName', 'Ssendi Samuel')); ?></a>
        </span>
        <?php
          $twitter = config('variables.twitterUrl');
          $youtube = config('variables.youtubeUrl');
          $linkedin = config('variables.linkedinUrl');
        ?>
        <?php if(!empty($twitter)): ?>
          <a href="<?php echo e($twitter); ?>" class="footer-link" target="_blank" aria-label="X"><i class="icon-base ri ri-twitter-x-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($youtube)): ?>
          <a href="<?php echo e($youtube); ?>" class="footer-link" target="_blank" aria-label="YouTube"><i class="icon-base ri ri-youtube-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($linkedin)): ?>
          <a href="<?php echo e($linkedin); ?>" class="footer-link" target="_blank" aria-label="LinkedIn"><i class="icon-base ri ri-linkedin-fill"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>
<!-- / Footer -->
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\footer\footer.blade.php ENDPATH**/ ?>