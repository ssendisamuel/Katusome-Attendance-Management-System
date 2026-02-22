@extends('layouts/layoutMaster')

@section('title', 'Students')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Students</h4>
    <div>
      <a href="{{ route('admin.students.import.form') }}" class="btn btn-outline-primary me-2">Bulk Upload</a>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">Add
        Student</button>
    </div>
  </div>
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if (session('info'))
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
          <input type="text" name="search" value="{{ request('search') }}" class="form-control"
            placeholder="e.g. Jane, jane@domain" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Program</label>
          <select name="program_id" class="form-select">
            <option value="">All Programs</option>
            @foreach ($programs as $program)
              <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Group</label>
          <select name="group_id" class="form-select">
            <option value="">All Groups</option>
            @foreach ($groups as $group)
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
          <button type="button" class="btn btn-outline-secondary" data-export="print"
            data-export-target="#studentsTableEl"><span class="icon-base ri ri-printer-line me-1"></span>Print</button>
          <button type="button" class="btn btn-outline-danger" data-export="pdf" data-export-target="#studentsTableEl"
            data-title="Students" data-filename="Students.pdf" data-header="Katusome Institute"
            data-footer-left="Katusome • Students"
            data-json-url="{{ route('admin.students.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span
              class="icon-base ri ri-file-pdf-line me-1"></span>PDF</button>
          <button type="button" class="btn btn-outline-success" data-export="excel" data-export-target="#studentsTableEl"
            data-title="Students" data-filename="Students.xlsx"
            data-json-url="{{ route('admin.students.index', array_merge(request()->query(), ['format' => 'json'])) }}"><span
              class="icon-base ri ri-file-excel-line me-1"></span>Excel</button>
        </div>
      </div>
    </div>
    @include('admin.students.partials.table')

    <!-- Create Student Modal -->
    <div class="modal fade" id="createStudentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Student</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('admin.students.store') }}" id="createStudentForm">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone</label>
                  <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Gender</label>
                  <select name="gender" class="form-select">
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Student No <span class="text-danger">*</span></label>
                  <input type="text" name="student_no" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Reg No <span class="text-danger">*</span></label>
                  <input type="text" name="reg_no" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Program</label>
                  <select name="program_id" class="form-select">
                    <option value="">Select Program</option>
                    @foreach ($programs as $program)
                      <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Group</label>
                  <select name="group_id" class="form-select">
                    <option value="">Select Group</option>
                    @foreach ($groups as $group)
                      <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Year of Study</label>
                  <select name="year_of_study" class="form-select">
                    <option value="">Select Year</option>
                    <option value="1">Year 1</option>
                    <option value="2">Year 2</option>
                    <option value="3">Year 3</option>
                    <option value="4">Year 4</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Initial Password</label>
                  <input type="text" name="initial_password" class="form-control"
                    placeholder="Defaults to 'password'">
                </div>
              </div>
              <div class="mt-4 text-end">
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSaveNewStudent">Save Student</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- Student Details Modal -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Student Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="studentDetailsModalBody">
            <div class="text-center p-4">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="mt-2 text-muted">Loading details...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  @vite(['resources/assets/js/report-export.js'])
  <script>
    (function() {
      const bindSweetDelete = () => {
        const buttons = document.querySelectorAll('.js-delete-student');
        buttons.forEach(btn => {
          if (btn.dataset.bound === '1') return;
          btn.dataset.bound = '1';
          btn.addEventListener('click', function(ev) {
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
          bindStudentPreviewModal();
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
        form.addEventListener('submit', function(ev) {
          ev.preventDefault();
          onFilterChange();
        });

        // Bind pagination links to load via AJAX
        bindPaginationAjax(form, baseUrl);
      };

      const bindStudentPreviewModal = () => {
        const modalEl = document.getElementById('studentDetailsModal');
        const modalBody = document.getElementById('studentDetailsModalBody');
        if (!modalEl || !modalBody) return;

        let bsModal;

        document.querySelectorAll('.view-student-details').forEach(btn => {
          // Avoid double binding
          if (btn.dataset.boundModal === 'true') return;
          btn.dataset.boundModal = 'true';

          btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.url;
            if (!url) return;

            // Reset body to loading state
            modalBody.innerHTML =
              '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading details...</p></div>';

            // Show modal
            if (!bsModal) {
              bsModal = new(window.bootstrap?.Modal || function() {})(modalEl);
            }
            if (bsModal && bsModal.show) bsModal.show();

            // Fetch details
            fetch(url, {
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                }
              })
              .then(r => {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.text();
              })
              .then(html => {
                modalBody.innerHTML = html;
              })
              .catch(err => {
                console.error(err);
                modalBody.innerHTML =
                  '<div class="text-danger text-center p-3">Failed to load details.</div>';
              });
          });
        });
      };

      const init = () => {
        bindSweetDelete();
        bindLiveFilters();
        bindStudentPreviewModal();
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
