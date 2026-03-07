@extends('layouts/layoutMaster')

@section('title', ucfirst(str_replace('_', ' ', $role)) . ' Management')

@section('content')
  <div class="row">
    <div class="col-md-12">
      <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">MUBS / Staff /</span> {{ ucfirst(str_replace('_', ' ', $role)) }}
      </h4>

      {{-- Toolbar --}}
      <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <div class="flex-grow-1">
          <input type="text" id="user-search" class="form-control" placeholder="Search users...">
        </div>
        @if (in_array($role, ['dean', 'hod']))
          <select id="user-campus-filter" class="form-select" style="max-width: 200px;">
            <option value="">All Campuses</option>
            @foreach ($campuses as $campus)
              <option value="{{ strtolower($campus->name) }}">{{ $campus->name }}</option>
            @endforeach
          </select>
        @endif
        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#userModal"
          onclick="resetUserForm()">
          <i class="ri ri-add-line me-1"></i> Add {{ ucfirst(str_replace('_', ' ', $role)) }}
        </button>
      </div>

      {{-- Users Table --}}
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover" id="users-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                @if ($role === 'dean')
                  <th>Faculty</th>
                @elseif($role === 'hod')
                  <th>Department</th>
                @endif
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="users-tbody">
              {{-- Will be populated via AJAX --}}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- User Modal --}}
  <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userModalTitle">Add {{ ucfirst(str_replace('_', ' ', $role)) }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          {{-- Tabs --}}
          <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign-panel"
                type="button" role="tab">
                @if (in_array($role, ['hod', 'dean']))
                  Select {{ ucfirst($role) }}
                @else
                  Assign Existing User
                @endif
              </button>
            </li>
            @if (!in_array($role, ['hod', 'dean']))
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create-panel"
                  type="button" role="tab">
                  Create New User
                </button>
              </li>
            @endif
          </ul>

          <div class="tab-content">
            {{-- Assign Existing User Tab --}}
            <div class="tab-pane fade show active" id="assign-panel" role="tabpanel">
              <form id="assignUserForm">
                @csrf
                <input type="hidden" name="role" value="{{ $role }}">

                @if ($role === 'dean')
                  {{-- Dean: Select Faculty First, then show lecturers from that faculty --}}
                  <div class="mb-3">
                    <label class="form-label">Select Faculty <span class="text-danger">*</span></label>
                    <select class="form-select" name="faculty_id" id="assign-faculty-id" required>
                      <option value="">Select Faculty</option>
                      @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                      @endforeach
                    </select>
                    <small class="text-muted">Select the faculty to assign a dean</small>
                  </div>

                  <div id="dean-user-selection" style="display: none;">
                    <div class="mb-3">
                      <label class="form-label">Select Faculty Member <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="user-search-input"
                        placeholder="Type to search or leave blank to see all...">
                      <div id="user-search-results" class="list-group mt-2" style="max-height: 250px; overflow-y: auto;">
                      </div>
                      <small class="text-muted">Only lecturers from the selected faculty</small>
                    </div>

                    <input type="hidden" name="user_id" id="selected-user-id">
                    <div id="selected-user-info" class="alert alert-info" style="display: none;">
                      <strong>Selected:</strong> <span id="selected-user-name"></span>
                    </div>

                    <div class="form-check form-switch mb-3">
                      <input class="form-check-input" type="checkbox" id="make-primary" name="make_primary"
                        value="1">
                      <label class="form-check-label" for="make-primary">Make this their primary role</label>
                    </div>

                    <div class="mt-3">
                      <button type="submit" class="btn btn-primary">Assign as Dean</button>
                    </div>
                  </div>
                @elseif($role === 'hod')
                  {{-- HOD: Select Faculty, then Department, then show lecturers from that department --}}
                  <div class="mb-3">
                    <label class="form-label">Select Faculty <span class="text-danger">*</span></label>
                    <select class="form-select" id="hod-faculty-filter">
                      <option value="">Select Faculty</option>
                      @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                      @endforeach
                    </select>
                    <small class="text-muted">First select the faculty</small>
                  </div>

                  <div id="hod-department-selection" style="display: none;">
                    <div class="mb-3">
                      <label class="form-label">Select Department <span class="text-danger">*</span></label>
                      <select class="form-select" name="department_id" id="assign-department-id" required>
                        <option value="">Select Department</option>
                      </select>
                      <small class="text-muted">Select the department to assign a HOD</small>
                    </div>
                  </div>

                  <div id="hod-user-selection" style="display: none;">
                    <div class="mb-3">
                      <label class="form-label">Select Department Member <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="user-search-input"
                        placeholder="Type to search or leave blank to see all...">
                      <div id="user-search-results" class="list-group mt-2"
                        style="max-height: 250px; overflow-y: auto;"></div>
                      <small class="text-muted">Only lecturers from the selected department</small>
                    </div>

                    <input type="hidden" name="user_id" id="selected-user-id">
                    <div id="selected-user-info" class="alert alert-info" style="display: none;">
                      <strong>Selected:</strong> <span id="selected-user-name"></span>
                    </div>

                    <div class="form-check form-switch mb-3">
                      <input class="form-check-input" type="checkbox" id="make-primary" name="make_primary"
                        value="1">
                      <label class="form-check-label" for="make-primary">Make this their primary role</label>
                    </div>

                    <div class="mt-3">
                      <button type="submit" class="btn btn-primary">Assign as HOD</button>
                    </div>
                  </div>
                @else
                  {{-- Other roles: Original search interface --}}
                  <div class="row g-3 mb-3">
                    <div class="col-md-4">
                      <label class="form-label">Campus</label>
                      <select class="form-select" id="assign-campus-filter" name="campus_filter">
                        <option value="">All Campuses</option>
                        @foreach ($campuses as $campus)
                          <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Faculty</label>
                      <select class="form-select" id="assign-faculty-filter" name="faculty_filter">
                        <option value="">All Faculties</option>
                        @foreach ($faculties as $faculty)
                          <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Department</label>
                      <select class="form-select" id="assign-department-filter" name="department_filter">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                          <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Search User <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="user-search-input"
                      placeholder="Type to search by name or email...">
                    <div id="user-search-results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">
                    </div>
                  </div>

                  <input type="hidden" name="user_id" id="selected-user-id">
                  <div id="selected-user-info" class="alert alert-info" style="display: none;">
                    <strong>Selected:</strong> <span id="selected-user-name"></span>
                  </div>

                  @if ($role === 'campus_chief')
                    <div class="mb-3">
                      <label class="form-label">Assign to Campus <span class="text-danger">*</span></label>
                      <select class="form-select" name="campus_id" id="assign-campus-id" required>
                        <option value="">Select Campus</option>
                        @foreach ($campuses as $campus)
                          <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  @endif

                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="make-primary" name="make_primary"
                      value="1">
                    <label class="form-check-label" for="make-primary">Make this their primary role</label>
                  </div>

                  <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Assign Role</button>
                  </div>
                @endif
              </form>
            </div>

            {{-- Create New User Tab (only for non-HOD/Dean roles) --}}
            @if (!in_array($role, ['hod', 'dean']))
              <div class="tab-pane fade" id="create-panel" role="tabpanel">
                <form id="createUserForm">
                  @csrf
                  <input type="hidden" name="role" value="{{ $role }}">

                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">Title</label>
                      <select class="form-select" name="title" id="create-title">
                        <option value="">Select</option>
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                        <option value="Prof">Prof</option>
                      </select>
                    </div>
                    <div class="col-md-9">
                      <label class="form-label">Full Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="name" id="create-name" required>
                    </div>
                  </div>

                  <div class="row g-3 mt-2">
                    <div class="col-md-6">
                      <label class="form-label">Email <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" name="email" id="create-email" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Phone</label>
                      <input type="text" class="form-control" name="phone" id="create-phone">
                    </div>
                  </div>

                  <div class="mb-3 mt-3">
                    <label class="form-label">Password (leave blank to auto-generate)</label>
                    <input type="password" class="form-control" name="password" id="create-password">
                  </div>

                  @if ($role === 'campus_chief')
                    <div class="mb-3">
                      <label class="form-label">Assign to Campus <span class="text-danger">*</span></label>
                      <select class="form-select" name="campus_id" id="create-campus-id" required>
                        <option value="">Select Campus</option>
                        @foreach ($campuses as $campus)
                          <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  @endif

                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="send-welcome-email" name="send_welcome_email"
                      value="1" checked>
                    <label class="form-check-label" for="send-welcome-email">Send welcome email with login
                      credentials</label>
                  </div>

                  <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Create User</button>
                  </div>
                </form>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection


@section('page-script')
  <script>
    const ROLE = '{{ $role }}';
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', function() {
      loadUsers();

      document.getElementById('user-search')?.addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterTable, 300);
      });

      document.getElementById('user-campus-filter')?.addEventListener('change', filterTable);

      // Attach event listeners using event delegation on the document
      document.addEventListener('change', function(e) {
        // For Dean: Show user search when faculty is selected
        if (e.target && e.target.id === 'assign-faculty-id' && ROLE === 'dean') {
          const facultyId = e.target.value;
          const userSelection = document.getElementById('dean-user-selection');
          console.log('Faculty selected:', facultyId, 'User selection div:', userSelection);
          if (facultyId) {
            userSelection.style.display = 'block';
            document.getElementById('user-search-input').value = '';
            document.getElementById('user-search-results').innerHTML = '';
            document.getElementById('selected-user-id').value = '';
            document.getElementById('selected-user-info').style.display = 'none';
            // Auto-trigger search to show all users
            setTimeout(() => {
              document.getElementById('user-search-input').value = ' ';
              searchUsers();
            }, 100);
          } else {
            userSelection.style.display = 'none';
          }
        }
      });

      // For HOD: Cascade faculty -> department -> user search using event delegation
      document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'hod-faculty-filter' && ROLE === 'hod') {
          const facultyId = e.target.value;
          const deptSelection = document.getElementById('hod-department-selection');
          const userSelection = document.getElementById('hod-user-selection');
          const deptSelect = document.getElementById('assign-department-id');

          if (facultyId) {
            fetch(`/admin/users/api/faculty-departments/${facultyId}`)
              .then(r => r.json())
              .then(departments => {
                deptSelect.innerHTML = '<option value="">Select Department</option>';
                departments.forEach(dept => {
                  deptSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
                });
                deptSelection.style.display = 'block';
                userSelection.style.display = 'none';
              })
              .catch(err => {
                console.error('Failed to load departments:', err);
                showToast('error', 'Failed to load departments');
              });
          } else {
            deptSelection.style.display = 'none';
            userSelection.style.display = 'none';
          }
        }

        if (e.target && e.target.id === 'assign-department-id' && ROLE === 'hod') {
          const deptId = e.target.value;
          const userSelection = document.getElementById('hod-user-selection');
          if (deptId) {
            userSelection.style.display = 'block';
            document.getElementById('user-search-input').value = '';
            document.getElementById('user-search-results').innerHTML = '';
            document.getElementById('selected-user-id').value = '';
            document.getElementById('selected-user-info').style.display = 'none';
            // Auto-trigger search to show all users
            setTimeout(() => {
              document.getElementById('user-search-input').value = ' ';
              searchUsers();
            }, 100);
          } else {
            userSelection.style.display = 'none';
          }
        }
      });


      const userSearchInput = document.getElementById('user-search-input');
      userSearchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchUsers(), 300);
      });

      if (!['hod', 'dean'].includes(ROLE)) {
        ['assign-campus-filter', 'assign-faculty-filter', 'assign-department-filter'].forEach(id => {
          document.getElementById(id)?.addEventListener('change', () => {
            if (userSearchInput.value.trim()) {
              searchUsers();
            }
          });
        });
      }

      document.getElementById('assignUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        assignRole();
      });

      document.getElementById('createUserForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        createUser();
      });

      @if (session('success'))
        showToast('success', '{{ session('success') }}');
      @endif
      @if (session('error'))
        showToast('error', '{{ session('error') }}');
      @endif
    });

    function resetUserForm() {
      document.getElementById('assignUserForm')?.reset();
      document.getElementById('createUserForm')?.reset();
      document.getElementById('user-search-results').innerHTML = '';
      document.getElementById('selected-user-id').value = '';
      document.getElementById('selected-user-info').style.display = 'none';
      document.getElementById('send-welcome-email')?.checked = true;

      if (ROLE === 'dean') {
        document.getElementById('dean-user-selection').style.display = 'none';
      }
      if (ROLE === 'hod') {
        document.getElementById('hod-department-selection').style.display = 'none';
        document.getElementById('hod-user-selection').style.display = 'none';
      }
    }

    function loadUsers() {
      fetch(`/admin/users/${ROLE}/list`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(users => {
          renderUsersTable(users);
        })
        .catch(err => {
          console.error('Failed to load users:', err);
          showToast('error', 'Failed to load users');
        });
    }

    function renderUsersTable(users) {
      const tbody = document.getElementById('users-tbody');
      if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No users found.</td></tr>';
        return;
      }

      tbody.innerHTML = users.map((user, index) => {
        let assignmentCol = '';
        if (ROLE === 'dean') {
          assignmentCol = `<td>${user.faculty || '—'}</td>`;
        } else if (ROLE === 'hod') {
          assignmentCol = `<td>${user.department || '—'}</td>`;
        }

        return `
                    <tr data-search="${user.name.toLowerCase()} ${user.email.toLowerCase()}"
                        data-campus="${(user.campus || '').toLowerCase()}">
                        <td>${index + 1}</td>
                        <td><span class="fw-medium">${user.title ? user.title + ' ' : ''}${user.name}</span></td>
                        <td>${user.email}</td>
                        <td>${user.phone || '—'}</td>
                        ${assignmentCol}
                        <td>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger"
                                onclick="removeUser(${user.user_role_id || user.user_id}, ${!user.user_role_id})"
                                title="Remove">
                                <i class="ri ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                `;
      }).join('');
    }

    function filterTable() {
      const searchTerm = document.getElementById('user-search')?.value.toLowerCase() || '';
      const campusFilter = document.getElementById('user-campus-filter')?.value || '';

      document.querySelectorAll('#users-table tbody tr[data-search]').forEach(row => {
        const matchText = !searchTerm || row.dataset.search.includes(searchTerm);
        const matchCampus = !campusFilter || row.dataset.campus.includes(campusFilter);
        row.style.display = (matchText && matchCampus) ? '' : 'none';
      });
    }


    function searchUsers() {
      const searchTerm = document.getElementById('user-search-input').value.trim();
      const resultsDiv = document.getElementById('user-search-results');

      const params = new URLSearchParams();

      if (searchTerm && searchTerm !== ' ') {
        params.append('search', searchTerm);
      }

      if (ROLE === 'dean') {
        const facultyId = document.getElementById('assign-faculty-id').value;
        if (facultyId) {
          params.append('faculty_id', facultyId);
        } else {
          resultsDiv.innerHTML = '<div class="list-group-item text-muted">Please select a faculty first</div>';
          return;
        }
      }

      if (ROLE === 'hod') {
        const deptId = document.getElementById('assign-department-id').value;
        if (deptId) {
          params.append('department_id', deptId);
        } else {
          resultsDiv.innerHTML = '<div class="list-group-item text-muted">Please select a department first</div>';
          return;
        }
      }

      if (!['hod', 'dean'].includes(ROLE)) {
        const campusId = document.getElementById('assign-campus-filter')?.value;
        const facultyId = document.getElementById('assign-faculty-filter')?.value;
        const deptId = document.getElementById('assign-department-filter')?.value;

        if (campusId) params.append('campus_id', campusId);
        if (facultyId) params.append('faculty_id', facultyId);
        if (deptId) params.append('department_id', deptId);
      }

      resultsDiv.innerHTML = '<div class="list-group-item text-muted">Searching...</div>';

      fetch(`/admin/users/search?${params}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(users => {
          if (users.length === 0) {
            resultsDiv.innerHTML =
              `<div class="list-group-item text-muted">No users found in this ${ROLE === 'dean' ? 'faculty' : ROLE === 'hod' ? 'department' : 'selection'}</div>`;
            return;
          }

          resultsDiv.innerHTML = users.map(user => `
                        <a href="#" class="list-group-item list-group-item-action" onclick="selectUser(${user.id}, '${user.name.replace(/'/g, "\\'")}', event)">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${user.title ? user.title + ' ' : ''}${user.name}</strong>
                                    <br><small class="text-muted">${user.email}</small>
                                </div>
                                <div class="text-end">
                                    <small class="badge bg-label-info">${user.current_role}</small>
                                    ${user.department ? '<br><small class="text-muted">' + user.department + '</small>' : ''}
                                </div>
                            </div>
                        </a>
                    `).join('');
        })
        .catch(err => {
          console.error('Search failed:', err);
          resultsDiv.innerHTML = '<div class="list-group-item text-danger">Search failed</div>';
        });
    }

    function selectUser(userId, userName, event) {
      event.preventDefault();
      document.getElementById('selected-user-id').value = userId;
      document.getElementById('selected-user-name').textContent = userName;
      document.getElementById('selected-user-info').style.display = 'block';
      document.getElementById('user-search-results').innerHTML = '';
    }

    function assignRole() {
      const form = document.getElementById('assignUserForm');
      const formData = new FormData(form);

      if (!formData.get('user_id')) {
        showToast('error', 'Please select a user');
        return;
      }

      fetch(`/admin/users/${ROLE}`, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
          } else {
            showToast('error', data.message);
          }
        })
        .catch(err => {
          console.error('Assign failed:', err);
          showToast('error', 'Failed to assign role');
        });
    }

    function createUser() {
      const form = document.getElementById('createUserForm');
      const formData = new FormData(form);

      fetch(`/admin/users/${ROLE}`, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            showToast('success', data.message);
            if (data.password) {
              showToast('info', 'Generated password: ' + data.password);
            }
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
          } else {
            showToast('error', data.message);
          }
        })
        .catch(err => {
          console.error('Create failed:', err);
          showToast('error', 'Failed to create user');
        });
    }

    function removeUser(id, isPrimaryRole) {
      const endpoint = isPrimaryRole ? `/admin/users/${ROLE}/${id}` : `/admin/users/role/${id}`;

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Remove this user?',
          text: "This action cannot be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, remove!'
        }).then(r => {
          if (r.isConfirmed) {
            performRemove(endpoint);
          }
        });
      } else {
        if (confirm('Remove this user?')) {
          performRemove(endpoint);
        }
      }
    }

    function performRemove(endpoint) {
      fetch(endpoint, {
          method: 'DELETE',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            showToast('success', data.message);
            loadUsers();
          } else {
            showToast('error', data.message);
          }
        })
        .catch(err => {
          console.error('Remove failed:', err);
          showToast('error', 'Failed to remove user');
        });
    }

    function showToast(type, message) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: type,
          title: type === 'success' ? 'Success!' : type === 'info' ? 'Info' : 'Error!',
          text: message,
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
      } else {
        alert(message);
      }
    }
  </script>
@endsection
