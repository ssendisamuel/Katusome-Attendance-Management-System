@extends('layouts/layoutMaster')

@section('title', 'Edit Schedule Series')

@section('content')
<h4 class="mb-4">Edit Schedule Series</h4>
<div class="card p-4">
  <form method="POST" action="{{ route('admin.series.update', $series) }}">
    @csrf
    @method('PUT')
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="{{ old('name', $series->name) }}" required>
      @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Course</label>
        <select id="series-edit-course" name="course_id" class="form-select" required>
          @foreach($courses as $course)
            <option value="{{ $course->id }}" data-lecturer-ids="{{ $course->lecturers->pluck('id')->implode(',') }}" @selected(old('course_id', $series->course_id)==$course->id)>{{ $course->name }}</option>
          @endforeach
        </select>
        @error('course_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select" required>
          @foreach($groups as $group)
            <option value="{{ $group->id }}" @selected(old('group_id', $series->group_id)==$group->id)>{{ $group->name }}</option>
          @endforeach
        </select>
        @error('group_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Lecturer (optional)</label>
        <select id="series-edit-lecturer" name="lecturer_id" class="form-select">
          <option value="">None</option>
          @foreach($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $series->lecturer_id)==$lecturer->id)>{{ $lecturer->name }}</option>
          @endforeach
        </select>
        @error('lecturer_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $series->start_date ? $series->start_date->format('Y-m-d') : '') }}" required>
        @error('start_date')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $series->end_date ? $series->end_date->format('Y-m-d') : '') }}" required>
        @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label">Start Time</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $series->start_time ? $series->start_time->format('H:i') : '') }}" required>
        @error('start_time')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">End Time</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $series->end_time ? $series->end_time->format('H:i') : '') }}" required>
        @error('end_time')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Days of Week</label>
      @php
        $selectedDays = collect(old('days_of_week', $series->days_of_week ?? []))
          ->map(function ($d) { return strtolower(trim($d)); });
        $allDays = ['mon','tue','wed','thu','fri','sat','sun'];
        $selectedLabel = $selectedDays->isEmpty()
          ? 'Select days'
          : $selectedDays->map(fn($d) => strtoupper($d))->implode(', ');
      @endphp
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
          {{ $selectedLabel }}
        </button>
        <div class="dropdown-menu w-100 p-3">
          <div class="row">
            @foreach($allDays as $day)
              <div class="col-6 mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="days_of_week[]" value="{{ $day }}" id="day-{{ $day }}" @checked($selectedDays->contains($day))>
                  <label class="form-check-label" for="day-{{ $day }}">{{ strtoupper($day) }}</label>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      @error('days_of_week')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="is_recurring" @checked(old('is_recurring', $series->is_recurring))>
      <label class="form-check-label" for="is_recurring">Recurring</label>
    </div>
    <div class="mb-3">
      <label class="form-label">Location (optional)</label>
      <input type="text" name="location" class="form-control" value="{{ old('location', $series->location) }}">
      @error('location')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-primary">Update</button>
    <a href="{{ route('admin.series.index') }}" class="btn btn-outline-secondary">Cancel</a>
  </form>
</div>
@endsection
<script>
  (function() {
    const courseSelect = document.getElementById('series-edit-course');
    const lecturerSelect = document.getElementById('series-edit-lecturer');
    if (!courseSelect || !lecturerSelect) return;

    function preselectFirstLecturer(opt) {
      if (!opt) return;
      const csv = (opt.dataset.lecturerIds || '').trim();
      const ids = csv ? csv.split(',').filter(Boolean) : [];
      if (ids.length === 0) return;
      const first = ids[0];
      if (!first) return;
      Array.from(lecturerSelect.options).forEach(o => {
        o.selected = String(o.value) === String(first);
      });
    }

    // Only auto-select if currently none is selected (avoid overriding existing series lecturer)
    const hasSelection = Array.from(lecturerSelect.options).some(o => o.selected && o.value);
    if (!hasSelection && courseSelect.value) {
      preselectFirstLecturer(courseSelect.selectedOptions[0]);
    }

    courseSelect.addEventListener('change', function() {
      const userHasSelected = Array.from(lecturerSelect.options).some(o => o.selected && o.value);
      if (!userHasSelected) {
        preselectFirstLecturer(courseSelect.selectedOptions[0]);
      }
    });
  })();
</script>