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
  <div class="card-body">
    <form id="programFilters" method="GET" action="{{ route('admin.programs.index') }}" class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Search by name or code</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. CS, Computer Science" />
      </div>
      <div class="col-md-4 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.programs.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print" data-export-target="#programsTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#programsTableEl" data-title="Programs" data-filename="Programs.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Programs" data-json-url="{{ route('admin.programs.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#programsTableEl" data-title="Programs" data-filename="Programs.xlsx" data-json-url="{{ route('admin.programs.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="programsTableEl">
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
                <button type="submit" class="btn btn-sm btn-outline-danger js-delete-program" data-name="{{ $program->name }}">Delete</button>
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

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script>
  (function () {
    document.querySelectorAll('.js-delete-program').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this program';
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