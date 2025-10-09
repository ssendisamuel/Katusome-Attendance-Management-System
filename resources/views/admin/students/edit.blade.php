@extends('layouts/layoutMaster')

@section('title', 'Edit Student')

@section('content')
  <h4 class="mb-4">Edit Student</h4>
  <div class="card p-4">
    <form method="POST" action="{{ route('admin.students.update', $student) }}">
      @csrf
      @method('PUT')
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
        @error('name')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
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
        <label class="form-label">Student No.</label>
        <input type="text" name="student_no" class="form-control" value="{{ old('student_no', $student->student_no) }}"
          required>
        @error('student_no')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Reg No.</label>
        <input type="text" name="reg_no" class="form-control" value="{{ old('reg_no', $student->reg_no) }}">
        @error('reg_no')
          <div class="text-danger small">{{ $message }}</div>
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
        <select name="program_id" id="studentProgram" class="form-select" required>
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
        <select name="group_id" id="studentGroup" class="form-select" required>
          @foreach ($groups as $group)
            <option value="{{ $group->id }}" @selected(old('group_id', $student->group_id) == $group->id)>{{ $group->name }}</option>
          @endforeach
        </select>
        @error('group_id')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Year of Study</label>
        <input type="number" min="1" max="10" name="year_of_study" class="form-control"
          value="{{ old('year_of_study', $student->year_of_study) }}">
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
        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="mustChangePassword" name="must_change_password" value="1" @checked(old('must_change_password', false))>
        <label class="form-check-label" for="mustChangePassword">Require password change on next login</label>
      </div>
      <button class="btn btn-primary">Update</button>
      <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
  </div>
  @push('scripts')
    <script>
      (function() {
        const programSelect = document.getElementById('studentProgram');
        const groupSelect = document.getElementById('studentGroup');
        const currentGroupId = '{{ old('group_id', $student->group_id) }}';
        async function fetchGroups(programId) {
          const url = `${window.location.origin}/admin/programs/${programId}/groups`;
          const res = await fetch(url, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          if (!res.ok) return [];
          return res.json();
        }
        async function refreshGroups(selectCurrent = false) {
          const pid = programSelect.value;
          groupSelect.innerHTML = '';
          const defaultOpt = document.createElement('option');
          defaultOpt.value = '';
          defaultOpt.textContent = 'Select Group';
          groupSelect.appendChild(defaultOpt);
          if (!pid) return;
          const groups = await fetchGroups(pid);
          groups.forEach(g => {
            const opt = document.createElement('option');
            opt.value = g.id;
            opt.textContent = g.name;
            if (selectCurrent && String(currentGroupId) === String(g.id)) opt.selected = true;
            groupSelect.appendChild(opt);
          });
        }
        programSelect?.addEventListener('change', () => refreshGroups(false));
        // On load, ensure groups list matches selected program and preselect current group if valid
        document.addEventListener('DOMContentLoaded', () => refreshGroups(true));
      })();
    </script>
  @endpush
@endsection
