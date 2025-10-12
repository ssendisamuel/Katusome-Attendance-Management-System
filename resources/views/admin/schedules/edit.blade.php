@extends('layouts/layoutMaster')

@section('title', 'Edit Schedule')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Edit Schedule</h4>
  <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card p-4">
  <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
      <div class="col-md-6">
        <label class="form-label">Course</label>
        <select id="schedule-edit-course" name="course_id" class="form-select" required>
          @foreach($courses as $course)
            <option value="{{ $course->id }}" data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}" {{ (old('course_id', $schedule->course_id) == $course->id) ? 'selected' : '' }}>{{ $course->name }}</option>
          @endforeach
        </select>
        @error('course_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select" required>
          @foreach($groups as $group)
            <option value="{{ $group->id }}" {{ (old('group_id', $schedule->group_id) == $group->id) ? 'selected' : '' }}>{{ $group->name }}</option>
          @endforeach
        </select>
        @error('group_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Lecturers (optional)</label>
@php($hasPivot = \Illuminate\Support\Facades\Schema::hasTable('lecturer_schedule'))
        @php($selectedLecturers = collect(old('lecturer_ids', ($hasPivot && $schedule->relationLoaded('lecturers')) ? ($schedule->lecturers->pluck('id') ?? []) : (optional($schedule->lecturer_id) ? [ $schedule->lecturer_id ] : []))))
        <label class="form-label">Lecturers (optional)</label>
        <select id="schedule-edit-lecturers" name="lecturer_ids[]" class="form-select" multiple>
          @foreach($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" {{ $selectedLecturers->contains($lecturer->id) ? 'selected' : '' }}>{{ $lecturer->name }}</option>
          @endforeach
        </select>
        <div class="form-text">Hold Ctrl/Command to select multiple</div>
        @error('lecturer_ids')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('lecturer_ids.*')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Series (optional)</label>
        <select name="series_id" class="form-select">
          <option value="">None</option>
          @foreach($series as $ser)
            <option value="{{ $ser->id }}" {{ (old('series_id', $schedule->series_id) == $ser->id) ? 'selected' : '' }}>{{ $ser->name }}</option>
          @endforeach
        </select>
        @error('series_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Location (optional)</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $schedule->location) }}" placeholder="e.g., Room A1">
        @error('location')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Start At</label>
        <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at', optional($schedule->start_at)->format('Y-m-d\TH:i')) }}" required>
        @error('start_at')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">End At</label>
        <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at', optional($schedule->end_at)->format('Y-m-d\TH:i')) }}" required>
        @error('end_at')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>

    <div class="mt-4">
      <button class="btn btn-primary">Update</button>
      <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<script>
  (function() {
    const courseSelect = document.getElementById('schedule-edit-course');
    const lecturersSelect = document.getElementById('schedule-edit-lecturers');
    if (!courseSelect || !lecturersSelect) return;

    function setLecturersFromCourseOption(opt) {
      if (!opt) return;
      const csv = (opt.dataset.lecturerIds || '').trim();
      const ids = csv ? csv.split(',').filter(Boolean) : [];
      if (ids.length === 0) return;
      Array.from(lecturersSelect.options).forEach(o => {
        o.selected = ids.includes(String(o.value));
      });
    }

    // On initial load, only preselect if no lecturers selected yet (e.g., switching course)
    const initiallySelectedCount = lecturersSelect.selectedOptions.length;
    if (initiallySelectedCount === 0 && courseSelect.value) {
      setLecturersFromCourseOption(courseSelect.selectedOptions[0]);
    }

    courseSelect.addEventListener('change', function() {
      const opt = courseSelect.selectedOptions[0];
      setLecturersFromCourseOption(opt);
    });
  })();
</script>
@endsection