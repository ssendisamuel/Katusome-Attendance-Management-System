@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Complete Profile')

@section('content')
<div class="misc-wrapper">
  <div class="container">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-4">
        <!-- Complete Profile -->
        <div class="card">
          <div class="card-body">
            <h4 class="mb-2">Complete Your Profile</h4>
            <p class="mb-4">We need a few more details to finish setting up your student account.</p>

            <form method="POST" action="{{ route('oauth.google.complete-profile') }}">
              @csrf
              <div class="alert alert-info small">
                For convenience, you can set a password now so you may log in with email/password in addition to Google.
              </div>
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" value="{{ $user->name }}" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="{{ $user->email }}" disabled>
              </div>

              <div class="mb-3">
                <label for="program_id" class="form-label">Program</label>
                <select id="program_id" name="program_id" class="form-select" required>
                  <option value="">Select your program</option>
                  @foreach($programs as $program)
                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="mb-3">
                <label for="group_id" class="form-label">Group</label>
                <select id="group_id" name="group_id" class="form-select" required>
                  <option value="">Select your group</option>
                  @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="mb-3">
                <label for="student_no" class="form-label">Student Number</label>
                <input id="student_no" name="student_no" type="text" class="form-control" required>
              </div>

              <div class="mb-3">
                <label for="reg_no" class="form-label">Registration Number (optional)</label>
                <input id="reg_no" name="reg_no" type="text" class="form-control">
              </div>

              <div class="mb-3">
                <label for="year_of_study" class="form-label">Year of Study</label>
                <input id="year_of_study" name="year_of_study" type="number" min="1" max="6" class="form-control" value="1">
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">Set Password</label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="new-password" placeholder="Choose a strong password" required>
              </div>
              <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" placeholder="Re-enter password" required>
              </div>

              <button type="submit" class="btn btn-primary d-grid w-100">Finish</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection