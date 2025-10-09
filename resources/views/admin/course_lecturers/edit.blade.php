@extends('layouts/layoutMaster')

@section('title', 'Assign Lecturers: ' . $course->code)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Assign Lecturers to {{ $course->name }} ({{ $course->code }})</h4>
  <a href="{{ route('admin.course-lecturers.index') }}" class="btn btn-outline-secondary">Back</a>
  
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('admin.course-lecturers.update', $course) }}">
      @csrf
      @method('PUT')
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <label class="form-label mb-0">Lecturers</label>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-lecturers">Select all</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-lecturers">Clear</button>
          </div>
        </div>
        <div class="position-relative">
          <select name="lecturer_ids[]" id="lecturer_ids" class="form-select select2" multiple>
            @foreach($lecturers as $lecturer)
              @php($name = optional($lecturer->user)->name ?? ('Lecturer #' . $lecturer->id))
              <option value="{{ $lecturer->id }}" @selected($course->lecturers->contains($lecturer->id))>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-text">Type to search, select multiple lecturers.</div>
        @error('lecturer_ids')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
        @error('lecturer_ids.*')
          <div class="text-danger small">{{ $message }}</div>
        @enderror
      </div>
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save Assignments</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('page-script')
<script>
  'use strict';
  (function waitForJQueryInit(retryMs){
    var $jq = window.jQuery || window.$;
    if (!$jq) { setTimeout(function(){ waitForJQueryInit(typeof retryMs==='number'?retryMs:50); }, typeof retryMs==='number'?retryMs:50); return; }
    $jq(function () {
      var $select = $jq('#lecturer_ids');
      if (!$select.length) return;

    // Keep an in-memory set of selected IDs for checkbox rendering
      var selectedIds = new Set(($select.val() || []).map(function (v) { return String(v); }));

    // Optional focus helper if present
      try { if (typeof window.select2Focus === 'function') { window.select2Focus($select); } } catch (e) {}

      function renderOptionWithCheckbox(data) {
        if (data.loading) return data.text;
        var id = String(data.id);
        var checked = selectedIds.has(id) ? 'checked' : '';
        var name = data.name || data.text || '';
        var email = data.email ? ' (' + data.email + ')' : '';
        return (
          '<div class="d-flex align-items-center">'
          + '<input type="checkbox" class="form-check-input me-2" ' + checked + ' />'
          + '<span>' + name + email + '</span>'
          + '</div>'
        );
      }

      $select.select2({
        placeholder: 'Select lecturers',
        dropdownParent: $select.parent(),
        closeOnSelect: false,
        width: '100%',
        minimumInputLength: 1,
        templateResult: renderOptionWithCheckbox,
        templateSelection: function (data) {
          return data.name || data.text;
        },
        escapeMarkup: function (m) { return m; },
        ajax: {
          url: '{{ route('admin.lecturers.search') }}',
          dataType: 'json',
          delay: 250,
          data: function (params) { return { q: params.term, page: params.page || 1 }; },
          processResults: function (data, params) {
            params.page = params.page || 1;
            return { results: data.results, pagination: { more: data.pagination && data.pagination.more } };
          },
          cache: true
        }
      });

    // Keep selectedIds in sync
      $select.on('select2:select', function (e) { selectedIds.add(String(e.params.data.id)); });
      $select.on('select2:unselect', function (e) { selectedIds.delete(String(e.params.data.id)); });

      // Enable checkbox clicks to toggle selection inside dropdown
      $select.on('select2:open', function () {
        var $results = $jq('.select2-results');
        $results.off('click', '.form-check-input');
        $results.on('click', '.form-check-input', function (ev) {
          ev.stopPropagation();
          var $opt = $jq(this).closest('.select2-results__option');
          var data = $opt.data('data');
          var id = data && data.id != null ? String(data.id) : null;
          if (!id) return;
          var current = ($select.val() || []).map(String);
          if (this.checked) { if (current.indexOf(id) === -1) current.push(id); }
          else { current = current.filter(function (v) { return v !== id; }); }
          $select.val(current).trigger('change');
          selectedIds = new Set(current);
        });
      });

      // Select All: fetch all pages and select every lecturer
      $jq('#select-all-lecturers').on('click', async function () {
        try {
          var page = 1, allValues = [];
          while (true) {
            var res = await fetch('{{ route('admin.lecturers.search') }}?page=' + page);
            var data = await res.json();
            var ids = (data.results || []).map(function (r) { return String(r.id); });
            allValues = allValues.concat(ids);
            if (!(data.pagination && data.pagination.more)) break;
            page++;
          }
          // Ensure options exist so Select2 accepts the selection
          allValues.forEach(function (id) {
            if (!$select.find('option[value="' + id + '"]').length) {
              $select.append('<option value="' + id + '" selected>' + id + '</option>');
            }
          });
          $select.val(allValues).trigger('change');
          selectedIds = new Set(allValues);
        } catch (err) { console.error('Select all failed:', err); }
      });

      // Clear selections
      $jq('#clear-lecturers').on('click', function () {
        $select.val([]).trigger('change');
        selectedIds.clear();
      });
    });
  })(50);
</script>
@endsection