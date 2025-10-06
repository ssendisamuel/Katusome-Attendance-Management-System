@php
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Forgot Password')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
      <div class="card p-md-7 p-1">
        <div class="card-body mt-1">
          <h4 class="mb-1">Reset your password</h4>
          <p class="mb-5">Enter your email to receive a reset link.</p>

          @if(session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
          @endif

          <form method="POST" action="{{ route('password.email') }}" class="mb-5">
            @csrf
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus />
              <label for="email">Email</label>
              @error('email')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary d-grid w-100" type="submit">Send Reset Link</button>
          </form>

          <p class="text-center mb-1">
            <a href="{{ route('login') }}">Back to Login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
