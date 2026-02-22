@php
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Create Academic Semester')

@section('content')
  <div class="row">
    <div class="col-md-6 mx-auto">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0">Create New Semester</h4>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.academic-semesters.store') }}">
            @csrf

            <div class="mb-3">
              <label class="form-label" for="year">Academic Year <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('year') is-invalid @enderror" id="year" name="year"
                value="{{ old('year') }}" placeholder="2025/2026" pattern="\d{4}/\d{4}" required>
              <small class="text-muted">Format: YYYY/YYYY (e.g., 2025/2026)</small>
              @error('year')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label" for="semester">Semester <span class="text-danger">*</span></label>
              <select class="form-select @error('semester') is-invalid @enderror" id="semester" name="semester" required>
                <option value="">Select Semester</option>
                <option value="Semester 1" {{ old('semester') == 'Semester 1' ? 'selected' : '' }}>Semester 1</option>
                <option value="Semester 2" {{ old('semester') == 'Semester 2' ? 'selected' : '' }}>Semester 2</option>
              </select>
              @error('semester')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date"
                name="start_date" value="{{ old('start_date') }}" required>
              @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                name="end_date" value="{{ old('end_date') }}" required>
              @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="ri-save-line me-1"></i> Create Semester
              </button>
              <a href="{{ route('admin.academic-semesters.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
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

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: '<ul style="text-align: left;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
          });
        }
      });
    </script>
  @endif
@endsection
