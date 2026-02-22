@extends('layouts/layoutMaster')

@section('title', 'Course Lecturer Assignments')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Assign Lecturers to Courses</h4>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">Manage Courses</a>
  </div>
  @if (session('success'))
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
          <input type="text" name="search" value="{{ request('search') }}" class="form-control"
            placeholder="e.g. Web, ICS101" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Program</label>
          <select name="program_id" class="form-select select2" data-placeholder="Select Program">
            <option value="">All Programs</option>
            @foreach ($programs as $program)
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
      <div id="table-container">
        @include('admin.course_lecturers.partials.table', ['courses' => $courses])
      </div>
    </div>
  </div>
@endsection

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/forms-selects.js'])
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.querySelector('input[name="search"]');
      const programSelect = document.querySelector('select[name="program_id"]');
      const tableContainer = document.getElementById('table-container');

      function fetchResults() {
        const search = searchInput.value;
        // For Select2, valid value is in the original select
        const programId = $(programSelect).val();
        const url = new URL("{{ route('admin.course-lecturers.index') }}");

        if (search) url.searchParams.set('search', search);
        if (programId) url.searchParams.set('program_id', programId);
        url.searchParams.set('fragment', 'table');

        fetch(url, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(response => response.text())
          .then(html => {
            tableContainer.innerHTML = html;
          })
          .catch(error => console.error('Error fetching results:', error));
      }

      // Debounce function
      function debounce(func, wait) {
        let timeout;
        return function(...args) {
          clearTimeout(timeout);
          timeout = setTimeout(() => func.apply(this, args), wait);
        };
      }

      const debouncedFetch = debounce(fetchResults, 400);

      if (searchInput) {
        searchInput.addEventListener('input', debouncedFetch);
      }

      // Listen to jQuery change event for Select2 support
      if (programSelect) {
        $(programSelect).on('change', fetchResults);
      }
    });
  </script>
@endsection
