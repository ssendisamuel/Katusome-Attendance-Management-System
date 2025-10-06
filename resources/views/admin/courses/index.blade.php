@extends('layouts/layoutMaster')

@section('title', 'Courses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Courses</h4>
  <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">Add Course</a>
</div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
          <th>Program</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($courses as $course)
          <tr>
            <td>{{ $course->code }}</td>
            <td>{{ $course->name }}</td>
            <td>{{ optional($course->program)->name }}</td>
            <td class="text-end">
              <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this course?')">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $courses->links() }}</div>
</div>
@endsection