@extends('layouts/layoutMaster')

@section('title', 'Attendance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Attendance Records</h4>
  <a href="{{ route('admin.attendance.create') }}" class="btn btn-primary"><span class="icon-base ri ri-add-line me-1"></span>Add Attendance</a>
</div>
@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card">
  <div class="card-body">
    <div id="attendanceFiltersWrap">
      @include('admin.attendance.partials.filters')
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#attendancesTableEl" data-title="Attendance Records" data-filename="Attendance_Records_{{ now()->toDateString() }}.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Attendance" data-json-url="{{ route('admin.attendance.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#attendancesTableEl" data-title="Attendance Records" data-filename="Attendance_Records_{{ now()->toDateString() }}.xlsx" data-json-url="{{ route('admin.attendance.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  <div id="attendancesTable">
    @include('admin.attendance.partials.table')
  </div>
</div>
<!-- Photo Preview Modal -->
<div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="photoPreviewImg" src="" alt="Attendance photo" class="img-fluid" />
      </div>
      <div class="modal-footer justify-content-center">
        <a id="photoDownloadBtn" href="#" class="btn btn-sm btn-primary" download>Download</a>
      </div>
    </div>
  </div>
  </div>
<script>
  (function() {
    const filtersWrap = document.getElementById('attendanceFiltersWrap');
    const tableWrap = document.getElementById('attendancesTable');
    const modalEl = document.getElementById('photoPreviewModal');
    const modalImg = document.getElementById('photoPreviewImg');
    const modalDownloadBtn = document.getElementById('photoDownloadBtn');
    let bootstrapModal;
    let form = filtersWrap.querySelector('#attendanceFilters');
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

      // Bind photo thumbnail clicks
      tableWrap.querySelectorAll('[data-photo-url]').forEach(el => {
        el.addEventListener('click', function(e){
          e.preventDefault();
          const url = this.getAttribute('data-photo-url');
          if (!url) return;
          modalImg.src = url;
          if (modalDownloadBtn) {
            modalDownloadBtn.href = url;
            // Try to set a sensible filename for the download
            const filename = (url.split('?')[0].split('/').pop()) || 'photo.jpg';
            modalDownloadBtn.setAttribute('download', filename);
          }
          if (!bootstrapModal) {
            bootstrapModal = new (window.bootstrap?.Modal || function(){}) (modalEl);
          }
          // Fallback if Bootstrap Modal not available
          if (bootstrapModal && bootstrapModal.show) {
            bootstrapModal.show();
          } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
          }
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
          form = filtersWrap.querySelector('#attendanceFilters');
          bindFilterEvents();
        })
        .catch(console.error);
    }

    attachPagination();

    function bindFilterEvents(){
      const searchInput = document.getElementById('attendanceSearch');
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
@vite(['resources/assets/js/report-export.js'])
@endsection