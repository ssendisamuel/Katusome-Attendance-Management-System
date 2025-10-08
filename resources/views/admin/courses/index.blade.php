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
  <div class="card-body">
    <form id="courseFilters" method="GET" action="{{ route('admin.courses.index') }}" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Search by name</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. Web Development" />
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
        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print" data-export-target="#coursesTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#coursesTableEl" data-title="Courses" data-filename="Courses.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Courses" data-json-url="{{ route('admin.courses.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#coursesTableEl" data-title="Courses" data-filename="Courses.xlsx" data-json-url="{{ route('admin.courses.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="coursesTableEl">
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
                <button type="submit" class="btn btn-sm btn-outline-danger js-delete-course" data-name="{{ $course->name }}">Delete</button>
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

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script>
  (function () {
    document.querySelectorAll('.js-delete-course').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this course';
        if (window.Swal && window.Swal.fire) {
          window.Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
          }).then(function (result) {
            if (result.isConfirmed) form.submit();
          });
        } else {
          if (confirm('Delete ' + name + '?')) form.submit();
        }
      });
    });
  })();
</script>
@endsection