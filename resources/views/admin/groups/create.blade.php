@extends('layouts/layoutMaster')

@section('title', 'Add Group')

@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Create Group</h5></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.groups.store') }}">
      @csrf
      <div class="mb-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <div class="mb-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select" required>
          <option value="">Select Program</option>
          @foreach($programs as $program)
            <option value="{{ $program->id }}" @selected(old('program_id')===$program->id)>{{ $program->name }} ({{ $program->code }})</option>
          @endforeach
        </select>
        @error('program_id')<small class="text-danger">{{ $message }}</small>@enderror
      </div>
      <button class="btn btn-primary" type="submit">Save</button>
      <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection