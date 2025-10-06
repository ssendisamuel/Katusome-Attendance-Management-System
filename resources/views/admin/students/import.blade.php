@extends('layouts/layoutMaster')

@section('title', 'Bulk Upload Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Bulk Upload Students</h4>
  <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Back</a>
  </div>

<div class="card p-4">
  <p class="mb-3">Upload a CSV file with the following headers: <code>name,email,phone,gender,student_no,reg_no</code>.</p>
  <p class="mb-3">You must select the Program, Group and (optional) Year applied to all uploaded records.</p>
  <a href="{{ route('admin.students.import.template') }}" class="btn btn-sm btn-outline-info mb-4">Download CSV Template</a>

  <form method="POST" action="{{ route('admin.students.import.process') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-4">
      <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" id="importProgram" class="form-select" required>
          <option value="">Select Program</option>
          @foreach($programs as $program)
            <option value="{{ $program->id }}">{{ $program->name }}</option>
          @endforeach
        </select>
        @error('program_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label">Group</label>
        <select name="group_id" id="importGroup" class="form-select" required>
          <option value="">Select Group</option>
          @foreach($groups as $group)
            <option value="{{ $group->id }}">{{ $group->name }}</option>
          @endforeach
        </select>
        @error('group_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label">Year of Study</label>
        <input type="number" min="1" max="10" name="year_of_study" class="form-control" value="{{ old('year_of_study', 1) }}">
        @error('year_of_study')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-12">
        <label class="form-label">CSV File</label>
        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
        @error('file')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="mt-4">
      <button class="btn btn-primary">Upload</button>
      <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
  <hr class="my-4">
  <h6>Notes</h6>
  <ul>
    <li><strong>Required:</strong> `name`, `email`, `student_no`.</li>
    <li><strong>Gender:</strong> one of `male`, `female`, `other` or blank.</li>
    <li>Existing students matched by `student_no` are updated, new ones are created.</li>
    <li>Email conflicts with different `student_no` are skipped.</li>
  </ul>
</div>
@push('scripts')
<script>
  (function() {
    const programSelect = document.getElementById('importProgram');
    const groupSelect = document.getElementById('importGroup');
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