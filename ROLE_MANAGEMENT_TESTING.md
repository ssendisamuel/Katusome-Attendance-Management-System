# Role Management System - Testing Guide

## Overview

The comprehensive role management system has been implemented on the "All Staff" page at `/admin/users/all-staff`.

## Features Implemented

### 1. View All Staff

- Lists all administrators and staff members
- Shows primary role and all assigned roles
- Displays organizational assignments (Dean of X, HOD of Y, Lecturer - Department Z)

### 2. Advanced Filtering

- Search by name or email
- Filter by role (Admin, Super Admin, Principal, Registrar, Campus Chief, QA Director, Dean, HOD, Lecturer)
- Filter by campus, faculty, or department
- All filters work together

### 3. Assign New Roles

- Click "Assign Role" button in the top right
- Search for existing users
- Select role to assign
- Role-specific fields appear automatically:
  - **Campus Chief**: Select campus
  - **Dean**: Select faculty (required)
  - **HOD**: Select department (required)
  - **Lecturer**: Select faculty → department (cascade, both required)
- Option to make the new role their primary role
- Creates both UserRole record and Lecturer record (for lecturer role)

### 4. Manage User Roles (NEW!)

- Click the dropdown menu (⋮) next to any user
- Select "Manage Roles" to open the role management modal
- View all roles assigned to the user
- **Set Primary Role**: Click "Set as Primary" button on any non-primary role
- **Remove Role**: Click the delete icon (🗑️) to remove a role
  - Cannot remove the only role from a user
  - If removing primary role, system automatically switches to another role
- **Assign New Role**: Click "Assign New Role" button at the bottom of the modal

## Testing Checklist

### Test 1: View All Staff

- [ ] Navigate to `/admin/users/all-staff`
- [ ] Verify all staff members are displayed
- [ ] Check that roles are shown correctly
- [ ] Verify assignments are displayed

### Test 2: Filtering

- [ ] Test search by name
- [ ] Test search by email
- [ ] Test role filter (try "lecturer", "admin", "hod")
- [ ] Test campus filter
- [ ] Test faculty filter
- [ ] Test department filter
- [ ] Test combined filters
- [ ] Click reset button to clear filters

### Test 3: Assign Lecturer Role

- [ ] Click "Assign Role" button
- [ ] Search for user (e.g., "sssendi@mubs.ac.ug")
- [ ] Select "Lecturer" role
- [ ] Select faculty (department dropdown should appear)
- [ ] Select department
- [ ] Check "Make this their primary role" if desired
- [ ] Submit form
- [ ] Verify success message
- [ ] Verify role appears in the user's role list
- [ ] Check database: both `lecturers` table and `user_roles` table should have records

### Test 4: Assign Other Roles

- [ ] Assign Dean role (requires faculty)
- [ ] Assign HOD role (requires department)
- [ ] Assign Campus Chief role (requires campus)
- [ ] Assign Admin role (no additional fields)

### Test 5: Manage Roles Modal

- [ ] Click dropdown menu (⋮) next to a user with multiple roles
- [ ] Click "Manage Roles"
- [ ] Verify all roles are displayed
- [ ] Verify primary role is marked with green badge
- [ ] Verify assignments are shown correctly

### Test 6: Set Primary Role

- [ ] Open "Manage Roles" for a user with multiple roles
- [ ] Click "Set as Primary" on a non-primary role
- [ ] Verify success message
- [ ] Verify the role is now marked as primary
- [ ] Verify the page refreshes with updated data

### Test 7: Remove Role

- [ ] Open "Manage Roles" for a user with multiple roles
- [ ] Click delete icon (🗑️) on a non-primary role
- [ ] Confirm deletion in the popup
- [ ] Verify success message
- [ ] Verify role is removed from the list
- [ ] Try to remove the only role (should show error)
- [ ] Try to remove primary role when other roles exist (should work and switch primary)

### Test 8: Assign New Role from Modal

- [ ] Open "Manage Roles" for any user
- [ ] Click "Assign New Role" button
- [ ] Verify the assign role modal opens
- [ ] Verify user is pre-selected
- [ ] Assign a new role
- [ ] Verify it appears in the user's role list

### Test 9: Lecturer Role Cleanup

- [ ] Assign lecturer role to a user
- [ ] Verify record in `lecturers` table
- [ ] Verify record in `user_roles` table
- [ ] Remove lecturer role
- [ ] Verify both records are deleted

### Test 10: Dean/HOD Cleanup

- [ ] Assign Dean role to a user with faculty
- [ ] Check `faculties` table - `dean_user_id` should be set
- [ ] Remove Dean role
- [ ] Check `faculties` table - `dean_user_id` should be NULL
- [ ] Repeat for HOD with departments

## Known Behavior

1. **Primary Role Protection**: Cannot remove the only role from a user
2. **Automatic Primary Switch**: When removing primary role (if other roles exist), system automatically switches to another role
3. **Lecturer Role**: Creates both `Lecturer` record and `UserRole` record for proper tracking
4. **Organizational Cleanup**: Removing Dean/HOD roles clears the faculty/department assignments
5. **Case-Insensitive Filtering**: Role filters work regardless of case or underscores

## Database Tables Involved

- `users` - Primary role stored in `role` column
- `user_roles` - Additional roles with organizational assignments
- `lecturers` - Lecturer-specific data (department assignment)
- `faculties` - Dean assignment (`dean_user_id`)
- `departments` - HOD assignment (`hod_user_id`)

## API Endpoints

- `GET /admin/users/all-staff` - Page view
- `GET /admin/users/all-staff/list` - AJAX: Get all staff data
- `GET /admin/users/search` - AJAX: Search users
- `POST /admin/users/assign-role` - AJAX: Assign role to user
- `GET /admin/users/{user}/roles-detail` - AJAX: Get detailed role info
- `POST /admin/users/{user}/set-primary-role` - AJAX: Set primary role
- `DELETE /admin/users/{user}/role/{role}` - AJAX: Remove role by name
- `DELETE /admin/users/role/{userRole}` - AJAX: Remove role by ID
- `GET /admin/users/api/faculty-departments/{faculty}` - AJAX: Get departments for faculty

## Troubleshooting

### Issue: Lecturer role not showing after assignment

**Solution**: Check both `lecturers` and `user_roles` tables. The `assignRole` method should create both records.

### Issue: Filters not working

**Solution**: Check browser console for JavaScript errors. Verify the role filter values match the database values (lowercase with underscores).

### Issue: 404 on manage roles

**Solution**: Verify routes are registered. Run `php artisan route:list | grep users` to check.

### Issue: Cannot remove role

**Solution**: Check if it's the only role. System prevents removing the last role from a user.

## Next Steps

After testing, consider:

1. Adding bulk role assignment
2. Adding role history/audit log
3. Adding email notifications when roles change
4. Adding permission-based access control for role management
