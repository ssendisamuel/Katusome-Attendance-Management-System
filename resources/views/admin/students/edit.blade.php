@extends('layouts/layoutMaster')

@section('title', 'Edit Student')

@section('content')
  <h4 class="mb-4">Edit Student</h4>
  <div class="card p-4">
    <form method="POST" action="{{ route('admin.students.update', $student) }}">
      @csrf
      @method('PUT')
      <div class="mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
        @error('name')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}">
        @error('email')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone) }}">
        @error('phone')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Student No. <span class="text-danger">*</span></label>
        <input type="text" name="student_no" class="form-control" value="{{ old('student_no', $student->student_no) }}"
          required>
        @error('student_no')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Reg No. <span class="text-danger">*</span></label>
        <input type="text" name="reg_no" class="form-control @error('reg_no') is-invalid @enderror"
          value="{{ old('reg_no', $student->reg_no) }}" required>
        @error('reg_no')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">Select Gender</option>
          <option value="male" @selected(old('gender', $student->gender) == 'male')>Male</option>
          <option value="female" @selected(old('gender', $student->gender) == 'female')>Female</option>
          <option value="other" @selected(old('gender', $student->gender) == 'other')>Other</option>
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
            <option value="{{ $program->id }}" @selected(old('program_id', $student->program_id) == $program->id)>{{ $program->name }}</option>
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
            <option value="{{ $group->id }}" @selected(old('group_id', $student->group_id) == $group->id)>{{ $group->name }}</option>
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
          <option value="1" @selected(old('year_of_study', $student->year_of_study) == 1)>Year 1</option>
          <option value="2" @selected(old('year_of_study', $student->year_of_study) == 2)>Year 2</option>
          <option value="3" @selected(old('year_of_study', $student->year_of_study) == 3)>Year 3</option>
          <option value="4" @selected(old('year_of_study', $student->year_of_study) == 4)>Year 4</option>
        </select>
        @error('year_of_study')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <hr class="my-4" />
      <h6 class="mb-3">Account Password</h6>
      <p class="text-muted small mb-3">Leave blank to keep the current password.</p>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password">
        @error('password')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="mustChangePassword" name="must_change_password" value="1"
          @checked(old('must_change_password', false))>
        <label class="form-check-label" for="mustChangePassword">Require password change on next login</label>
      </div>
      <button class="btn btn-primary">Update</button>
      <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
  </div>
  @push('scripts')
    <script>
      // Groups are now independent of programs (A-G), no filtering needed
    </script>
  @endpush
@endsection
