@php
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Register Basic - Pages')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
  <style>
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
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
  <div class="position-relative">
    <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
      <div class="authentication-inner py-6">
        <!-- Register Card -->
        <div class="card p-md-7 p-1">
          <!-- Logo -->
          <div class="app-brand justify-content-center mt-5">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          <div class="card-body mt-1">
            <h4 class="mb-1">Create your account</h4>
            <p class="mb-5">Provide your details to get started.</p>

            <form id="formAuthentication" class="mb-5" action="{{ route('register.post') }}" method="POST">
              @csrf
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control" id="name" name="name"
                  placeholder="Enter your full name" value="{{ old('name') }}" autofocus required />
                <label for="name">Full Name</label>
                @error('name')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="email" class="form-control" id="email" name="email" placeholder="e.g., 220003232@mubs.ac.ug" value="{{ old('email') }}" pattern="^[^@\s]+@mubs\.ac\.ug$" title="Email must be a mubs.ac.ug address" required />
                <label for="email">Email</label>
                <small class="text-muted">Only MUBS emails are allowed (e.g., 220003232@mubs.ac.ug).</small>
                @error('email')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <!-- Student Details -->
              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter your phone" value="{{ old('phone') }}" />
                <label for="phone">Phone</label>
                @error('phone')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <select class="form-select" id="gender" name="gender">
                  <option value="" selected>Choose gender (optional)</option>
                  <option value="male" {{ old('gender')==='male' ? 'selected' : '' }}>Male</option>
                  <option value="female" {{ old('gender')==='female' ? 'selected' : '' }}>Female</option>
                  <option value="other" {{ old('gender')==='other' ? 'selected' : '' }}>Other</option>
                </select>
                <label for="gender">Gender</label>
                @error('gender')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control" id="student_no" name="student_no" placeholder="Enter your student number" value="{{ old('student_no') }}" required />
                <label for="student_no">Student Number</label>
                @error('student_no')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="text" class="form-control" id="reg_no" name="reg_no" placeholder="Enter your registration number (optional)" value="{{ old('reg_no') }}" />
                <label for="reg_no">Registration Number</label>
                @error('reg_no')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <select class="form-select" id="program_id" name="program_id" required>
                  <option value="" selected>Select your program</option>
                  @foreach($programs as $program)
                    <option value="{{ $program->id }}" {{ old('program_id')==$program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                  @endforeach
                </select>
                <label for="program_id">Program</label>
                @error('program_id')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <select class="form-select" id="group_id" name="group_id" required>
                  <option value="" selected>Select your group</option>
                </select>
                <label for="group_id">Group</label>
                @error('group_id')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-floating form-floating-outline mb-5 form-control-validation">
                <input type="number" class="form-control" id="year_of_study" name="year_of_study" min="1" max="10" placeholder="Enter your year of study" value="{{ old('year_of_study', 1) }}" />
                <label for="year_of_study">Year of Study</label>
                @error('year_of_study')
                  <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>
              <div class="mb-5 form-password-toggle form-control-validation">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="password" class="form-control" name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" required />
                      <label for="password">Password</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i
                      class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                  </div>
                </div>
                @error('password')
                  <small class="text-danger">{{ $message }}</small>
                @enderror

              <div class="mb-5 form-control-validation">
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" />
                  <label class="form-check-label" for="terms-conditions">
                    I agree to
                    <a href="javascript:void(0);">privacy policy & terms</a>
                  </label>
                </div>
              </div>
              <button class="btn btn-primary d-grid w-100 mb-5">Sign up</button>
            </form>

            <p class="text-center mb-5">
              <span>Already have an account?</span>
              <a href="{{ route('login') }}">
                <span>Sign in instead</span>
              </a>
            </p>

            <div class="divider my-5">
              <div class="divider-text">or</div>
            </div>
            <div class="mb-5">
              <a href="{{ route('oauth.google.redirect') }}" class="btn btn-google w-100" aria-label="Sign in with Google" title="Sign in with Google" role="button">
                <span class="google-icon-chip" aria-hidden="true">
                  <span class="google-icon">
                    <!-- Inline Google 'G' SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" width="18" height="18" aria-hidden="true" focusable="false">
                      <path fill="#EA4335" d="M9 7.2v3.6h5.1c-.2 1.1-.8 2-1.7 2.6l2.8 2.2C16.6 14.5 18 12 18 9c0-.6-.1-1.2-.2-1.8H9z"/>
                      <path fill="#34A853" d="M3.9 10.8l-.6-.5-2.2 1.7C2.2 14.9 5.3 18 9 18c2.4 0 4.5-.8 6-2.2l-2.8-2.2c-.8.5-1.9.8-3.2.8-2.5 0-4.6-1.7-5.3-4z"/>
                      <path fill="#FBBC05" d="M3.3 10.3c-.2-.6-.3-1.3-.3-2 0-.7.1-1.4.3-2L1.1 4.6C.4 6 .1 7.5.1 9s.3 3 .9 4.4l2.3-1.8z"/>
                      <path fill="#4285F4" d="M9 3.6c1.3 0 2.4.5 3.3 1.3l2.4-2.4C13.8.9 11.6 0 9 0 5.3 0 2.2 2.1.9 5.1l2.4 1.9C3.6 5 6.1 3.6 9 3.6z"/>
                    </svg>
                  </span>
                </span>
                <span>Sign in with Google</span>
              </a>
            </div>
          </div>
        </div>
        <!-- Register Card -->
        <img alt="mask"
          src="{{ asset('assets/img/illustrations/auth-basic-register-mask-' . $configData['theme'] . '.png') }}"
          class="authentication-image d-none d-lg-block"
          data-app-light-img="illustrations/auth-basic-register-mask-light.png"
          data-app-dark-img="illustrations/auth-basic-register-mask-dark.png" />
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const programSelect = document.getElementById('program_id');
      const groupSelect = document.getElementById('group_id');
      const allGroups = @json($groups->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'program_id' => $g->program_id]));
      const oldGroupId = {{ json_encode(old('group_id')) }};

      function populateGroups(programId) {
        // Clear current options
        groupSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select your group';
        placeholder.selected = true;
        groupSelect.appendChild(placeholder);

        if (!programId) { return; }
        const filtered = allGroups.filter(g => String(g.program_id) === String(programId));
        filtered.forEach(g => {
          const opt = document.createElement('option');
          opt.value = g.id;
          opt.textContent = g.name;
          if (String(oldGroupId) === String(g.id)) opt.selected = true;
          groupSelect.appendChild(opt);
        });
      }

      // Initialize on load
      if (programSelect) {
        populateGroups(programSelect.value);
        programSelect.addEventListener('change', function() {
          populateGroups(this.value);
        });
      }
    });
  </script>
@endsection

