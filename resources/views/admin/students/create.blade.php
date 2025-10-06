@extends('layouts/layoutMaster')

@section('title', 'Add Student')

@section('content')
<h4 class="mb-4">Add Student</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.students.store') }}">
    @csrf
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
      @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
      @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Student No.</label>
      <input type="text" name="student_no" class="form-control" value="{{ old('student_no') }}" required>
      @error('student_no')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Reg No.</label>
      <input type="text" name="reg_no" class="form-control" value="{{ old('reg_no') }}">
      @error('reg_no')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <option value="">Select Gender</option>
        <option value="male" @selected(old('gender')=='male')>Male</option>
        <option value="female" @selected(old('gender')=='female')>Female</option>
        <option value="other" @selected(old('gender')=='other')>Other</option>
      </select>
      @error('gender')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Program</label>
      <select name="program_id" id="studentProgram" class="form-select" required>
        <option value="">Select Program</option>
        @foreach($programs as $program)
          <option value="{{ $program->id }}" @selected(old('program_id')==$program->id)>{{ $program->name }}</option>
        @endforeach
      </select>
      @error('program_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Group</label>
      <select name="group_id" id="studentGroup" class="form-select" required>
        <option value="">Select Group</option>
        @foreach($groups as $group)
          <option value="{{ $group->id }}" @selected(old('group_id')==$group->id)>{{ $group->name }}</option>
        @endforeach
      </select>
      @error('group_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
      <label class="form-label">Year of Study</label>
      <input type="number" min="1" max="10" name="year_of_study" class="form-control" value="{{ old('year_of_study') }}">
      @error('year_of_study')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-primary">Save</button>
    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@push('scripts')
<script>
  (function() {
    const programSelect = document.getElementById('studentProgram');
    const groupSelect = document.getElementById('studentGroup');
    async function fetchGroups(programId) {
      const url = `${window.location.origin}/admin/programs/${programId}/groups`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return [];
      return res.json();
    }
    async function refreshGroups() {
      const pid = programSelect.value;
      groupSelect.innerHTML = '<option value="">Select Group</option>';
      if (!pid) return;
      const groups = await fetchGroups(pid);
      groups.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.textContent = g.name;
        groupSelect.appendChild(opt);
      });
    }
    programSelect?.addEventListener('change', refreshGroups);
  })();
</script>
@endpush
@endsection