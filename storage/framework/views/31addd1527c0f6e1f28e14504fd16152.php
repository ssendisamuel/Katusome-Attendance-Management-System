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
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal  menu flex-grow-0" <?php echo $menuAttributesHtml; ?>>
  <?php
    $isAuthenticated = auth()->check();
    $userRole = $isAuthenticated ? auth()->user()->role : null;
  ?>
  <div class="<?php echo e($containerNav); ?> d-flex h-100">
    <ul class="menu-inner">
      <?php $__currentLoopData = $menuData[1]->menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          // Skip Login/Register when authenticated
          if ($isAuthenticated && in_array($menu->slug ?? '', ['login','register'])) {
            continue;
          }
        ?>
        
        <?php
          $activeClass = null;
          $currentRouteName = Route::currentRouteName();

          if ($currentRouteName === $menu->slug) {
              $activeClass = 'active';
          } elseif (isset($menu->submenu)) {
              if (gettype($menu->slug) === 'array') {
                  foreach ($menu->slug as $slug) {
                      if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
                          $activeClass = 'active';
                      }
                  }
              } else {
                  if (str_contains($currentRouteName, $menu->slug) and strpos($currentRouteName, $menu->slug) === 0) {
                      $activeClass = 'active';
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
          </a>

          
          <?php if(isset($menu->submenu)): ?>
            <?php echo $__env->make('layouts.sections.menu.submenu', ['menu' => $menu->submenu], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
          <?php endif; ?>
        </li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      <?php if($isAuthenticated): ?>
        <?php if($userRole === 'student'): ?>
          <li class="menu-item">
            <a href="<?php echo e(route('student.dashboard')); ?>" class="menu-link">
              <i class="menu-icon icon-base ri ri-dashboard-line"></i>
              <div><?php echo e(__('Student Dashboard')); ?></div>
            </a>
          </li>
          <li class="menu-item">
            <a href="<?php echo e(route('attendance.checkin.create')); ?>" class="menu-link">
              <i class="menu-icon icon-base ri ri-checkbox-circle-line"></i>
              <div><?php echo e(__('Check In')); ?></div>
            </a>
          </li>
        <?php elseif($userRole === 'lecturer'): ?>
          <li class="menu-item">
            <a href="<?php echo e(route('lecturer.attendance.index')); ?>" class="menu-link">
              <i class="menu-icon icon-base ri ri-clipboard-line"></i>
              <div><?php echo e(__('Attendance')); ?></div>
            </a>
          </li>
        <?php elseif($userRole === 'admin'): ?>
          <li class="menu-item">
            <a href="<?php echo e(url('/admin/dashboard')); ?>" class="menu-link">
              <i class="menu-icon icon-base ri ri-bar-chart-line"></i>
              <div><?php echo e(__('Admin Dashboard')); ?></div>
            </a>
          </li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
</aside>
<!--/ Horizontal Menu -->
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\menu\horizontalMenu.blade.php ENDPATH**/ ?>