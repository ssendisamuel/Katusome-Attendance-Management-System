@extends('layouts/layoutMaster')

@section('title', 'Edit Program')

@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Edit Program</h5></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.programs.update', $program) }}">
      @csrf
      @method('PUT')
      <div class="mb-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $program->name) }}" required>
        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $program->code) }}" required>
        @error('code')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <button class="btn btn-primary" type="submit">Update</button>
      <a href="{{ route('admin.programs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection