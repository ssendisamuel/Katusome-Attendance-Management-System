@extends('layouts/layoutMaster')

@section('title', 'Edit Group')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Edit Group</h5>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.groups.update', $group) }}">
        @csrf
        @method('PUT')
        <div class="mb-4">
          <label class="form-label">Group Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $group->name) }}" placeholder="e.g. A, B, C" required>
          <small class="text-muted">Enter a simple group identifier (A-G, or any other name)</small>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <button class="btn btn-primary" type="submit">Update</button>
        <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
@endsection
