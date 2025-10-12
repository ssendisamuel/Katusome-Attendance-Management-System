@extends('layouts/layoutMaster')

@section('title', 'Add Attendance')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Add Attendance</h4>
  <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">Back to List</a>
 </div>
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.attendance.store') }}" class="row g-4">
      @csrf
      <div class="col-12 col-md-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" value="{{ $date }}" class="form-control" id="attendanceDateInput" />
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Course</label>
        <select id="filterCourse" class="form-select">
          <option value="">All</option>
          @foreach($courses as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Group</label>
        <select id="filterGroup" class="form-select">
          <option value="">All</option>
          @foreach($groups as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Schedule</label>
        <select name="schedule_id" id="scheduleSelect" class="form-select" required>
          <option value="">Select schedule</option>
          @foreach($schedules as $s)
            <option value="{{ $s->id }}" data-course="{{ $s->course_id }}" data-group="{{ $s->group_id }}" data-start="{{ $s->start_at?->format('Y-m-d H:i') }}" data-end="{{ $s->end_at?->format('Y-m-d H:i') }}">
              {{ optional($s->course)->name }} — {{ optional($s->group)->name }} — {{ $s->start_at->format('Y-m-d H:i') }} @ {{ $s->location }}
            </option>
          @endforeach
        </select>
        <small class="text-muted">Use filters above to narrow schedules by date, course, and group.</small>
      </div>
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label mb-0">Students</label>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-students">Clear</button>
        </div>
        <select name="student_ids[]" id="studentSelect" class="form-select select2" multiple required>
          <!-- Options loaded via AJAX -->
        </select>
        <small class="text-muted">Type to search, select multiple students. Filters by selected schedule's group.</small>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
          <option value="present">Present</option>
          <option value="late">Late</option>
          <option value="absent">Absent</option>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Marked At</label>
        <input type="datetime-local" name="marked_at" id="markedAtInput" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required />
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label">Lat</label>
        <input type="number" name="lat" step="0.0000001" class="form-control" />
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label">Lng</label>
        <input type="number" name="lng" step="0.0000001" class="form-control" />
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save Attendance</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('page-script')
<script>
  (function(){
    const filterCourse = document.getElementById('filterCourse');
    const filterGroup = document.getElementById('filterGroup');
    const scheduleSelect = document.getElementById('scheduleSelect');
    const studentSelect = document.getElementById('studentSelect');
    const dateInput = document.getElementById('attendanceDateInput');
    const markedAtInput = document.getElementById('markedAtInput');

    function applyFilters(){
      const c = filterCourse.value;
      const g = filterGroup.value;
      const date = dateInput.value;
      [...scheduleSelect.options].forEach(opt => {
        if (!opt.value) return; // skip placeholder
        const matchesCourse = !c || (opt.getAttribute('data-course') === c);
        const matchesGroup = !g || (opt.getAttribute('data-group') === g);
        // Also filter by date using data-start prefix
        const start = opt.getAttribute('data-start') || '';
        const matchesDate = !date || (start.startsWith(date));
        opt.hidden = !(matchesCourse && matchesGroup && matchesDate);
      });
    }

    filterCourse.addEventListener('change', applyFilters);
    filterGroup.addEventListener('change', applyFilters);
    dateInput.addEventListener('change', function(){
      applyFilters();
      // default marked_at to selected date if empty
      if (dateInput.value && !markedAtInput.value){
        markedAtInput.value = dateInput.value + 'T08:00';
      }
      // Reload page to fetch schedules for selected date
      if (dateInput.value) {
        try {
          var base = '{{ route('admin.attendance.create') }}';
          var url = new URL(base, window.location.origin);
          url.searchParams.set('date', dateInput.value);
          // Preserve selected course/group filters if present
          if (filterCourse.value) url.searchParams.set('course_id', filterCourse.value);
          if (filterGroup.value) url.searchParams.set('group_id', filterGroup.value);
          window.location.href = url.toString();
        } catch (e) { console.warn('Date reload failed', e); }
      }
    });

    // If a schedule is selected, default marked_at within window
    scheduleSelect.addEventListener('change', function(){
      const opt = scheduleSelect.selectedOptions[0];
      if (!opt) return;
      const start = opt.getAttribute('data-start');
      if (start){
        const iso = start.replace(' ', 'T');
        markedAtInput.value = iso;
      }
    });

    // Optional: filter students by group when schedule selected
    // Initialize Select2 for students with AJAX search
    (function initSelect2(retryMs){
      var $jq = window.jQuery || window.$;
      if (!$jq) { setTimeout(function(){ initSelect2(typeof retryMs==='number'?retryMs:50); }, typeof retryMs==='number'?retryMs:50); return; }
      $jq(function(){
        var $select = $jq('#studentSelect');
        if (!$select.length) return;

        // Optional focus helper
        try { if (typeof window.select2Focus === 'function') { window.select2Focus($select); } } catch (e) {}

        $select.select2({
          placeholder: 'Select students',
          dropdownParent: $select.parent(),
          closeOnSelect: false,
          width: '100%',
          minimumInputLength: 1,
          templateResult: function (data) {
            if (data.loading) return data.text;
            var name = data.name || data.text || '';
            var group = data.group ? ' (' + data.group + ')' : '';
            return '<div>' + name + group + '</div>';
          },
          templateSelection: function (data) { return data.name || data.text; },
          escapeMarkup: function (m) { return m; },
          ajax: {
            url: '{{ route('admin.reports.students.search') }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
              var opt = scheduleSelect.selectedOptions[0];
              var gid = opt ? (opt.getAttribute('data-group') || '') : '';
              return { q: params.term, page: params.page || 1, group_id: gid };
            },
            processResults: function (data, params) {
              params.page = params.page || 1;
              var arr = Array.isArray(data) ? data : (data.results || []);
              return { results: arr.map(function(d){ return { id: d.id, text: d.label, name: d.name, group: d.group }; }) };
            },
            cache: true
          }
        });

        // Clear selections
        $jq('#clear-students').on('click', function(){ $select.val([]).trigger('change'); });

        // Refresh results when schedule changes to filter by group
        $jq(scheduleSelect).on('change', function(){
          // Close and reopen to refresh
          if ($select.data('select2')) {
            $select.select2('close');
            setTimeout(function(){ $select.select2('open'); }, 50);
          }
        });
      });
    })(50);

    // Initial filters
    applyFilters();
  })();
</script>
@endsection