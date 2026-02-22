@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Academic Semesters')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0">Academic Semesters</h4>
          <a href="{{ route('admin.academic-semesters.create') }}" class="btn btn-primary">
            <i class="ri-add-line me-1"></i> Create New Semester
          </a>
        </div>
        <div class="card-body">
          @if ($semesters->isEmpty())
            <div class="alert alert-info">
              No academic semesters created yet. Create one to get started.
            </div>
          @else
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($semesters as $semester)
                    <tr>
                      <td>{{ $semester->year }}</td>
                      <td>{{ $semester->semester }}</td>
                      <td>{{ $semester->start_date->format('M j, Y') }}</td>
                      <td>{{ $semester->end_date->format('M j, Y') }}</td>
                      <td>
                        @if ($semester->is_active)
                          <span class="badge bg-success">Active</span>
                        @else
                          <span class="badge bg-secondary">Inactive</span>
                        @endif
                      </td>
                      <td>
                        @if ($semester->is_active)
                          <button type="button" class="btn btn-sm btn-warning"
                            onclick="confirmDeactivate({{ $semester->id }}, '{{ $semester->display_name }}')">
                            <i class="ri-close-line"></i> Deactivate
                          </button>
                          <form id="deactivate-form-{{ $semester->id }}" method="POST"
                            action="{{ route('admin.academic-semesters.deactivate', $semester) }}" style="display:none">
                            @csrf
                          </form>
                        @else
                          <button type="button" class="btn btn-sm btn-success"
                            onclick="confirmActivate({{ $semester->id }}, '{{ $semester->display_name }}')">
                            <i class="ri-check-line"></i> Activate
                          </button>
                          <form id="activate-form-{{ $semester->id }}" method="POST"
                            action="{{ route('admin.academic-semesters.activate', $semester) }}" style="display:none">
                            @csrf
                          </form>
                        @endif
                        <a href="{{ route('admin.academic-semesters.edit', $semester) }}" class="btn btn-sm btn-primary">
                          <i class="ri-edit-line"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger"
                          onclick="confirmDelete({{ $semester->id }}, '{{ $semester->display_name }}')">
                          <i class="ri-delete-bin-line"></i> Delete
                        </button>
                        <form id="delete-form-{{ $semester->id }}" method="POST"
                          action="{{ route('admin.academic-semesters.destroy', $semester) }}" style="display:none">
                          @csrf
                          @method('DELETE')
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.Swal) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false
          });
        }
      });
    </script>
  @endif

  @if (session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: @json(session('error')),
          });
        }
      });
    </script>
  @endif

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: '@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach',
          });
        }
      });
    </script>
  @endif

  <script>
    function confirmActivate(semesterId, semesterName) {
      Swal.fire({
        title: 'Activate Semester?',
        html: `Are you sure you want to activate <strong>${semesterName}</strong>?<br><small class="text-muted">This will deactivate all other semesters.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Activate',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('activate-form-' + semesterId).submit();
        }
      });
    }

    function confirmDeactivate(semesterId, semesterName) {
      Swal.fire({
        title: 'Deactivate Semester?',
        html: `Are you sure you want to deactivate <strong>${semesterName}</strong>?<br><small class="text-muted">Students will not be prompted to enroll until you activate a semester.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Deactivate',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('deactivate-form-' + semesterId).submit();
        }
      });
    }

    function confirmDelete(semesterId, semesterName) {
      Swal.fire({
        title: 'Delete Semester?',
        html: `Are you sure you want to delete <strong>${semesterName}</strong>?<br><small class="text-danger">This action cannot be undone!</small>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('delete-form-' + semesterId).submit();
        }
      });
    }
  </script>
@endsection
