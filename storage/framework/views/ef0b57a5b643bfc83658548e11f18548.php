<?php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $menuAttributesHtml = '';
  if (!empty($configData['menuAttributes']) && is_iterable($configData['menuAttributes'])) {
      foreach ($configData['menuAttributes'] as $attribute => $value) {
          $menuAttributesHtml .= ' ' . $attribute . '="' . e($value) . '"';
      }
  }
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu" <?php echo $menuAttributesHtml; ?>>

  <!-- ! Hide app brand if navbar-full -->
  <?php if(!isset($navbarFull)): ?>
    <div class="app-brand demo">
      <a href="<?php echo e(url('/')); ?>" class="app-brand-link gap-xl-0 gap-2">
        <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
        <span class="app-brand-text demo menu-text fw-semibold ms-2"><?php echo e(config('variables.templateName')); ?></span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M8.47365 11.7183C8.11707 12.0749 8.11707 12.6531 8.47365 13.0097L12.071 16.607C12.4615 16.9975 12.4615 17.6305 12.071 18.021C11.6805 18.4115 11.0475 18.4115 10.657 18.021L5.83009 13.1941C5.37164 12.7356 5.37164 11.9924 5.83009 11.5339L10.657 6.707C11.0475 6.31653 11.6805 6.31653 12.071 6.707C12.4615 7.09747 12.4615 7.73053 12.071 8.121L8.47365 11.7183Z"
            fill-opacity="0.9" />
          <path
            d="M14.3584 11.8336C14.0654 12.1266 14.0654 12.6014 14.3584 12.8944L18.071 16.607C18.4615 16.9975 18.4615 17.6305 18.071 18.021C17.6805 18.4115 17.0475 18.4115 16.657 18.021L11.6819 13.0459C11.3053 12.6693 11.3053 12.0587 11.6819 11.6821L16.657 6.707C17.0475 6.31653 17.6805 6.31653 18.071 6.707C18.4615 7.09747 18.4615 7.73053 18.071 8.121L14.3584 11.8336Z"
            fill-opacity="0.4" />
        </svg>
      </a>
    </div>
  <?php endif; ?>

  <div class="menu-inner-shadow"></div>

  <?php
    // Only show Admin vertical menu to admin users
    $showAdminMenu = auth()->check() && auth()->user()->role === 'admin';
  ?>
  <ul class="menu-inner py-1">
    <?php if($showAdminMenu): ?>
    <?php $__currentLoopData = $menuData[0]->menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      

      
      <?php if(isset($menu->menuHeader)): ?>
        <li class="menu-header small mt-5">
          <span class="menu-header-text"><?php echo e(__($menu->menuHeader)); ?></span>
        </li>
      <?php else: ?>
        
        <?php
          $activeClass = null;
          $currentRouteName = Route::currentRouteName();

          if ($currentRouteName === $menu->slug) {
              $activeClass = 'active';
          } elseif (isset($menu->submenu)) {
              if (gettype($menu->slug) === 'array') {
                  foreach ($menu->slug as $slug) {
                      if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
                          $activeClass = 'active open';
                      }
                  }
              } else {
                  if (str_contains($currentRouteName, $menu->slug) and strpos($currentRouteName, $menu->slug) === 0) {
                      $activeClass = 'active open';
                  }
              }
          }
        ?>

        
        <li class="menu-item <?php echo e($activeClass); ?>">
          <a href="<?php echo e(isset($menu->url) ? url($menu->url) : 'javascript:void(0);'); ?>"
            class="<?php echo e(isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link'); ?>"
            <?php if(isset($menu->target) and !empty($menu->target)): ?> target="_blank" <?php endif; ?>>
            <?php if(isset($menu->icon)): ?>
              <i class="<?php echo e($menu->icon); ?>"></i>
            <?php endif; ?>
            <div><?php echo e(isset($menu->name) ? __($menu->name) : ''); ?></div>
            <?php if(isset($menu->badge)): ?>
              <div class="badge bg-<?php echo e($menu->badge[0]); ?> rounded-pill ms-auto"><?php echo e($menu->badge[1]); ?></div>
            <?php endif; ?>
          </a>

          
          <?php if(isset($menu->submenu)): ?>
            <?php echo $__env->make('layouts.sections.menu.submenu', ['menu' => $menu->submenu], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php endif; ?>
        </li>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
      <?php
        $user = auth()->user();
        $role = $user ? $user->role : null;
      ?>
      <?php if($role === 'student'): ?>
        <li class="menu-item">
          <a href="<?php echo e(route('student.dashboard')); ?>" class="menu-link">
            <i class="menu-icon icon-base ri ri-dashboard-line"></i>
            <div><?php echo e(__('Student Dashboard')); ?></div>
          </a>
        </li>
        <li class="menu-item">
          <a href="<?php echo e(route('student.attendance.today')); ?>" class="menu-link">
            <i class="menu-icon icon-base ri ri-checkbox-circle-line"></i>
            <div><?php echo e(__('Attendance')); ?></div>
          </a>
        </li>
        <li class="menu-item">
          <a href="<?php echo e(route('password.change.edit')); ?>" class="menu-link">
            <i class="menu-icon icon-base ri ri-key-2-line"></i>
            <div><?php echo e(__('Change Password')); ?></div>
          </a>
        </li>
        <li class="menu-item">
          <a href="#" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form-vertical').submit();">
            <i class="menu-icon icon-base ri ri-logout-box-r-line"></i>
            <div><?php echo e(__('Logout')); ?></div>
          </a>
          <form id="logout-form-vertical" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none"><?php echo csrf_field(); ?></form>
        </li>
      <?php elseif($role === 'lecturer'): ?>
        <li class="menu-item">
          <a href="<?php echo e(route('lecturer.attendance.index')); ?>" class="menu-link">
            <i class="menu-icon icon-base ri ri-clipboard-line"></i>
            <div><?php echo e(__('Attendance')); ?></div>
          </a>
        </li>
      <?php else: ?>
        <li class="menu-item">
          <a href="<?php echo e(url('/')); ?>" class="menu-link">
            <i class="menu-icon icon-base ri ri-home-5-line"></i>
            <div><?php echo e(__('Home')); ?></div>
          </a>
        </li>
      <?php endif; ?>
    <?php endif; ?>
  </ul>

</aside>
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>