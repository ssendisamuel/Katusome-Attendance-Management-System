@extends('layouts/layoutMaster')

@section('title', 'All Staff & Administrators')

@section('content')
  <div class="row">
    <div class="col-md-12">
      <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">MUBS / Users /</span> All Staff & Administrators
      </h4>

      {{-- Filters Card --}}
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Search</label>
              <input type="text" id="staff-search" class="form-control" placeholder="Search by name or email...">
            </div>
            <div class="col-md-2">
              <label class="form-label">Role</label>
              <select id="role-filter" class="form-select">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
                <option value="principal">Principal</option>
                <option value="registrar">Registrar</option>
                <option value="campus_chief">Campus Chief</option>
                <option value="qa_director">QA Director</option>
                <option value="dean">Dean</option>
                <option value="hod">HOD</option>
                <option value="lecturer">Lecturer</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Campus</label>
              <select id="campus-filter" class="form-select">
                <option value="">All Campuses</option>
                @foreach ($campuses as $campus)
                  <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Faculty</label>
              <select id="faculty-filter" class="form-select">
                <option value="">All Faculties</option>
                @foreach ($faculties as $faculty)
                  <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Department</label>
              <select id="department-filter" class="form-select">
                <option value="">All Departments</option>
                @foreach ($departments as $dept)
                  <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                <i class="ri ri-refresh-line"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- Staff Table --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Staff Members</h5>
          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="ri ri-add-line me-1"></i> Assign Role
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover" id="staff-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Primary Role</th>
                <th>All Roles</th>
                <th>Assignment</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="staff-tbody">
              <tr>
                <td colspan="7" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <span class="ms-2">Loading staff...</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Add/Assign Role Modal --}}
  <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Assign Role to User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="assignRoleForm">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Search User <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="modal-user-search"
                placeholder="Type to search by name or email...">
              <div id="modal-user-results" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;"></div>
            </div>

            <input type="hidden" name="user_id" id="modal-selected-user-id">
            <div id="modal-selected-user-info" class="alert alert-info" style="display: none;">
              <strong>Selected:</strong> <span id="modal-selected-user-name"></span>
            </div>

            <div class="mb-3">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <select class="form-select" name="role" id="modal-role-select" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
                <option value="principal">Principal</option>
                <option value="registrar">Registrar</option>
                <option value="campus_chief">Campus Chief</option>
                <option value="qa_director">QA Director</option>
                <option value="dean">Dean</option>
                <option value="hod">HOD</option>
                <option value="lecturer">Lecturer</option>
              </select>
            </div>

            <div id="modal-assignment-fields">
              <div class="mb-3" id="modal-campus-field" style="display: none;">
                <label class="form-label">Campus</label>
                <select class="form-select" name="campus_id" id="modal-campus-select">
                  <option value="">Select Campus</option>
                  @foreach ($campuses as $campus)
                    <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="mb-3" id="modal-faculty-field" style="display: none;">
                <label class="form-label">Faculty <span class="text-danger"
                    id="modal-faculty-required">*</span></label>
                <select class="form-select" name="faculty_id" id="modal-faculty-select">
                  <option value="">Select Faculty</option>
                  @foreach ($faculties as $faculty)
                    <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="mb-3" id="modal-department-field" style="display: none;">
                <label class="form-label">Department <span class="text-danger"
                    id="modal-department-required">*</span></label>
                <select class="form-select" name="department_id" id="modal-department-select">
                  <option value="">Select Department</option>
                  @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->faculty->name }})</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="make_primary" id="modal-make-primary"
                value="1">
              <label class="form-check-label" for="modal-make-primary">Make this their primary role</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Assign Role</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    let searchTimeout;
    let allStaff = [];

    document.addEventListener('DOMContentLoaded', function() {
      loadAllStaff();

      // Filter event listeners
      document.getElementById('staff-search').addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterStaff, 300);
      });

      ['role-filter', 'campus-filter', 'faculty-filter', 'department-filter'].forEach(id => {
        document.getElementById(id).addEventListener('change', filterStaff);
      });

      // Modal user search
      document.getElementById('modal-user-search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(searchModalUsers, 300);
      });

      // Role selection changes assignment fields
      document.getElementById('modal-role-select').addEventListener('change', function() {
        updateAssignmentFields(this.value);
      });

      // For lecturer role: cascade faculty -> department
      document.getElementById('modal-faculty-select').addEventListener('change', function() {
        const role = document.getElementById('modal-role-select').value;
        if (role === 'lecturer') {
          loadDepartmentsForFaculty(this.value);
        }
      });

      // Form submission
      document.getElementById('assignRoleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        assignRoleToUser();
      });

      @if (session('success'))
        showToast('success', '{{ session('success') }}');
      @endif
      @if (session('error'))
        showToast('error', '{{ session('error') }}');
      @endif
    });

    function loadAllStaff() {
      fetch('/admin/users/all-staff/list', {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(data => {
          console.log('Loaded staff:', data.length, 'users');
          console.log('Sample user:', data[0]);
          allStaff = data;
          renderStaffTable(allStaff);
        })
        .catch(err => {
          console.error('Failed to load staff:', err);
          document.getElementById('staff-tbody').innerHTML =
            '<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load staff</td></tr>';
        });
    }

    function renderStaffTable(staff) {
      const tbody = document.getElementById('staff-tbody');
      if (!staff || staff.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No staff found</td></tr>';
        return;
      }

      tbody.innerHTML = staff.map((user, index) => {
        const roles = user.all_roles.map(r => `<span class="badge bg-label-primary me-1">${r}</span>`).join('');
        const assignment = user.assignments.join(', ') || '—';

        return `
          <tr data-user-id="${user.id}">
            <td>${index + 1}</td>
            <td>
              <div class="d-flex align-items-center">
                <div>
                  <strong>${user.title ? user.title + ' ' : ''}${user.name}</strong>
                </div>
              </div>
            </td>
            <td>${user.email}</td>
            <td><span class="badge bg-label-success">${user.primary_role}</span></td>
            <td>${roles}</td>
            <td><small class="text-muted">${assignment}</small></td>
            <td>
              <div class="dropdown">
                <button type="button" class="btn btn-sm btn-icon btn-outline-primary dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown">
                  <i class="ri ri-more-2-line"></i>
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <a class="dropdown-item" href="#" onclick="viewUserRoles(${user.id}, event)">
                      <i class="ri ri-eye-line me-2"></i> View Roles
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="#" onclick="editUserRoles(${user.id}, event)">
                      <i class="ri ri-edit-line me-2"></i> Manage Roles
                    </a>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function filterStaff() {
      const searchTerm = document.getElementById('staff-search').value.toLowerCase();
      const roleFilter = document.getElementById('role-filter').value.toLowerCase();
      const campusFilter = document.getElementById('campus-filter').value;
      const facultyFilter = document.getElementById('faculty-filter').value;
      const deptFilter = document.getElementById('department-filter').value;

      console.log('Filtering with:', {
        searchTerm,
        roleFilter,
        campusFilter,
        facultyFilter,
        deptFilter
      });

      const filtered = allStaff.filter(user => {
        const matchSearch = !searchTerm ||
          user.name.toLowerCase().includes(searchTerm) ||
          user.email.toLowerCase().includes(searchTerm);

        // Check if role matches (check both primary and all roles, case-insensitive)
        const matchRole = !roleFilter ||
          user.primary_role_raw.toLowerCase() === roleFilter ||
          user.all_roles.some(r => r.toLowerCase().replace(/ /g, '_') === roleFilter);

        const matchCampus = !campusFilter || user.campus_ids.includes(parseInt(campusFilter));
        const matchFaculty = !facultyFilter || user.faculty_ids.includes(parseInt(facultyFilter));
        const matchDept = !deptFilter || user.department_ids.includes(parseInt(deptFilter));

        if (!matchRole && roleFilter) {
          console.log('Role mismatch for', user.name, ':', {
            roleFilter,
            primary_role_raw: user.primary_role_raw,
            all_roles: user.all_roles
          });
        }

        return matchSearch && matchRole && matchCampus && matchFaculty && matchDept;
      });

      console.log('Filtered results:', filtered.length, 'users');
      renderStaffTable(filtered);
    }

    function resetFilters() {
      document.getElementById('staff-search').value = '';
      document.getElementById('role-filter').value = '';
      document.getElementById('campus-filter').value = '';
      document.getElementById('faculty-filter').value = '';
      document.getElementById('department-filter').value = '';
      renderStaffTable(allStaff);
    }

    function searchModalUsers() {
      const searchTerm = document.getElementById('modal-user-search').value.trim();
      const resultsDiv = document.getElementById('modal-user-results');

      if (searchTerm.length < 2) {
        resultsDiv.innerHTML = '';
        return;
      }

      const params = new URLSearchParams({
        search: searchTerm
      });

      resultsDiv.innerHTML = '<div class="list-group-item text-muted">Searching...</div>';

      fetch(`/admin/users/search?${params}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(users => {
          if (users.length === 0) {
            resultsDiv.innerHTML = '<div class="list-group-item text-muted">No users found</div>';
            return;
          }

          resultsDiv.innerHTML = users.map(user => {
            const userName = (user.name || '').replace(/'/g, "\\'");
            return `
              <a href="#" class="list-group-item list-group-item-action" onclick="selectModalUser(${user.id}, '${userName}', event)">
                <div class="d-flex justify-content-between">
                  <div>
                    <strong>${user.title ? user.title + ' ' : ''}${user.name}</strong>
                    <br><small class="text-muted">${user.email}</small>
                  </div>
                  <div class="text-end">
                    <small class="badge bg-label-info">${user.current_role}</small>
                  </div>
                </div>
              </a>
            `;
          }).join('');
        })
        .catch(err => {
          console.error('Search failed:', err);
          resultsDiv.innerHTML = '<div class="list-group-item text-danger">Search failed</div>';
        });
    }

    function selectModalUser(userId, userName, event) {
      event.preventDefault();
      document.getElementById('modal-selected-user-id').value = userId;
      document.getElementById('modal-selected-user-name').textContent = userName;
      document.getElementById('modal-selected-user-info').style.display = 'block';
      document.getElementById('modal-user-results').innerHTML = '';
    }

    function updateAssignmentFields(role) {
      // Hide all fields first
      document.getElementById('modal-campus-field').style.display = 'none';
      document.getElementById('modal-faculty-field').style.display = 'none';
      document.getElementById('modal-department-field').style.display = 'none';

      // Reset required indicators
      const facultyRequired = document.getElementById('modal-faculty-required');
      const deptRequired = document.getElementById('modal-department-required');
      if (facultyRequired) facultyRequired.style.display = 'none';
      if (deptRequired) deptRequired.style.display = 'none';

      // Show relevant fields based on role
      if (role === 'campus_chief') {
        document.getElementById('modal-campus-field').style.display = 'block';
      } else if (role === 'dean') {
        document.getElementById('modal-faculty-field').style.display = 'block';
        if (facultyRequired) facultyRequired.style.display = 'inline';
      } else if (role === 'hod') {
        document.getElementById('modal-department-field').style.display = 'block';
        if (deptRequired) deptRequired.style.display = 'inline';
      } else if (role === 'lecturer') {
        // For lecturer: show faculty first, then department will appear after faculty selection
        document.getElementById('modal-faculty-field').style.display = 'block';
        if (facultyRequired) facultyRequired.style.display = 'inline';
        // Reset department dropdown
        const deptSelect = document.getElementById('modal-department-select');
        deptSelect.innerHTML = '<option value="">Select Department</option>';
      }
    }

    function loadDepartmentsForFaculty(facultyId) {
      const deptField = document.getElementById('modal-department-field');
      const deptSelect = document.getElementById('modal-department-select');
      const deptRequired = document.getElementById('modal-department-required');

      if (!facultyId) {
        deptField.style.display = 'none';
        deptSelect.innerHTML = '<option value="">Select Department</option>';
        return;
      }

      // Show loading
      deptSelect.innerHTML = '<option value="">Loading departments...</option>';
      deptField.style.display = 'block';
      if (deptRequired) deptRequired.style.display = 'inline';

      // Load departments for this faculty
      fetch(`/admin/users/api/faculty-departments/${facultyId}`)
        .then(r => r.json())
        .then(departments => {
          deptSelect.innerHTML = '<option value="">Select Department</option>';
          departments.forEach(dept => {
            deptSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
          });
        })
        .catch(err => {
          console.error('Failed to load departments:', err);
          deptSelect.innerHTML = '<option value="">Failed to load departments</option>';
          showToast('error', 'Failed to load departments');
        });
    }

    function assignRoleToUser() {
      const form = document.getElementById('assignRoleForm');
      const formData = new FormData(form);
      const role = formData.get('role');

      if (!formData.get('user_id')) {
        showToast('error', 'Please select a user');
        return;
      }

      if (!role) {
        showToast('error', 'Please select a role');
        return;
      }

      // Validate required fields based on role
      if (role === 'dean' && !formData.get('faculty_id')) {
        showToast('error', 'Please select a faculty for Dean role');
        return;
      }

      if (role === 'hod' && !formData.get('department_id')) {
        showToast('error', 'Please select a department for HOD role');
        return;
      }

      if (role === 'lecturer') {
        if (!formData.get('faculty_id')) {
          showToast('error', 'Please select a faculty for Lecturer role');
          return;
        }
        if (!formData.get('department_id')) {
          showToast('error', 'Please select a department for Lecturer role');
          return;
        }
      }

      if (role === 'campus_chief' && !formData.get('campus_id')) {
        showToast('error', 'Please select a campus for Campus Chief role');
        return;
      }

      fetch('/admin/users/assign-role', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('addRoleModal'));
            if (modal) modal.hide();
            form.reset();
            document.getElementById('modal-selected-user-info').style.display = 'none';
            updateAssignmentFields(''); // Reset fields
            loadAllStaff();
          } else {
            showToast('error', data.message);
          }
        })
        .catch(err => {
          console.error('Assign failed:', err);
          showToast('error', 'Failed to assign role');
        });
    }

    function viewUserRoles(userId, event) {
      event.preventDefault();
      const user = allStaff.find(u => u.id === userId);
      if (!user) return;

      const rolesHtml = user.all_roles.map(role => `<li>${role}</li>`).join('');

      Swal.fire({
        title: user.name,
        html: `
          <div class="text-start">
            <p><strong>Email:</strong> ${user.email}</p>
            <p><strong>Primary Role:</strong> <span class="badge bg-label-success">${user.primary_role}</span></p>
            <p><strong>All Roles:</strong></p>
            <ul>${rolesHtml}</ul>
            <p><strong>Assignments:</strong> ${user.assignments.join(', ') || 'None'}</p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Close'
      });
    }

    function editUserRoles(userId, event) {
      event.preventDefault();
      const user = allStaff.find(u => u.id === userId);
      if (!user) return;

      // Fetch detailed role information for this user
      fetch(`/admin/users/${userId}/roles-detail`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(r => r.json())
        .then(data => {
          showRoleManagementModal(user, data);
        })
        .catch(err => {
          console.error('Failed to load role details:', err);
          showToast('error', 'Failed to load role details');
        });
    }

    function showRoleManagementModal(user, roleDetails) {
      const rolesHtml = roleDetails.roles.map(role => {
        const isPrimary = role.is_primary;
        const badge = isPrimary ? '<span class="badge bg-success ms-2">Primary</span>' : '';
        const deleteBtn = !isPrimary ?
          `<button class="btn btn-sm btn-icon btn-outline-danger" onclick="removeUserRole(${user.id}, ${role.user_role_id}, '${role.role}', event)" title="Remove">
            <i class="ri ri-delete-bin-line"></i>
          </button>` :
          '<small class="text-muted">Cannot remove primary</small>';

        const setPrimaryBtn = !isPrimary ?
          `<button class="btn btn-sm btn-outline-primary" onclick="setPrimaryRole(${user.id}, '${role.role}', event)">
            Set as Primary
          </button>` :
          '';

        return `
          <div class="card mb-2">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <strong>${role.role_display}</strong>${badge}
                  ${role.assignment ? '<br><small class="text-muted">' + role.assignment + '</small>' : ''}
                </div>
                <div class="btn-group">
                  ${setPrimaryBtn}
                  ${deleteBtn}
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');

      Swal.fire({
        title: 'Manage Roles: ' + user.name,
        html: `
          <div class="text-start">
            <p><strong>Email:</strong> ${user.email}</p>
            <p><strong>Current Roles:</strong></p>
            ${rolesHtml}
            <hr>
            <button class="btn btn-primary w-100" onclick="openAssignRoleForUser(${user.id}, '${user.name.replace(/'/g, "\\'")}')">
              <i class="ri ri-add-line me-1"></i> Assign New Role
            </button>
          </div>
        `,
        width: '600px',
        showConfirmButton: false,
        showCloseButton: true
      });
    }

    function openAssignRoleForUser(userId, userName) {
      Swal.close();
      document.getElementById('modal-selected-user-id').value = userId;
      document.getElementById('modal-selected-user-name').textContent = userName;
      document.getElementById('modal-selected-user-info').style.display = 'block';
      const modal = new bootstrap.Modal(document.getElementById('addRoleModal'));
      modal.show();
    }

    function removeUserRole(userId, userRoleId, role, event) {
      event.preventDefault();
      event.stopPropagation();

      Swal.fire({
        title: 'Remove Role?',
        text: `Are you sure you want to remove the ${role} role from this user?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it!'
      }).then((result) => {
        if (result.isConfirmed) {
          const endpoint = userRoleId ? `/admin/users/role/${userRoleId}` : `/admin/users/${userId}/role/${role}`;

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
                loadAllStaff();
                Swal.close();
              } else {
                showToast('error', data.message);
              }
            })
            .catch(err => {
              console.error('Remove failed:', err);
              showToast('error', 'Failed to remove role');
            });
        }
      });
    }

    function setPrimaryRole(userId, role, event) {
      event.preventDefault();
      event.stopPropagation();

      Swal.fire({
        title: 'Set Primary Role?',
        text: `Set ${role} as the primary role for this user?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, set it!'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`/admin/users/${userId}/set-primary-role`, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                role: role
              })
            })
            .then(r => r.json())
            .then(data => {
              if (data.success) {
                showToast('success', data.message);
                loadAllStaff();
                Swal.close();
              } else {
                showToast('error', data.message);
              }
            })
            .catch(err => {
              console.error('Set primary failed:', err);
              showToast('error', 'Failed to set primary role');
            });
        }
      });
    }

    function showToast(type, message) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: type,
          title: type === 'success' ? 'Success!' : 'Error!',
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
