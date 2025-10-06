<!-- Footer: Start -->
<footer class="landing-footer">
  <div class="footer-top position-relative overflow-hidden">
    <img src="<?php echo e(asset('assets/img/front-pages/backgrounds/footer-bg.png')); ?>" alt="footer bg"
      class="footer-bg banner-bg-img" />
    <div class="container">
      <div class="row gx-0 gy-6 g-lg-10">
        <div class="col-lg-5">
          <a href="javascript:;" class="app-brand-link mb-6">
            <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
            <span
              class="app-brand-text demo text-white fw-semibold ms-2 ps-1"><?php echo e(config('variables.templateName')); ?></span>
          </a>
          <p class="footer-text footer-logo-description mb-6">Most Powerful & Comprehensive 🤩 React NextJS Admin
            Template with Elegant Material Design & Unique Layouts.</p>
          <form class="footer-form">
            <div class="d-flex mt-2 gap-4">
              <div class="form-floating form-floating-outline w-px-250">
                <input type="text" class="form-control bg-transparent" id="newsletter-1" placeholder="Your email" />
                <label for="newsletter-1">Subscribe to newsletter</label>
              </div>
              <button type="submit" class="btn btn-primary">Subscribe</button>
            </div>
          </form>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <h6 class="footer-title mb-4 mb-lg-6">Demos</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Vertical Layout</a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Horizontal Layout</a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Bordered Layout</a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Semi Dark Layout</a>
            </li>
            <li>
              <a href="javascript:;" class="footer-link">Dark Layout</a>
            </li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <h6 class="footer-title mb-4 mb-lg-6">Pages</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Pricing</a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Payment<span
                  class="badge rounded-pill bg-primary ms-2">New</span></a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Checkout</a>
            </li>
            <li class="mb-4">
              <a href="javascript:;" class="footer-link">Help Center</a>
            </li>
            <li>
              <a href="javascript:;" class="footer-link">Login/Register</a>
            </li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-4">
          <h6 class="footer-title mb-4 mb-lg-6">Download our app</h6>
          <a href="javascript:void(0);" class="d-block footer-link mb-4"><img
              src="<?php echo e(asset('assets/img/front-pages/landing-page/apple-icon.png')); ?>" alt="apple icon" /></a>
          <a href="javascript:void(0);" class="d-block footer-link"><img
              src="<?php echo e(asset('assets/img/front-pages/landing-page/google-play-icon.png')); ?>" alt="google play icon" /></a>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom py-5">
    <div class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
      <div class="mb-2 mb-md-0">
        <span class="footer-bottom-text">©
          <script>
          document.write(new Date().getFullYear());
          </script>
          , Made with <i class="icon-base ri ri-heart-fill text-danger"></i> by
        </span>
        <a href="<?php echo e(config('variables.creatorUrl') ?: url('/')); ?>" target="_blank"
          class="footer-link fw-medium footer-theme-link"><?php echo e(config('variables.creatorName', 'Ssendi Samuel')); ?></a>
      </div>
      <div>
        <?php
          $facebook = config('variables.facebookUrl');
          $twitter = config('variables.twitterUrl');
          $instagram = config('variables.instagramUrl');
          $github = config('variables.githubUrl') ?: config('variables.githubFreeUrl');
          $youtube = config('variables.youtubeUrl');
          $linkedin = config('variables.linkedinUrl');
        ?>
        <?php if(!empty($github)): ?>
          <a href="<?php echo e($github); ?>" class="footer-link me-4" target="_blank"><i class="icon-base ri ri-github-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($facebook)): ?>
          <a href="<?php echo e($facebook); ?>" class="footer-link me-4" target="_blank"><i class="icon-base ri ri-facebook-circle-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($twitter)): ?>
          <a href="<?php echo e($twitter); ?>" class="footer-link me-4" target="_blank"><i class="icon-base ri ri-twitter-x-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($youtube)): ?>
          <a href="<?php echo e($youtube); ?>" class="footer-link me-4" target="_blank"><i class="icon-base ri ri-youtube-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($linkedin)): ?>
          <a href="<?php echo e($linkedin); ?>" class="footer-link" target="_blank"><i class="icon-base ri ri-linkedin-fill"></i></a>
        <?php endif; ?>
        <?php if(!empty($instagram)): ?>
          <a href="<?php echo e($instagram); ?>" class="footer-link" target="_blank"><i class="icon-base ri ri-instagram-line"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>
<!-- Footer: End -->
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\footer\footer-front.blade.php ENDPATH**/ ?>