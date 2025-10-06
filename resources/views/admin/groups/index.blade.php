@extends('layouts/layoutMaster')

@section('title', 'Groups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Groups</h4>
  <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">Add Group</a>
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
          <th>Students</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($groups as $group)
          <tr>
            <td>{{ $group->name }}</td>
            <td>{{ $group->program->name }}</td>
            <td>{{ $group->students_count }}</td>
            <td class="text-end">
              <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger js-delete-group" data-name="{{ $group->name }}">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $groups->links() }}</div>
</div>
@endsection

@section('page-script')
<script>
  (function () {
    document.querySelectorAll('.js-delete-group').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const form = this.closest('form');
        const name = this.dataset.name || 'this group';
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