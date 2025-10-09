@extends('layouts/layoutMaster')

@section('title', 'Course Lecturer Assignments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Assign Lecturers to Courses</h4>
  <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">Manage Courses</a>
</div>
@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
<div class="card">
  <div class="card-body">
    <form id="assignmentFilters" method="GET" action="{{ route('admin.course-lecturers.index') }}" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Search by course name or code</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. Web, ICS101" />
      </div>
      <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
          <option value="">All Programs</option>
          @foreach($programs as $program)
            <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.course-lecturers.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <hr />
    @include('admin.course_lecturers.partials.table', ['courses' => $courses])
  </div>
</div>
@endsection