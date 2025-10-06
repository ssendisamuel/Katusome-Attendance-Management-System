@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Change Password')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-md-8 col-lg-6">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Change Password</h5>
        </div>
        <div class="card-body">
          @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
          @endif

          <form method="POST" action="{{ route('password.change.update') }}" class="mb-3">
            @csrf

            <div class="mb-4 form-control-validation">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password" />
              @error('current_password')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4 form-control-validation">
              <label for="password" class="form-label">New Password</label>
              <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password" />
              @error('password')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4 form-control-validation">
              <label for="password_confirmation" class="form-label">Confirm New Password</label>
              <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password" />
            </div>

            <button type="submit" class="btn btn-primary">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection