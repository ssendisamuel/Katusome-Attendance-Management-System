@extends('layouts/layoutMaster')

@section('title', 'Add Lecturer')

@section('content')
<h4 class="mb-4">Add Lecturer</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.lecturers.store') }}" id="lecturerCreateForm">
    @csrf
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email') }}">
      @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
      @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Initial Password (optional)</label>
      <input type="text" name="initial_password" class="form-control" value="{{ old('initial_password') }}" placeholder="Defaults to 'password' if blank">
      @error('initial_password')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary" id="btnSaveLecturer" data-loading-text="Adding Lecturer...">Save</button>
    <a href="{{ route('admin.lecturers.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@endsection

@section('page-script')
<script>
  (function() {
    const form = document.getElementById('lecturerCreateForm');
    const submitBtn = document.getElementById('btnSaveLecturer');
    if (!form || !submitBtn) return;
    form.addEventListener('submit', function() {
      // Prevent duplicate submissions and show loading state until reload/redirect
      submitBtn.disabled = true;
      const loadingText = submitBtn.getAttribute('data-loading-text') || 'Processing...';
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loadingText;
    });
  })();
</script>
@endsection