@extends('layouts/layoutMaster')

@section('title', 'Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">Students</h4>
  <div>
    <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary me-2">Bulk Upload</a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">Add Student</a>
  </div>
</div>
@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
@if(session('info'))
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    {{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
<div class="card">
  <div class="card-body">
    <form id="studentFilters" method="GET" action="{{ route('admin.students.index') }}" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Search by name or email</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="e.g. Jane, jane@domain" />
      </div>
      <div class="col-md-4">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-select">
          <option value="">All Programs</option>
          @foreach($programs as $program)
            <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Group</label>
        <select name="group_id" class="form-select">
          <option value="">All Groups</option>
          @foreach($groups as $group)
            <option value="{{ $group->id }}" @selected(request('group_id') == $group->id)>{{ $group->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 d-flex align-items-end justify-content-end">
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">Filter</button>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-export="print" data-export-target="#studentsTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
        <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#studentsTableEl" data-title="Students" data-filename="Students.pdf" data-header="Katusome Institute" data-footer-left="Katusome • Students" data-json-url="{{ route('admin.students.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
        <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#studentsTableEl" data-title="Students" data-filename="Students.xlsx" data-json-url="{{ route('admin.students.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
      </div>
    </div>
  </div>
  @include('admin.students.partials.table')
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/report-export.js'])
<script>
  (function () {
    const bindSweetDelete = () => {
      const buttons = document.querySelectorAll('.js-delete-student');
      buttons.forEach(btn => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function (ev) {
          ev.preventDefault();
          const form = btn.closest('form');
          if (!form) return;
          const name = btn.dataset.name || 'this student';

          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
              title: 'Delete student?',
              text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete',
              cancelButtonText: 'Cancel',
              buttonsStyling: false,
              customClass: {
                confirmButton: 'btn btn-danger ms-2',
                cancelButton: 'btn btn-outline-secondary'
              }
            }).then(result => {
              if (result.isConfirmed) form.submit();
            });
          } else {
            if (confirm('Delete this student?')) form.submit();
          }
        });
      });
    };

    const serializeFilters = (form) => {
      const params = new URLSearchParams();
      const search = form.querySelector('input[name="search"]');
      const program = form.querySelector('select[name="program_id"]');
      const group = form.querySelector('select[name="group_id"]');
      const page = form.dataset.page || '';
      if (search && search.value.trim() !== '') params.set('search', search.value.trim());
      if (program && program.value) params.set('program_id', program.value);
      if (group && group.value) params.set('group_id', group.value);
      if (page) params.set('page', page);
      return params;
    };

    const updateExportUrls = (baseUrl, params) => {
      const urlWithParams = `${baseUrl}?${params.toString()}`;
      const pdfBtn = document.querySelector('button[data-export="pdf"][data-json-url]');
      const excelBtn = document.querySelector('button[data-export="excel"][data-json-url]');
      if (pdfBtn) pdfBtn.setAttribute('data-json-url', `${urlWithParams}&format=json`);
      if (excelBtn) excelBtn.setAttribute('data-json-url', `${urlWithParams}&format=json`);
    };

    const bindPaginationAjax = (form, baseUrl) => {
      const footer = document.querySelector('.card-footer');
      if (!footer) return;
      footer.addEventListener('click', (ev) => {
        const a = ev.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href');
        if (!href) return;
        // Intercept and use AJAX
        ev.preventDefault();
        try {
          const url = new URL(href, window.location.origin);
          const pageParam = url.searchParams.get('page') || '';
          form.dataset.page = pageParam;
        } catch (e) {
          // fallback: try regex
          const m = href.match(/[?&]page=(\d+)/);
          form.dataset.page = m ? m[1] : '';
        }
        fetchAndReplace(form, baseUrl);
      });
    };

    const fetchAndReplace = async (form, baseUrl) => {
      const params = serializeFilters(form);
      const ajaxParams = new URLSearchParams(params);
      ajaxParams.set('fragment', 'table');
      const url = `${baseUrl}?${ajaxParams.toString()}`;
      try {
        const res = await fetch(url, {
          headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        let replaced = false;
        const newTable = doc.querySelector('#studentsTableEl');
        const newWrapper = newTable ? newTable.parentElement : doc.querySelector('.table-responsive');
        const newFooter = doc.querySelector('.card-footer');
        const curTable = document.querySelector('#studentsTableEl');
        const curWrapper = curTable ? curTable.parentElement : document.querySelector('.table-responsive');
        const curFooter = document.querySelector('.card-footer');
        if (newWrapper && curWrapper && curWrapper.parentNode) {
          const cloned = newWrapper.cloneNode(true);
          curWrapper.parentNode.replaceChild(cloned, curWrapper);
          replaced = true;
        } else if (newTable && curTable) {
          const newBody = newTable.querySelector('tbody');
          const curBody = curTable.querySelector('tbody');
          if (newBody && curBody) {
            curBody.innerHTML = newBody.innerHTML;
            replaced = true;
          }
        }
        if (newFooter && curFooter) {
          curFooter.innerHTML = newFooter.innerHTML;
          replaced = true;
        }
        // If we couldn't replace anything, fallback to full navigation
        if (!replaced) {
          window.location.assign(url);
          return;
        }
        // Re-bind delete buttons for new rows
        bindSweetDelete();
        // Re-bind pagination on the newly replaced footer
        const form = document.getElementById('studentFilters');
        if (form) bindPaginationAjax(form, baseUrl);
        // Update export URLs to reflect current filters
        updateExportUrls(baseUrl, params);
        // Update address bar (no reload) with clean params (without fragment)
        const cleanUrl = `${baseUrl}?${params.toString()}`;
        if (history && history.replaceState) {
          history.replaceState(null, '', cleanUrl);
        }
      } catch (err) {
        console.error('Failed to update list:', err);
        // Fallback to full navigation so filters still apply
        const params = serializeFilters(form);
        const url = `${baseUrl}?${params.toString()}`;
        window.location.assign(url);
      }
    };

    const debounce = (fn, wait = 300) => {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
      };
    };

    const bindLiveFilters = () => {
      const form = document.getElementById('studentFilters');
      if (!form) return;
      const baseUrl = form.action;
      const onFilterChange = debounce(() => {
        // Reset to page 1 on any filter change other than pagination
        form.dataset.page = '';
        fetchAndReplace(form, baseUrl);
      }, 250);

      const search = form.querySelector('input[name="search"]');
      const program = form.querySelector('select[name="program_id"]');
      const group = form.querySelector('select[name="group_id"]');

      if (search) search.addEventListener('input', onFilterChange);
      if (program) program.addEventListener('change', onFilterChange);
      if (group) group.addEventListener('change', onFilterChange);

      // Intercept form submit to avoid full page reload
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        onFilterChange();
      });

      // Bind pagination links to load via AJAX
      bindPaginationAjax(form, baseUrl);
    };

    const init = () => {
      bindSweetDelete();
      bindLiveFilters();
      // Initialize export URLs to current state
      const form = document.getElementById('studentFilters');
      if (form) updateExportUrls(form.action, serializeFilters(form));
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
</script>
@endsection