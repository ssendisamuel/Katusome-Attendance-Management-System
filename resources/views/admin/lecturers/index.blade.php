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
  <div class="table-responsive">
    <table class="table">
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
<script>
  (function () {
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