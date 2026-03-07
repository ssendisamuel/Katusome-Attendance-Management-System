# Multi-Role System Implementation

## Overview

The system now supports multiple roles per user with the ability to switch between roles dynamically. Users can be assigned multiple roles (e.g., a user can be both a Lecturer and HOD) and switch between them without logging out.

## Implementation Approach

Instead of creating duplicate controllers and routes, we **enhanced the existing UserManagementController** to support the new multi-role features. This maintains consistency with the existing codebase and avoids duplication.

## Accessing the Features

### Navigation Menu

The staff management features are accessible through the existing menu structure:

**Users → Staff** submenu:

- `/admin/users/hod` - HODs
- `/admin/users/dean` - Deans
- `/admin/users/qa_director` - QA Staff
- `/admin/users/principal` - Principal
- `/admin/users/registrar` - School Registrar
- `/admin/users/campus_chief` - Campus Chiefs

### Role Switcher

- Located in the navbar user dropdown
- Visible only for users with multiple roles
- Shows all available roles with context
- One-click switching between roles

## Key Features

### 1. Multiple Role Assignment

- Users can have one primary role and multiple additional roles
- Each role can be scoped to specific organizational units (Campus, Faculty, Department)
- Role assignments are tracked in the `user_roles` table

### 2. Dynamic User Search (AJAX)

- Search existing users (lecturers) by name, email
- Filter by Campus, Faculty, or Department
- Real-time search without page reload
- Assign roles to existing users or create new users

### 3. User Creation with Titles

- Title field added (Mr, Ms, Dr, Prof, Associate Prof, etc.)
- Phone number field added
- Automatic password generation
- Welcome email with login credentials

### 4. Welcome Email System

- Modern, responsive email template
- Includes login credentials and instructions
- Automatic delivery upon user creation
- Password reset requirement on first login

### 5. Role Switching

- Users with multiple roles can switch between them
- Role switcher in navbar dropdown
- Automatic redirect to appropriate dashboard
- Session-based role tracking

## Database Schema

### New Tables

#### `user_roles`

- `id` - Primary key
- `user_id` - Foreign key to users
- `role` - Role name (admin, principal, dean, hod, etc.)
- `campus_id` - Optional campus assignment
- `faculty_id` - Optional faculty assignment
- `department_id` - Optional department assignment
- `is_active` - Boolean flag
- Unique constraint on (user_id, role, campus_id, faculty_id, department_id)

### Modified Tables

#### `users`

- Added `title` - User title (Mr, Ms, Dr, Prof, etc.)
- Added `phone` - Phone number
- Added `active_role` - Currently active role for role switching

## API Endpoints

### Staff Management (Enhanced UserManagementController)

- `GET /admin/users/{role}` - Staff management page (hod, dean, qa_director, principal, registrar, campus_chief)
- `GET /admin/users/search` - Search existing users (AJAX)
- `GET /admin/users/by-role/{role}` - Get users by role (AJAX)
- `POST /admin/users/assign-role` - Assign role to existing user (AJAX)
- `POST /admin/users/{role}` - Create new staff user
- `PUT /admin/users/{role}/{user}` - Update staff user
- `DELETE /admin/users/remove-role/{userRole}` - Remove role from user (AJAX)

### Cascading Dropdowns (Existing)

- `GET /admin/users/api/faculty-departments/{faculty}` - Get departments by faculty
- `GET /admin/users/api/department-staff/{department}` - Get staff by department
- `GET /admin/users/api/faculty-staff/{faculty}` - Get staff by faculty

### Role Switching

- `GET /role/available` - Get available roles for current user
- `POST /role/switch` - Switch to different role

## User Model Methods

### New Methods

- `hasRole(string $role): bool` - Check if user has a specific role
- `getAllRoles(): array` - Get all roles for the user
- `getCurrentRole(): string` - Get currently active role
- `switchRole(string $role): bool` - Switch to a different role
- `getAvailableRoles()` - Get available roles with context

### Relationships

- `userRoles()` - All user role assignments
- `activeUserRoles()` - Active role assignments only

## Authorization

### Updated Gates

- `admin` gate now checks for any administrative role (admin, super_admin, principal, registrar, campus_chief, qa_director, dean, hod)
- `lecturer` gate checks if user has lecturer role
- `student` gate checks if user has student role

### Role Hierarchy

1. Super Admin
2. Admin
3. Principal
4. Registrar
5. Campus Chief
6. QA Director
7. Dean
8. HOD (Head of Department)
9. Lecturer
10. Student

## Usage Examples

### Access Staff Management

Navigate to any of these URLs through the menu:

- `/admin/users/hod` - Manage HODs
- `/admin/users/dean` - Manage Deans
- `/admin/users/qa_director` - Manage QA Staff
- `/admin/users/principal` - Manage Principal
- `/admin/users/registrar` - Manage Registrar
- `/admin/users/campus_chief` - Manage Campus Chiefs

### Assign Role to Existing User:

1. Navigate to the appropriate staff page (e.g., `/admin/users/hod`)
2. Click "Add [Role]"
3. Use "Assign Existing User" tab
4. Filter by Campus/Faculty/Department (optional)
5. Search for user by name or email
6. Select user from results
7. Choose organizational assignment (Faculty for Dean, Department for HOD, etc.)
8. Optionally check "Make this the primary role"
9. Click "Assign Role"

### Create New Staff User:

1. Navigate to the appropriate staff page
2. Click "Add [Role]"
3. Use "Create New User" tab
4. Fill in Title, Name, Email, Phone
5. Select organizational assignment if needed
6. Check/uncheck "Send welcome email"
7. Click "Create User"
8. User receives welcome email with credentials (if enabled)

### Switch Roles:

1. Click on your avatar in navbar
2. Look for "Switch Role" section (only visible if you have multiple roles)
3. Click on desired role
4. Automatically redirected to appropriate dashboard

### Programmatic Role Management

```php
// Assign multiple roles
$userRole = UserRole::create([
    'user_id' => $user->id,
    'role' => 'hod',
    'department_id' => $department->id,
    'is_active' => true,
]);

// Check if user has a specific role
if ($user->hasRole('hod')) {
    // User is an HOD
}

// Get all roles
$roles = $user->getAllRoles(); // ['lecturer', 'hod']

// Switch to HOD role
$user->switchRole('hod');

// Get current active role
$currentRole = $user->getCurrentRole();
```

## Frontend Components

### Staff Management Page

- Located at `/admin/users/{role}` (e.g., `/admin/users/hod`, `/admin/users/dean`)
- Tabbed interface for assigning existing users or creating new ones
- Modal-based user creation and assignment
- Dynamic search with filters (Campus, Faculty, Department)
- Real-time user list updates via AJAX
- Integrated with existing navigation menu

### Role Switcher

- Integrated in navbar dropdown
- Shows all available roles with context
- Indicates primary role and current active role
- One-click role switching

## Email Template

- Modern, responsive design
- Gradient header with branding
- Credential box with copy-friendly formatting
- Step-by-step login instructions
- Security warnings and best practices

## Security Features

- Automatic password generation (12 characters)
- Force password change on first login
- Role-based access control
- Session regeneration on role switch
- CSRF protection on all endpoints

## Migration Path

### For Existing Users

1. Existing users retain their primary role in the `role` column
2. Additional roles are added to `user_roles` table
3. No data migration required for basic functionality

### For New Deployments

1. Run migrations: `php artisan migrate`
2. Access staff management through the menu: Users → Staff
3. Create or assign roles to users
4. Users will receive welcome emails automatically

## Configuration

### Email Settings

Ensure SMTP settings are configured in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@attendance.mubs.ac.ug
MAIL_FROM_NAME="${APP_NAME}"
```

### App Settings

```env
APP_NAME="MUBS Attendance System"
APP_URL=https://attendance.mubs.ac.ug
```

## Testing

### Test New User Creation

1. Navigate to `/admin/users/dean`
2. Click "Add Dean"
3. Use "Create New User" tab
4. Fill in all fields (Title: Dr, Name, Email, Phone)
5. Select a faculty
6. Ensure "Send welcome email" is checked
7. Submit form
8. Check email inbox for welcome message

### Test Role Assignment

1. Navigate to `/admin/users/hod`
2. Click "Add HOD"
3. Use "Assign Existing User" tab
4. Search for an existing lecturer
5. Assign HOD role with department
6. Verify user appears in HOD list with "Additional" badge (if not primary role)

### Test Role Switching

1. Login as a user with multiple roles
2. Click on user avatar in navbar
3. Look for "Switch Role" section
4. Click on a different role
5. Verify redirect to appropriate dashboard

### Test Welcome Email

1. Create a new staff user with "Send welcome email" checked
2. Check email inbox for welcome message
3. Verify credentials work for login
4. Confirm password change is required on first login

### Test Dynamic Search

1. Navigate to any staff page (e.g., `/admin/users/hod`)
2. Click "Add HOD" and go to "Assign Existing User" tab
3. Select a faculty from the filter dropdown
4. Type a name in the search box
5. Verify results update in real-time
6. Select a user and verify assignment fields appear

## Troubleshooting

### Role Switcher Not Showing

- Ensure user has multiple roles assigned
- Check `user_roles` table for active roles
- Verify JavaScript is loading correctly

### Welcome Email Not Sending

- Check SMTP configuration in `.env`
- Verify mail logs: `tail -f storage/logs/laravel.log`
- Test email: `php artisan mail:test-send your@email.com`

### Permission Denied After Role Switch

- Clear application cache: `php artisan cache:clear`
- Regenerate config cache: `php artisan config:cache`
- Check gate definitions in `AuthServiceProvider`

### Users Not Loading in Table

- Check browser console for JavaScript errors
- Verify AJAX endpoint is accessible: `/admin/users/by-role/{role}`
- Check network tab for failed requests

## Files Created/Modified

**New Files:**

- `database/migrations/2026_03_07_090232_create_user_roles_table.php` - User roles table
- `database/migrations/2026_03_07_090306_add_title_and_phone_to_users_table.php` - Title and phone fields
- `database/migrations/2026_03_07_090256_add_active_role_to_users_table.php` - Active role tracking
- `app/Models/UserRole.php` - UserRole model
- `app/Http/Controllers/RoleSwitchController.php` - Role switching logic
- `app/Mail/WelcomeStaffMail.php` - Welcome email mailable
- `resources/views/emails/welcome-staff.blade.php` - Modern email template
- `MULTI_ROLE_SYSTEM.md` - This documentation

**Enhanced Files:**

- `app/Models/User.php` - Added multi-role methods and relationships
- `app/Http/Controllers/Admin/UserManagementController.php` - Enhanced with AJAX endpoints and multi-role support
- `resources/views/admin/users/index.blade.php` - Completely redesigned with dynamic search and multi-role UI
- `app/Providers/AuthServiceProvider.php` - Updated gates for multi-role support
- `app/Http/Controllers/AuthController.php` - Updated login redirects
- `routes/web.php` - Added AJAX endpoints for user search and role management
- `resources/views/layouts/sections/navbar/navbar-partial.blade.php` - Added role switcher

## Future Enhancements

- Role-based permissions (granular access control)
- Role expiration dates
- Role approval workflow
- Audit log for role changes
- Bulk role assignment
- Role templates for common combinations
