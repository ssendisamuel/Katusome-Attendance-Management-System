@extends('layouts/layoutMaster')

@section('title', 'Add Program')

@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Create Program</h5></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.programs.store') }}">
      @csrf
      <div class="mb-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
        @error('code')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <button class="btn btn-primary" type="submit">Save</button>
      <a href="{{ route('admin.programs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection