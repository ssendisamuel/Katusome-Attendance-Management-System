@extends('layouts/layoutMaster')

@section('title', 'Add Schedule')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Add Schedule</h4>
  <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card p-4">
  <form action="{{ route('admin.schedules.store') }}" method="POST">
    @csrf

    <div class="row g-4">
      <div class="col-md-6">
        <label class="form-label">Course</label>
        <select id="schedule-course" name="course_id" class="form-select" required>
          <option value="">Select Course</option>
          @foreach($courses as $course)
            <option value="{{ $course->id }}" data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
          @endforeach
        </select>
        @error('course_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select" required>
          <option value="">Select Group</option>
          @foreach($groups as $group)
            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
          @endforeach
        </select>
        @error('group_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Lecturers (optional)</label>
        <select id="schedule-lecturers" name="lecturer_ids[]" class="form-select" multiple>
          @foreach($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" {{ collect(old('lecturer_ids', []))->contains($lecturer->id) ? 'selected' : '' }}>{{ $lecturer->name }}</option>
          @endforeach
        </select>
        <div class="form-text">Hold Ctrl/Command to select multiple</div>
        @error('lecturer_ids')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('lecturer_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Series (optional)</label>
        <select name="series_id" class="form-select">
          <option value="">Select Series</option>
          @foreach($series as $ser)
            <option value="{{ $ser->id }}" {{ old('series_id') == $ser->id ? 'selected' : '' }}>{{ $ser->name }}</option>
          @endforeach
        </select>
        @error('series_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Location (optional)</label>
        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g., Room A1">
        @error('location')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Start At</label>
        <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at') }}" required>
        @error('start_at')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">End At</label>
        <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at') }}" required>
        @error('end_at')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-primary">Save</button>
      <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const courseSelect = document.getElementById('schedule-course');
    const lecturersSelect = document.getElementById('schedule-lecturers');
    if (!courseSelect || !lecturersSelect) return;

    function setLecturersFromCourseOption(opt) {
      if (!opt) return;
      const csv = (opt.dataset.lecturerIds || '').trim();
      const ids = csv ? csv.split(',').filter(Boolean) : [];
      // If no course lecturers, do not alter selection
      if (ids.length === 0) return;
      // Clear current selection and select course lecturers
      Array.from(lecturersSelect.options).forEach(o => {
        o.selected = ids.includes(String(o.value));
      });
    }

    // On initial load, only preselect if no lecturers selected yet
    const initiallySelectedCount = lecturersSelect.selectedOptions.length;
    if (initiallySelectedCount === 0 && courseSelect.value) {
      setLecturersFromCourseOption(courseSelect.selectedOptions[0]);
    }

    // On course change, always preselect assigned lecturers
    courseSelect.addEventListener('change', function() {
      const opt = courseSelect.selectedOptions[0];
      setLecturersFromCourseOption(opt);
    });
  })();
</script>
@endsection