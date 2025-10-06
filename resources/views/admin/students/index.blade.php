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
              <button type="submit" class="btn btn-sm btn-outline-danger js-delete-student" data-name="{{ $student->name }}">Delete</button>
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

@section('page-script')
<script>
  (function () {
    const bindSweetDelete = () => {
      const buttons = document.querySelectorAll('.js-delete-student');
      buttons.forEach(btn => {
        // Avoid double-binding
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function (ev) {
          ev.preventDefault();
          const form = btn.closest('form');
          if (!form) return;
          const name = btn.dataset.name || 'this student';

          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              title: 'Delete student?',
              text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete',
              cancelButtonText: 'Cancel',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn btn-danger ms-2',
                cancelButton: 'btn btn-outline-secondary'
              }
            }).then(result => {
              if (result.isConfirmed) form.submit();
            });
          } else {
            // Fallback if SweetAlert2 not available
            if (confirm('Delete this student?')) form.submit();
          }
        });
      });
    };

    // Run after DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bindSweetDelete);
    } else {
      bindSweetDelete();
    }
  })();
</script>
@endsection