@php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $menuAttributesHtml = '';
  if (!empty($configData['menuAttributes']) && is_iterable($configData['menuAttributes'])) {
      foreach ($configData['menuAttributes'] as $attribute => $value) {
          $menuAttributesHtml .= ' ' . $attribute . '="' . e($value) . '"';
      }
  }
@endphp
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal  menu flex-grow-0" {!! $menuAttributesHtml !!}>
  @php
    $isAuthenticated = auth()->check();
    $userRole = $isAuthenticated ? auth()->user()->role : null;
  @endphp
  <div class="{{ $containerNav }} d-flex h-100">
    <ul class="menu-inner">
      @foreach ($menuData[1]->menu as $menu)
        @php
          // Skip Login/Register when authenticated
          if ($isAuthenticated && in_array($menu->slug ?? '', ['login','register'])) {
            continue;
          }
        @endphp
        {{-- active menu method --}}
        @php
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
        @endphp

        {{-- main menu --}}
        <li class="menu-item {{ $activeClass }}">
          <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
            class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
            @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
            @isset($menu->icon)
              <i class="{{ $menu->icon }}"></i>
            @endisset
            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
          </a>

          {{-- submenu --}}
          @isset($menu->submenu)
            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
          @endisset
        </li>
      @endforeach

      @if($isAuthenticated)
        @if($userRole === 'student')
          <li class="menu-item">
            <a href="{{ route('student.dashboard') }}" class="menu-link">
              <i class="menu-icon icon-base ri ri-dashboard-line"></i>
              <div>{{ __('Student Dashboard') }}</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{ route('student.attendance.today') }}" class="menu-link">
              <i class="menu-icon icon-base ri ri-checkbox-circle-line"></i>
              <div>{{ __('Attendance') }}</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="{{ route('password.change.edit') }}" class="menu-link">
              <i class="menu-icon icon-base ri ri-key-2-line"></i>
              <div>{{ __('Change Password') }}</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="#" class="menu-link" onclick="event.preventDefault(); document.getElementById('logout-form-horizontal').submit();">
              <i class="menu-icon icon-base ri ri-logout-box-r-line"></i>
              <div>{{ __('Logout') }}</div>
            </a>
            <form id="logout-form-horizontal" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
          </li>
        @elseif($userRole === 'lecturer')
          <li class="menu-item">
            <a href="{{ route('lecturer.attendance.index') }}" class="menu-link">
              <i class="menu-icon icon-base ri ri-clipboard-line"></i>
              <div>{{ __('Attendance') }}</div>
            </a>
          </li>
        @elseif($userRole === 'admin')
          <li class="menu-item">
            <a href="{{ url('/admin/dashboard') }}" class="menu-link">
              <i class="menu-icon icon-base ri ri-bar-chart-line"></i>
              <div>{{ __('Admin Dashboard') }}</div>
            </a>
          </li>
        @endif
      @endif
    </ul>
  </div>
</aside>
<!--/ Horizontal Menu -->
