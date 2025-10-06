@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'My Profile')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12 col-lg-8">
      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Profile Information</h5>
          @if(session('success'))
            <span class="badge bg-success">{{ session('success') }}</span>
          @endif
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('profile.update') }}" class="row g-4">
            @csrf

            <div class="col-md-6 form-control-validation">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required />
              @error('name')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 form-control-validation">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required />
              @error('email')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            @if($student)
              <div class="col-md-6 form-control-validation">
                <label class="form-label">Student Number</label>
                <input type="text" class="form-control" value="{{ $student->student_no }}" disabled />
              </div>
              <div class="col-md-6 form-control-validation">
                <label class="form-label">Registration Number</label>
                <input type="text" class="form-control" value="{{ $student->reg_no }}" disabled />
              </div>
              <div class="col-md-6 form-control-validation">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}" />
                @error('phone')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6 form-control-validation">
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select">
                  @php $g = old('gender', $student->gender); @endphp
                  <option value="" {{ $g === null ? 'selected' : '' }}>Select</option>
                  <option value="male" {{ $g === 'male' ? 'selected' : '' }}>Male</option>
                  <option value="female" {{ $g === 'female' ? 'selected' : '' }}>Female</option>
                  <option value="other" {{ $g === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6 form-control-validation">
                <label class="form-label">Program</label>
                <input type="text" class="form-control" value="{{ optional($student->program)->name }}" disabled />
              </div>
              <div class="col-md-6 form-control-validation">
                <label class="form-label">Group</label>
                <input type="text" class="form-control" value="{{ optional($student->group)->name }}" disabled />
              </div>
            @endif

            @if($lecturer)
              <div class="col-md-6 form-control-validation">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $lecturer->phone) }}" />
                @error('phone')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
              </div>
            @endif

            <div class="col-12">
              <button type="submit" class="btn btn-primary">Save Changes</button>
              <a href="{{ url()->previous() }}" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-4">
            <div class="position-relative avatar avatar-xl me-4">
              @php
                $hasAvatar = $user && !empty($user->avatar_url);
                $name = $user->name ?? 'User';
                $initials = collect(explode(' ', $name))->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                $initials = mb_strtoupper(mb_substr($initials, 0, 2));
              @endphp
              @if($hasAvatar)
                <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle w-100 h-100" />
              @else
                <span class="avatar-initial rounded-circle bg-label-primary w-100 h-100 d-flex align-items-center justify-content-center" style="font-size:1.25rem;">{{ $initials }}</span>
              @endif
              <button type="button" class="btn btn-icon btn-sm btn-primary rounded-circle position-absolute" style="right:-6px;bottom:-6px; z-index:2;" data-bs-toggle="modal" data-bs-target="#changeAvatarModal" aria-label="Change avatar">
                <i class="icon-base ri ri-pencil-line icon-16px"></i>
              </button>
            </div>
            <div>
              <h6 class="mb-1">{{ $user->name }}</h6>
              <small class="text-muted">{{ ucfirst($user->role) }}</small>
            </div>
          </div>
          <p class="mb-4">Manage your personal information used across the attendance system.</p>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="{{ route('password.change.edit') }}">Change Password</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
  <!-- Change Avatar Modal inline -->
<div class="modal fade" id="changeAvatarModal" tabindex="-1" aria-labelledby="changeAvatarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changeAvatarLabel">Change Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="context" value="avatar" />
        <div class="modal-body">
          <div class="mb-3">
            <label for="avatar" class="form-label">Upload new avatar</label>
            <input class="form-control" type="file" id="avatar" name="avatar" accept="image/*">
            <div class="form-text">Max 2MB. JPG, PNG, or WebP.</div>
            @error('avatar')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection