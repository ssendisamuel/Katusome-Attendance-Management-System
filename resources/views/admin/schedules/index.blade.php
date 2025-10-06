@extends('layouts/layoutMaster')

@section('title', 'Schedules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Schedules</h4>
  <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">Add Schedule</a>
  </div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="card-body">
    <div id="schedulesFiltersWrap">
      @include('admin.schedules.partials.filters')
    </div>
  </div>
  <div id="schedulesTable">
    @include('admin.schedules.partials.table')
  </div>
</div>
<script>
  (function() {
    const filtersWrap = document.getElementById('schedulesFiltersWrap');
    const tableWrap = document.getElementById('schedulesTable');
    let form = filtersWrap.querySelector('#scheduleFilters');
    let debounceTimer;

    function urlFromForm(){
      const params = new URLSearchParams(new FormData(form));
      return form.action + (params.toString() ? ('?' + params.toString()) : '');
    }

    function attachPagination(){
      tableWrap.querySelectorAll('.pagination a').forEach(a => {
        a.addEventListener('click', function(e){
          e.preventDefault();
          updateTable(this.href);
        });
      });
    }

    function updateTable(url){
      const target = url || urlFromForm();
      fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text())
        .then(html => {
          tableWrap.innerHTML = html;
          attachPagination();
          history.replaceState(null, '', target);
        })
        .catch(console.error);
    }

    function refreshFilters(){
      const params = new URLSearchParams(new FormData(form));
      params.set('fragment', 'filters');
      const target = form.action + '?' + params.toString();
      fetch(target, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text())
        .then(html => {
          filtersWrap.innerHTML = html;
          form = filtersWrap.querySelector('#scheduleFilters');
          bindFilterEvents();
        })
        .catch(console.error);
    }

    attachPagination();

    function bindFilterEvents(){
      const searchInput = document.getElementById('scheduleSearch');
      form.addEventListener('submit', function(e){
        e.preventDefault();
        updateTable();
      });

      const courseEl = form.querySelector('[name="course_id"]');
      if (courseEl) courseEl.addEventListener('change', () => { refreshFilters(); updateTable(); });

      ['group_id','lecturer_id','date'].forEach(name => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.addEventListener('change', () => updateTable());
      });

      if (searchInput) {
        searchInput.addEventListener('input', function(){
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(function(){
            updateTable();
          }, 400);
        });
      }

      const resetLink = form.querySelector('a.btn.btn-outline-secondary');
      if (resetLink) {
        resetLink.addEventListener('click', function(e){
          e.preventDefault();
          form.reset();
          refreshFilters();
          updateTable(this.href);
        });
      }
    }

    bindFilterEvents();
  })();
</script>
@endsection