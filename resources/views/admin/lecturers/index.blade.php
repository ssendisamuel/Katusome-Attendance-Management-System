@extends('layouts/layoutMaster')

@section('title', 'Lecturers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Lecturers</h4>
  <a href="{{ route('admin.lecturers.create') }}" class="btn btn-primary">Add Lecturer</a>
</div>
@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if(session('info'))
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    {{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
<div class="card">
  <div class="card-body">
    <form id="lecturerFilters" method="GET" action="{{ route('admin.lecturers.index') }}" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Search by name, email or phone</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. John, 077..." />
      </div>
      <div class="col-md-6 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print" data-export-target="#lecturersTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#lecturersTableEl" data-title="Lecturers" data-filename="Lecturers.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Lecturers" data-json-url="{{ route('admin.lecturers.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#lecturersTableEl" data-title="Lecturers" data-filename="Lecturers.xlsx" data-json-url="{{ route('admin.lecturers.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table" id="lecturersTableEl">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lecturers as $lecturer)
          <tr>
            <td>{{ $lecturer->name }}</td>
            <td>{{ $lecturer->email }}</td>
            <td>{{ $lecturer->phone }}</td>
            <td class="text-end">
              <a href="{{ route('admin.lecturers.edit', $lecturer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.lecturers.destroy', $lecturer) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger js-delete-lecturer" data-name="{{ $lecturer->name }}">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $lecturers->links() }}</div>
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script>
  (function () {
    if (window.Toast) {
      @if(session('success'))
        window.Toast.fire({ icon: 'success', title: @json(session('success')) });
      @endif
      @if(session('info'))
        window.Toast.fire({ icon: 'info', title: @json(session('info')) });
      @endif
      @if(session('error'))
        window.Toast.fire({ icon: 'error', title: @json(session('error')) });
      @endif
    }

    document.querySelectorAll('.js-delete-lecturer').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this lecturer';
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