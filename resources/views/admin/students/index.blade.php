@extends('layouts/layoutMaster')

@section('title', 'Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Students</h4>
  <div>
    <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary me-2">Bulk Upload</a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">Add Student</a>
  </div>
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
          <th>Program</th>
          <th>Group</th>
          <th>Year</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($students as $student)
          <tr>
            <td>{{ $student->name }}</td>
            <td>{{ optional($student->program)->name }}</td>
            <td>{{ optional($student->group)->name }}</td>
            <td>{{ $student->year_of_study }}</td>
            <td class="text-end">
              <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this student?')">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $students->links() }}</div>
</div>
@endsection