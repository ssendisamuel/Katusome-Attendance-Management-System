<?php
  // Avoid relying on the Auth facade alias in Blade; use the request-bound helper
  $defaultAvatar = asset('assets/img/avatars/1.png');
  $user = auth()->user();
  $userHasImage = false;
  $avatarUrl = $defaultAvatar;
  $initials = 'U';
  if ($user) {
    $userHasImage = !empty($user->avatar_url ?? '') || !empty($user->profile_photo_url ?? '');
    $avatarUrl = !empty($user->avatar_url ?? '')
      ? $user->avatar_url
      : (!empty($user->profile_photo_url ?? '') ? $user->profile_photo_url : $defaultAvatar);
    $name = trim($user->name ?? 'User');
    $initials = collect(explode(' ', $name))
      ->map(fn($p) => mb_substr($p, 0, 1))
      ->implode('');
    $initials = mb_strtoupper(mb_substr($initials, 0, 2));
  }
  // Role label for display in dropdown
  $roleLabel = 'User';
  if ($user && !empty($user->role)) {
    if ($user->role === 'student') { $roleLabel = 'Student'; }
    elseif ($user->role === 'lecturer') { $roleLabel = 'Lecturer'; }
    elseif ($user->role === 'admin') { $roleLabel = 'Admin'; }
  }
?>

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
<?php if(isset($navbarFull)): ?>
  <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
    <a href="<?php echo e(url('/')); ?>" class="app-brand-link gap-2">
      <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
  <span class="app-brand-text demo menu-text fw-semibold ms-1"><?php echo e(config('variables.templateName')); ?></span>
    </a>

    <!-- Display menu close icon only for horizontal-menu with navbar-full -->
    <?php if(isset($menuHorizontal)): ?>
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
        <i class="icon-base ri ri-close-line icon-sm"></i>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- ! Not required for layout-without-menu -->
<?php if(!isset($navbarHideToggle)): ?>
  <div
    class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 <?php echo e(isset($menuHorizontal) ? ' d-xl-none ' : ''); ?> <?php echo e(isset($contentNavbar) ? ' d-xl-none ' : ''); ?>">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base ri ri-menu-line icon-md"></i>
    </a>
  </div>
<?php endif; ?>

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
  <?php if($configData['hasCustomizer'] == true): ?>
    <!-- Search -->
    <div class="navbar-nav align-items-center">
      <li class="nav-item dropdown me-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
          data-bs-toggle="dropdown">
          <i class="icon-base ri ri-sun-line icon-md theme-icon-active"></i>
          <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
          <li>
            <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
              aria-pressed="false">
              <span><i class="icon-base ri ri-sun-line icon-22px me-3" data-icon="sun-line"></i>Light</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark"
              aria-pressed="true">
              <span><i class="icon-base ri ri-moon-clear-line icon-22px me-3"
                  data-icon="moon-clear-line"></i>Dark</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
              aria-pressed="false">
              <span><i class="icon-base ri ri-computer-line icon-22px me-3" data-icon="computer-line"></i>System</span>
            </button>
          </li>
        </ul>
      </li>
    </div>
    <!-- / Style Switcher-->
  <?php endif; ?>
  <ul class="navbar-nav flex-row align-items-center ms-auto">
    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          <?php if($user && $userHasImage): ?>
            <img src="<?php echo e($avatarUrl); ?>" alt="User avatar" class="rounded-circle" width="40" height="40" onerror="this.onerror=null;this.src='<?php echo e($defaultAvatar); ?>';" />
          <?php else: ?>
            <span class="avatar-initial rounded-circle bg-label-primary w-px-40 h-px-40 d-flex align-items-center justify-content-center"><?php echo e($initials); ?></span>
          <?php endif; ?>
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
        <li>
          <a class="dropdown-item" href="<?php echo e(route('profile.show')); ?>">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  <?php if($user && $userHasImage): ?>
                    <img src="<?php echo e($avatarUrl); ?>" alt="User avatar" class="w-px-40 h-auto rounded-circle" width="40" height="40" onerror="this.onerror=null;this.src='<?php echo e($defaultAvatar); ?>';" />
                  <?php else: ?>
                    <span class="avatar-initial rounded-circle bg-label-primary w-px-40 h-px-40 d-flex align-items-center justify-content-center"><?php echo e($initials); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0 small"><?php echo e($user ? $user->name : 'John Doe'); ?></h6>
                <?php
                  $roleLabel = $user ? ucfirst($user->role ?? 'User') : 'Guest';
                ?>
                <small class="text-body-secondary"><?php echo e($roleLabel); ?></small>
              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider"></div>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo e(route('profile.show')); ?>">
            <i class="icon-base ri ri-user-3-line icon-22px me-2"></i> <span class="align-middle">My
              Profile</span> </a>
        </li>
        <?php
          $jetstreamEnabled = class_exists('Laravel\\Jetstream\\Jetstream');
        ?>
        <?php if($user && $jetstreamEnabled && Laravel\Jetstream\Jetstream::hasApiFeatures()): ?>
          <li>
            <a class="dropdown-item" href="<?php echo e(route('api-tokens.index')); ?>"> <i
                class="icon-base ri ri-settings-4-line icon-22px me-3"></i><span class="align-middle">Settings</span>
            </a>
          </li>
        <?php endif; ?>
        <li>
          <?php
            $userRole = $user ? ($user->role ?? 'user') : null;
          ?>
          <?php if($user): ?>
            <?php if($userRole === 'student'): ?>
              <a class="dropdown-item" href="<?php echo e(route('student.attendance.today')); ?>">
                <i class="icon-base ri ri-checkbox-circle-line icon-22px me-3"></i>
                <span class="align-middle">Attendance Today</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="<?php echo e(route('student.dashboard')); ?>">
                <i class="icon-base ri ri-dashboard-line icon-22px me-3"></i>
                <span class="align-middle">Student Dashboard</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="<?php echo e(route('password.change.edit')); ?>">
                <i class="icon-base ri ri-key-2-line icon-22px me-3"></i>
                <span class="align-middle">Change Password</span>
              </a>
            <?php elseif($userRole === 'lecturer'): ?>
              <a class="dropdown-item" href="<?php echo e(route('lecturer.attendance.index')); ?>">
                <i class="icon-base ri ri-clipboard-line icon-22px me-3"></i>
                <span class="align-middle">Attendance</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="<?php echo e(route('password.change.edit')); ?>">
                <i class="icon-base ri ri-key-2-line icon-22px me-3"></i>
                <span class="align-middle">Change Password</span>
              </a>
            <?php elseif($userRole === 'admin'): ?>
              <a class="dropdown-item" href="<?php echo e(url('/admin/dashboard')); ?>">
                <i class="icon-base ri ri-bar-chart-line icon-22px me-3"></i>
                <span class="align-middle">Admin Dashboard</span>
              </a>
            <?php endif; ?>
          <?php endif; ?>
        </li>
        <?php if($user && $jetstreamEnabled && Laravel\Jetstream\Jetstream::hasTeamFeatures()): ?>
          <li>
            <div class="dropdown-divider"></div>
          </li>
          <li>
            <h6 class="dropdown-header">Manage Team</h6>
          </li>
          <li>
            <div class="dropdown-divider my-1"></div>
          </li>
          <li>
            <a class="dropdown-item"
              href="<?php echo e($user && ($user->currentTeam ?? null) ? route('teams.show', $user->currentTeam->id) : 'javascript:void(0)'); ?>">
              <i class="icon-base ri ri-settings-3-line icon-md me-3"></i><span>Team Settings</span>
            </a>
          </li>
          <?php if($jetstreamEnabled): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', Laravel\Jetstream\Jetstream::newTeamModel())): ?>
              <li>
                <a class="dropdown-item" href="<?php echo e(route('teams.create')); ?>">
                  <i class="icon-base ri ri-group-line icon-md me-3"></i><span>Create New Team</span>
                </a>
              </li>
            <?php endif; ?>
          <?php endif; ?>
          <?php if($user && $user->allTeams()->count() > 1): ?>
            <li>
              <div class="dropdown-divider my-1"></div>
            </li>
            <li>
              <h6 class="dropdown-header">Switch Teams</h6>
            </li>
            <li>
              <div class="dropdown-divider my-1"></div>
            </li>
          <?php endif; ?>
          <?php if($user): ?>
            <?php $__currentLoopData = $user->allTeams(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              

              
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php endif; ?>
        <?php endif; ?>
        <li>
          <div class="dropdown-divider my-1"></div>
        </li>
        <?php if(auth()->guard()->check()): ?>
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <a class="btn btn-danger d-flex" href="<?php echo e(route('logout')); ?>"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <small class=" align-middle">Logout</small>
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
              </a>
            </div>
          </li>
          <form method="POST" id="logout-form" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
          </form>
        <?php endif; ?>
        <?php if(auth()->guard()->guest()): ?>
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <a class="btn btn-danger d-flex" href="<?php echo e(route('login')); ?>">
                <small class="align-middle">Login</small>
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
              </a>
            </div>
          </li>
        <?php endif; ?>
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views/layouts/sections/navbar/navbar-partial.blade.php ENDPATH**/ ?>