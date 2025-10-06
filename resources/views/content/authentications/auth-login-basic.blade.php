@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login - Pages')

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-auth.scss'
])
<style>
  /* Login-only override to eliminate any theme-injected brand icon */
  .authentication-wrapper .app-brand-link::before,
  .authentication-wrapper .app-brand-link::after,
  .authentication-wrapper .app-brand-text::before,
  .authentication-wrapper .app-brand-text::after { content: none !important; display: none !important; }
  .authentication-wrapper .app-brand,
  .authentication-wrapper .app-brand::before,
  .authentication-wrapper .app-brand::after,
  .authentication-wrapper .app-brand-link { background: none !important; -webkit-mask-image: none !important; mask-image: none !important; }
  .authentication-wrapper .app-brand .app-brand-text { display: none !important; }
  .authentication-wrapper .app-brand-link svg,
  .authentication-wrapper .app-brand-link i { display: none !important; }
  .authentication-wrapper .app-brand img.app-brand-img { display: inline-block !important; height: 54px !important; width: 54px !important; object-fit: contain !important; }

  /* Google Sign-In button - clean, accessible, brand-consistent */
  .authentication-wrapper .btn-google {
    background-color: #ffffff;
    border: 1px solid #dadce0;
    color: #3c4043;
    font-weight: 600;
    border-radius: 9999px;
    padding: 0.5rem 1rem;
    display: flex;
    width: 100%;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    line-height: 1.25;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(60,64,67,.12);
    transition: box-shadow .15s ease, border-color .15s ease, transform .06s ease;
  }
  .authentication-wrapper .btn-google:hover {
    border-color: #c8cdd3;
    box-shadow: 0 4px 12px rgba(60,64,67,.18);
  }
  .authentication-wrapper .btn-google:active {
    background-color: #f8f9fa;
    transform: translateY(1px);
    box-shadow: 0 1px 4px rgba(60,64,67,.12);
  }
  .authentication-wrapper .btn-google:focus-visible { outline: 2px solid #1a73e8; outline-offset: 2px; }
  .authentication-wrapper .btn-google .google-icon { width: 18px; height: 18px; display: inline-block; }
  .authentication-wrapper .btn-google .google-icon-chip { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 1px solid #e6e8ea; box-shadow: 0 1px 2px rgba(60,64,67,.08); }
  [data-bs-theme="dark"] .authentication-wrapper .btn-google { color: #e9eaeb; background-color: #1f2225; border-color: #3a3f44; box-shadow: 0 2px 8px rgba(0,0,0,.25); }
  [data-bs-theme="dark"] .authentication-wrapper .btn-google:hover { border-color: #4a5056; box-shadow: 0 4px 12px rgba(0,0,0,.35); }
  [data-bs-theme="dark"] .authentication-wrapper .btn-google .google-icon-chip { background: #2a2e32; border-color: #3a3f44; }

  /* New login card design */
  .authentication-wrapper .login-card { border-radius: 12px; box-shadow: 0 10px 24px rgba(21,22,24,.08); overflow: hidden; }
  .authentication-wrapper .login-hero { background: linear-gradient(135deg, #4c63d2, #3b49df); color: #fff; border-top-left-radius: 12px; border-top-right-radius: 12px; padding: 2rem 1rem; text-align: center; }
  .authentication-wrapper .login-title { font-size: 1.5rem; font-weight: 700; margin: 0; }
  .authentication-wrapper .login-subtitle { margin-top: .5rem; opacity: .95; }
  .authentication-wrapper .login-logo-badge { position: relative; width: 96px; height: 96px; background: #fff; border-radius: 50%; box-shadow: 0 6px 16px rgba(60,64,67,.25); border: 6px solid #fff; margin: -48px auto 1.25rem; display: flex; align-items: center; justify-content: center; }
  .authentication-wrapper .login-logo-badge img { width: 72px; height: 72px; object-fit: contain; }
  .authentication-wrapper .btn-login { background: linear-gradient(135deg, #4c63d2, #3b49df); border: none; }
  .authentication-wrapper .btn-login:hover { filter: brightness(1.03); }
  .authentication-wrapper .btn-login:active { filter: brightness(0.97); }
  .authentication-wrapper .form-control::placeholder { color: #9aa0a6; }
</style>
@endsection

@section('page-script')
@vite([
  'resources/assets/js/pages-auth.js'
])
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
      <!-- Login Card -->
      <div class="card login-card p-md-7 p-1">
        <!-- Logo -->
        <div class="app-brand justify-content-center mt-5">
          <a href="{{ url('/') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">@include('_partials.macros')</span>
            <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
          </a>
        </div>
        <!-- /Logo -->

        <div class="card-body mt-1">
          <h4 class="mb-1">Welcome to Katusome Attendance</h4>
          <p class="mb-6">Sign in with your official MUBS credentials to record or manage class attendance.</p>

          <!-- Credentials Login Form -->
          <form id="formAuthentication" class="mb-5" action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus />
              <label for="email">Email</label>
            </div>
            <div class="mb-5 form-password-toggle form-control-validation">
              <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                  <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required />
                  <label for="password">Password</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="icon-base ri ri-eye-off-line icon-20px"></i></span>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-5">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember-me" name="remember" {{ old('remember') ? 'checked' : '' }} />
                <label class="form-check-label" for="remember-me"> Remember Me </label>
              </div>
              <a href="{{ route('password.request') }}" class="mb-1 mt-2">
                <span>Forgot Password?</span>
              </a>
            </div>
            <div class="mb-5">
              <button class="btn btn-login btn-primary d-grid w-100" type="submit">Log In</button>
            </div>
          </form>

          <!-- Divider -->
          <div class="divider my-5">
            <div class="divider-text">or</div>
          </div>

          <!-- Google Sign-In -->
          <div class="mb-5">
            <a href="javascript:;" class="btn btn-google w-100" aria-label="Sign in with Google" title="Sign in with Google" role="button">
              <span class="google-icon-chip" aria-hidden="true">
                <span class="google-icon">
                  <!-- Inline Google 'G' SVG -->
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18">
                    <path fill="#EA4335" d="M9 7.2v3.6h5.1c-.2 1.1-.8 2-1.7 2.6l2.8 2.2C16.6 14.5 18 12 18 9c0-.6-.1-1.2-.2-1.8H9z"/>
                    <path fill="#34A853" d="M3.9 10.8l-.6-.5-2.2 1.7C2.2 14.9 5.3 18 9 18c2.4 0 4.5-.8 6-2.2l-2.8-2.2c-.8.5-1.9.8-3.2.8-2.5 0-4.6-1.7-5.3-4z"/>
                    <path fill="#FBBC05" d="M3.3 10.3c-.2-.6-.3-1.3-.3-2 0-.7.1-1.4.3-2L1.1 4.6C.4 6 .1 7.5.1 9s.3 3 .9 4.4l2.3-1.8z"/>
                    <path fill="#4285F4" d="M9 3.6c1.3 0 2.4.5 3.3 1.4l2.4-2.4C13.5.9 11.4 0 9 0 5.3 0 2.2 3.1 1.1 7.4l2.2 1.7C4.4 5.3 6.5 3.6 9 3.6z"/>
                  </svg>
                </span>
              </span>
              <span>Sign in with Google</span>
            </a>
          </div>

          <!-- Register link -->
          <p class="text-center mb-1">
            <span>New on our platform?</span>
            <a href="{{ route('register') }}">
              <span>Create an account</span>
            </a>
          </p>
        </div>
      </div>
      <!-- /Login Card -->
      <!-- Illustration mask -->
      <img alt="mask"
        src="{{ asset('assets/img/illustrations/auth-basic-login-mask-' . $configData['theme'] . '.png') }}"
        class="authentication-image d-none d-lg-block"
        data-app-light-img="illustrations/auth-basic-login-mask-light.png"
        data-app-dark-img="illustrations/auth-basic-login-mask-dark.png"
        onerror="this.onerror=null;this.src='{{ asset('assets/img/illustrations/auth-basic-register-mask-' . $configData['theme'] . '.png') }}';" />
    </div>
  </div>
</div>
@endsection
