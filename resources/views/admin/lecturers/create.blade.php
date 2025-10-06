@extends('layouts/layoutMaster')

@section('title', 'Add Lecturer')

@section('content')
<h4 class="mb-4">Add Lecturer</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.lecturers.store') }}">
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
    <button class="btn btn-primary">Save</button>
    <a href="{{ route('admin.lecturers.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@endsection