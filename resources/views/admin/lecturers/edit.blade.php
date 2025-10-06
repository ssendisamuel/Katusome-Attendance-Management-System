@extends('layouts/layoutMaster')

@section('title', 'Edit Lecturer')

@section('content')
<h4 class="mb-4">Edit Lecturer</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.lecturers.update', $lecturer) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="{{ old('name', $lecturer->name) }}" required>
      @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email', $lecturer->email) }}">
      @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="{{ old('phone', $lecturer->phone) }}">
      @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-primary">Update</button>
    <a href="{{ route('admin.lecturers.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@endsection