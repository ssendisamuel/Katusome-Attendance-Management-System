@extends('layouts/layoutMaster')

@section('title', 'Add Student')

@section('content')
  <h4 class="mb-4">Add Student</h4>
  <div class="card p-4">
    <form method="POST" action="{{ route('admin.students.store') }}" id="studentCreateForm">
      @csrf
      <div class="mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        @error('name')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        @error('email')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
        @error('phone')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Student No. <span class="text-danger">*</span></label>
        <input type="text" name="student_no" class="form-control" value="{{ old('student_no') }}" required>
        @error('student_no')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Reg No. <span class="text-danger">*</span></label>
        <input type="text" name="reg_no" class="form-control @error('reg_no') is-invalid @enderror"
          value="{{ old('reg_no') }}" required>
        @error('reg_no')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">Select Gender</option>
          <option value="male" @selected(old('gender') == 'male')>Male</option>
          <option value="female" @selected(old('gender') == 'female')>Female</option>
          <option value="other" @selected(old('gender') == 'other')>Other</option>
        </select>
        @error('gender')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
          <option value="">Select Program</option>
          @foreach ($programs as $program)
            <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->name }}</option>
          @endforeach
        </select>
        @error('program_id')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select">
          <option value="">Select Group</option>
          @foreach ($groups as $group)
            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
          @endforeach
        </select>
        <small class="text-muted">Groups A-G are independent of programs</small>
        @error('group_id')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Year of Study</label>
        <select name="year_of_study" class="form-select">
          <option value="">Select Year</option>
          <option value="1" @selected(old('year_of_study') == 1)>Year 1</option>
          <option value="2" @selected(old('year_of_study') == 2)>Year 2</option>
          <option value="3" @selected(old('year_of_study') == 3)>Year 3</option>
          <option value="4" @selected(old('year_of_study') == 4)>Year 4</option>
        </select>
        @error('year_of_study')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Initial Password (optional)</label>
        <input type="text" name="initial_password" class="form-control" value="{{ old('initial_password') }}"
          placeholder="Defaults to 'password' if blank">
        @error('initial_password')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <button type="submit" class="btn btn-primary" id="btnSaveStudent"
        data-loading-text="Adding Student...">Save</button>
      <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
  </div>
@section('page-script')
  <script>
    (function() {
      // Groups are now independent (A-G), no cascading filtering needed

      const form = document.getElementById('studentCreateForm');
      const submitBtn = document.getElementById('btnSaveStudent');
      if (form && submitBtn) {
        form.addEventListener('submit', function() {
          submitBtn.disabled = true;
          const loadingText = submitBtn.getAttribute('data-loading-text') || 'Processing...';
          submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            loadingText;
        });
      }
    })();
  </script>
@endsection
@endsection
