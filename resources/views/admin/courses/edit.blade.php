@extends('layouts/layoutMaster')

@section('title', 'Edit Course')

@section('content')
<h4 class="mb-4">Edit Course</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.courses.update', $course) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Code</label>
      <input type="text" name="code" class="form-control" value="{{ old('code', $course->code) }}" required>
      @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="{{ old('name', $course->name) }}" required>
      @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control">{{ old('description', $course->description) }}</textarea>
      @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Program</label>
      <select name="program_id" class="form-select" required>
        @foreach($programs as $program)
          <option value="{{ $program->id }}" @selected(old('program_id', $course->program_id) == $program->id)>{{ $program->name }}</option>
        @endforeach
      </select>
      @error('program_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-primary">Update</button>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@endsection