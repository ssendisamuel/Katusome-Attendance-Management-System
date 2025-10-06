@extends('layouts/layoutMaster')

@section('title', 'Programs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Programs</h4>
  <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">Add Program</a>
  </div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Code</th>
          <th>Groups</th>
          <th>Courses</th>
          <th>Students</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($programs as $program)
          <tr>
            <td>{{ $program->name }}</td>
            <td>{{ $program->code }}</td>
            <td>{{ $program->groups_count }}</td>
            <td>{{ $program->courses_count }}</td>
            <td>{{ $program->students_count }}</td>
            <td class="text-end">
              <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.programs.destroy', $program) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this program?')">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $programs->links() }}</div>
</div>
@endsection