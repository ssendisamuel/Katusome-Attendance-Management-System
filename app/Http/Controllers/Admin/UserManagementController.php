<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeStaffMail;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    // Staff roles that can be managed
    const STAFF_ROLES = [
        'admin',
        'super_admin',
        'principal',
        'registrar',
        'campus_chief',
        'qa_director',
        'dean',
        'hod',
    ];

    /**
     * Get detailed role information for a user
     */
    public function getUserRolesDetail(User $user)
    {
        $roles = [];

        // Add primary role
        $primaryAssignment = '';
        if ($user->role === 'dean' && $user->deanOfFaculty) {
            $primaryAssignment = 'Dean of ' . $user->deanOfFaculty->name;
        } elseif ($user->role === 'hod' && $user->hodOfDepartment) {
            $primaryAssignment = 'HOD of ' . $user->hodOfDepartment->name;
        } elseif ($user->role === 'lecturer' && $user->lecturer && $user->lecturer->department) {
            $primaryAssignment = 'Lecturer - ' . $user->lecturer->department->name;
        }

        $roles[] = [
            'role' => $user->role,
            'role_display' => ucfirst(str_replace('_', ' ', $user->role)),
            'is_primary' => true,
            'user_role_id' => null,
            'assignment' => $primaryAssignment,
        ];

        // Add additional roles from user_roles table (skip if same as primary role to avoid duplicates)
        $userRoles = UserRole::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('role', '!=', $user->role) // Skip primary role to avoid duplicates
            ->with(['campus', 'faculty', 'department'])
            ->get();

        foreach ($userRoles as $ur) {
            $assignment = '';
            if ($ur->campus) {
                $assignment = $ur->campus->name;
            } elseif ($ur->faculty) {
                $assignment = $ur->faculty->name;
            } elseif ($ur->department) {
                $assignment = $ur->department->name;
            }

            $roles[] = [
                'role' => $ur->role,
                'role_display' => ucfirst(str_replace('_', ' ', $ur->role)),
                'is_primary' => false,
                'user_role_id' => $ur->id,
                'assignment' => $assignment,
            ];
        }

        return response()->json(['roles' => $roles]);
    }

    /**
     * Set a role as the primary role for a user
     */
    public function setPrimaryRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $user->update(['role' => $validated['role']]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Primary role updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update primary role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a role from a user by role name
     */
    public function removeRoleByName(User $user, string $role)
    {
        DB::beginTransaction();
        try {
            // Don't allow removing the primary role if it's the only role
            if ($user->role === $role) {
                $hasOtherRoles = UserRole::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where('role', '!=', $role) // Exclude the role we're trying to remove
                    ->exists();

                if (!$hasOtherRoles) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot remove the only role from a user.',
                    ], 400);
                }

                // Switch primary role to another role
                $newPrimaryRole = UserRole::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->where('role', '!=', $role)
                    ->first();

                if ($newPrimaryRole) {
                    $user->update(['role' => $newPrimaryRole->role]);
                }
            }

            // Remove from user_roles table
            UserRole::where('user_id', $user->id)
                ->where('role', $role)
                ->delete();

            // Refresh user to get updated role
            $user->refresh();

            // If lecturer role was removed, check if user still has lecturer role anywhere
            if ($role === 'lecturer') {
                $stillHasLecturerRole = $user->role === 'lecturer' ||
                    UserRole::where('user_id', $user->id)
                        ->where('role', 'lecturer')
                        ->where('is_active', true)
                        ->exists();

                // Only delete lecturer record if user no longer has lecturer role
                if (!$stillHasLecturerRole) {
                    Lecturer::where('user_id', $user->id)->delete();
                }
            }

            // Clear organizational relationships
            if ($role === 'dean') {
                Faculty::where('dean_user_id', $user->id)->update(['dean_user_id' => null]);
            } elseif ($role === 'hod') {
                Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show all staff and administrators page
     */
    public function allStaff()
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->with('faculty')->orderBy('name')->get();

        return view('admin.users.all-staff', compact('campuses', 'faculties', 'departments'));
    }

    /**
     * Get all staff and administrators - AJAX endpoint
     */
    public function getAllStaffList()
    {
        // Include lecturers in addition to staff roles
        $users = User::where(function ($query) {
            $query->whereIn('role', array_merge(self::STAFF_ROLES, ['lecturer']))
                ->orWhereHas('userRoles', function ($q) {
                    $q->whereIn('role', array_merge(self::STAFF_ROLES, ['lecturer']))->where('is_active', true);
                });
        })
            ->with(['userRoles' => function ($q) {
                $q->where('is_active', true)->with(['campus', 'faculty', 'department']);
            }, 'deanOfFaculty', 'hodOfDepartment.faculty', 'lecturer.department.faculty'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                // Collect all roles (including from user_roles table)
                $allRoles = collect([$user->role]);
                $user->userRoles->each(function ($ur) use ($allRoles) {
                    $allRoles->push($ur->role);
                });
                $allRoles = $allRoles->unique()->map(function ($role) {
                    return ucfirst(str_replace('_', ' ', $role));
                })->values()->toArray();

                // Collect assignments
                $assignments = [];
                $campusIds = [];
                $facultyIds = [];
                $departmentIds = [];

                if ($user->deanOfFaculty) {
                    $assignments[] = 'Dean of ' . $user->deanOfFaculty->name;
                    $facultyIds[] = $user->deanOfFaculty->id;
                }

                if ($user->hodOfDepartment) {
                    $assignments[] = 'HOD of ' . $user->hodOfDepartment->name;
                    $departmentIds[] = $user->hodOfDepartment->id;
                    if ($user->hodOfDepartment->faculty) {
                        $facultyIds[] = $user->hodOfDepartment->faculty->id;
                    }
                }

                // Process user_roles assignments
                $processedLecturerDept = false; // Track if we've already added lecturer assignment
                $user->userRoles->each(function ($ur) use (&$assignments, &$campusIds, &$facultyIds, &$departmentIds, &$processedLecturerDept) {
                    $roleName = ucfirst(str_replace('_', ' ', $ur->role));

                    if ($ur->campus) {
                        $assignments[] = $roleName . ' - ' . $ur->campus->name;
                        $campusIds[] = $ur->campus->id;
                    }
                    if ($ur->faculty) {
                        $assignments[] = $roleName . ' - ' . $ur->faculty->name;
                        $facultyIds[] = $ur->faculty->id;
                    }
                    if ($ur->department) {
                        $assignments[] = $roleName . ' - ' . $ur->department->name;
                        $departmentIds[] = $ur->department->id;
                        if ($ur->department->faculty) {
                            $facultyIds[] = $ur->department->faculty->id;
                        }
                        // Mark that we've processed lecturer department
                        if ($ur->role === 'lecturer') {
                            $processedLecturerDept = true;
                        }
                    }
                });

                // Check if user has lecturer role (either as primary or in user_roles)
                $hasLecturerRole = $user->role === 'lecturer' ||
                    $user->userRoles->contains('role', 'lecturer');

                // Add lecturer department ONLY if user has lecturer role AND not already added from user_roles
                if ($hasLecturerRole && !$processedLecturerDept && $user->lecturer && $user->lecturer->department) {
                    $assignments[] = 'Lecturer - ' . $user->lecturer->department->name;
                    $departmentIds[] = $user->lecturer->department->id;
                    if ($user->lecturer->department->faculty) {
                        $facultyIds[] = $user->lecturer->department->faculty->id;
                    }
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'title' => $user->title,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'primary_role' => ucfirst(str_replace('_', ' ', $user->role)),
                    'primary_role_raw' => $user->role,
                    'all_roles' => $allRoles,
                    'all_roles_raw' => $allRoles, // For filtering
                    'assignments' => array_unique($assignments),
                    'campus_ids' => array_values(array_unique($campusIds)),
                    'faculty_ids' => array_values(array_unique($facultyIds)),
                    'department_ids' => array_values(array_unique($departmentIds)),
                ];
            });

        return response()->json($users);
    }

    /**
     * Generic user listing filtered by role.
     */
    public function index(Request $request, string $role)
    {
        $query = User::where('role', $role);

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        if ($role === 'hod' || $role === 'dean') {
            if ($request->filled('campus_id')) {
                $campusId = $request->integer('campus_id');
                $query->whereHas($role === 'dean' ? 'deanOfFaculty' : 'hodOfDepartment.faculty', function ($q) use ($campusId) {
                    $q->whereHas('campuses', function ($q2) use ($campusId) {
                        $q2->where('campuses.id', $campusId);
                    });
                });
            }

            if ($request->filled('faculty_id')) {
                $facultyId = $request->integer('faculty_id');
                if ($role === 'dean') {
                    $query->whereHas('deanOfFaculty', function ($q) use ($facultyId) {
                        $q->where('id', $facultyId);
                    });
                } else {
                    $query->whereHas('hodOfDepartment', function ($q) use ($facultyId) {
                        $q->where('faculty_id', $facultyId);
                    });
                }
            }

            if ($role === 'hod' && $request->filled('department_id')) {
                $query->whereHas('hodOfDepartment', function ($q) use ($request) {
                    $q->where('id', $request->integer('department_id'));
                });
            }
        }

        $users = $query->orderBy('name')->get();
        $viewData = compact('users', 'role');

        // Always provide campuses, faculties, and departments for the search filters
        $viewData['campuses'] = Campus::where('is_active', true)->orderBy('name')->get();
        $viewData['faculties'] = Faculty::where('is_active', true)->orderBy('name')->get();
        $viewData['departments'] = Department::where('is_active', true)->with('faculty')->orderBy('name')->get();

        return view('admin.users.index', $viewData);
    }

    /**
     * Search existing users (lecturers) for assignment - AJAX endpoint
     */
    public function searchUsers(Request $request)
    {
        $query = User::query();

        // Search term
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        // Filter by campus
        if ($request->filled('campus_id')) {
            $campusId = $request->integer('campus_id');
            $query->whereHas('lecturer.department.faculty.campuses', function ($q) use ($campusId) {
                $q->where('campuses.id', $campusId);
            });
        }

        // Filter by faculty
        if ($request->filled('faculty_id')) {
            $facultyId = $request->integer('faculty_id');
            $query->whereHas('lecturer.department', function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId);
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $departmentId = $request->integer('department_id');
            $query->whereHas('lecturer', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Only show users with lecturer records or existing staff
        $query->where(function ($q) {
            $q->whereHas('lecturer')
              ->orWhereIn('role', self::STAFF_ROLES);
        });

        $users = $query->with(['lecturer.department.faculty'])
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'title' => $user->title,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'department' => $user->lecturer?->department?->name,
                    'faculty' => $user->lecturer?->department?->faculty?->name,
                    'current_role' => $user->role,
                ];
            });

        return response()->json($users);
    }

    /**
     * Get users with a specific role - AJAX endpoint
     */
    public function getUsersByRole(Request $request, string $role)
    {
        if (!in_array($role, self::STAFF_ROLES)) {
            abort(404);
        }

        // Get users with this role (either as primary role or in user_roles table)
        $users = User::where('role', $role)
            ->orWhereHas('userRoles', function ($q) use ($role) {
                $q->where('role', $role)->where('is_active', true);
            })
            ->with(['userRoles' => function ($q) use ($role) {
                $q->where('role', $role)->with(['campus', 'faculty', 'department']);
            }, 'deanOfFaculty', 'hodOfDepartment.faculty'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) use ($role) {
                // Try to get from user_roles table first
                $userRole = $user->userRoles->where('role', $role)->first();

                // Determine assignment based on role
                $assignment = '';
                if ($role === 'dean' && $user->deanOfFaculty) {
                    $assignment = $user->deanOfFaculty->name;
                } elseif ($role === 'hod' && $user->hodOfDepartment) {
                    $assignment = $user->hodOfDepartment->name;
                } elseif ($userRole) {
                    if ($userRole->department) {
                        $assignment = $userRole->department->name;
                    } elseif ($userRole->faculty) {
                        $assignment = $userRole->faculty->name;
                    } elseif ($userRole->campus) {
                        $assignment = $userRole->campus->name;
                    }
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'title' => $user->title,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role,
                    'campus' => $userRole?->campus?->name,
                    'faculty' => $userRole?->faculty?->name ?? ($role === 'dean' ? $user->deanOfFaculty?->name : null),
                    'department' => $userRole?->department?->name ?? ($role === 'hod' ? $user->hodOfDepartment?->name : null),
                    'assignment' => $assignment,
                    'is_primary_role' => $user->role === $role,
                    'user_role_id' => $userRole?->id,
                    'user_id' => $user->id, // For deletion if no user_role_id
                ];
            });

        return response()->json($users);
    }

    /**
     * Assign role to existing user - AJAX endpoint
     */
    public function assignRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', Rule::in(array_merge(self::STAFF_ROLES, ['lecturer']))],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'make_primary' => ['boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        DB::beginTransaction();
        try {
            // Special handling for lecturer role
            if ($validated['role'] === 'lecturer') {
                if (!isset($validated['department_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Department is required for lecturer role.',
                    ], 400);
                }

                // Create or update lecturer record
                Lecturer::updateOrCreate(
                    ['user_id' => $user->id],
                    ['department_id' => $validated['department_id']]
                );

                // Also create a user_role record for tracking
                UserRole::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'role' => 'lecturer',
                        'department_id' => $validated['department_id'],
                    ],
                    [
                        'is_active' => true,
                        'faculty_id' => $validated['faculty_id'] ?? null,
                    ]
                );

                // Update user role if making primary
                if ($request->boolean('make_primary')) {
                    $user->update(['role' => 'lecturer']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Lecturer role assigned successfully.',
                ]);
            }

            // For other staff roles, create user role record
            $userRole = UserRole::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'campus_id' => $validated['campus_id'] ?? null,
                    'faculty_id' => $validated['faculty_id'] ?? null,
                    'department_id' => $validated['department_id'] ?? null,
                ],
                [
                    'is_active' => true,
                ]
            );

            // Update primary role if requested
            if ($request->boolean('make_primary')) {
                $user->update(['role' => $validated['role']]);
            }

            // Update organizational relationships
            $this->updateOrganizationalRelationships($user, $validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove role from user - AJAX endpoint
     */
    public function removeRole(Request $request, UserRole $userRole)
    {
        DB::beginTransaction();
        try {
            $user = $userRole->user;
            $role = $userRole->role;

            // Don't allow removing the primary role if it's the only role
            if ($user->role === $role && $user->userRoles()->where('role', '!=', $role)->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the only role from a user.',
                ], 400);
            }

            $userRole->delete();

            // If this was the primary role, switch to another role
            if ($user->role === $role) {
                $newPrimaryRole = $user->userRoles()->first();
                if ($newPrimaryRole) {
                    $user->update(['role' => $newPrimaryRole->role]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove role: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, string $role)
    {
        // Handle role assignment to existing user (AJAX) - check this FIRST before any validation
        if ($request->filled('user_id')) {
            return $this->assignRole($request);
        }

        // Original creation logic - enhanced with new fields
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'send_welcome_email' => ['boolean'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
        ]);

        DB::beginTransaction();
        try {
            // Generate password if not provided
            $password = $validated['password'] ?? Str::random(12);
            $sendEmail = $request->boolean('send_welcome_email', true);

            $user = User::create([
                'title' => $validated['title'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($password),
                'role' => $role,
                'must_change_password' => true,
            ]);

            // Create user role record
            UserRole::create([
                'user_id' => $user->id,
                'role' => $role,
                'campus_id' => $validated['campus_id'] ?? null,
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'is_active' => true,
            ]);

            // Update organizational relationships
            $this->updateOrganizationalRelationships($user, $validated);

            // Send welcome email if requested
            if ($sendEmail) {
                $this->sendWelcomeEmail($user, $password);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => ucfirst(str_replace('_', ' ', $role)) . ' created successfully.',
                    'password' => !$sendEmail ? $password : null,
                ]);
            }

            return redirect()->route('admin.users.role', $role)
                ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, string $role, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        // Update password if provided
        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Role-specific assignment
        if ($role === 'dean' && $request->filled('faculty_id')) {
            // Remove old assignment
            Faculty::where('dean_user_id', $user->id)->update(['dean_user_id' => null]);
            Faculty::where('id', $request->integer('faculty_id'))
                ->update(['dean_user_id' => $user->id]);
        } elseif ($role === 'hod' && $request->filled('department_id')) {
            Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);
            Department::where('id', $request->integer('department_id'))
                ->update(['hod_user_id' => $user->id]);
        }

        return redirect()->route('admin.users.role', $role)
            ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' updated successfully.');
    }

    public function destroy(string $role, User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.role', $role)
                ->with('error', 'You cannot delete your own account.');
        }

        // Clear role-specific assignments
        Faculty::where('dean_user_id', $user->id)->update(['dean_user_id' => null]);
        Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);

        // Revert role to lecturer if they were assigned from a lecturer? Or just delete.
        // If we are assigning staff, we probably don't want to completely delete them.
        // The original code `delete()`s the user. For safety, let's keep it but perhaps we should just update role.
        // For now keep original behavior.
        $user->delete();

        return redirect()->route('admin.users.role', $role)
            ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' deleted successfully.');
    }

    // --- API Methods for Cascading Dropdowns ---

    public function getFacultyDepartments(Faculty $faculty)
    {
        $departments = $faculty->departments()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        return response()->json($departments);
    }

    public function getDepartmentStaff(Department $department)
    {
        $staff = User::whereHas('lecturer', function ($q) use ($department) {
            $q->where('department_id', $department->id);
        })->orderBy('name')->get(['id', 'name', 'email']);
        return response()->json($staff);
    }

    public function getFacultyStaff(Faculty $faculty)
    {
        $staff = User::whereHas('lecturer.department', function ($q) use ($faculty) {
            $q->where('faculty_id', $faculty->id);
        })->orderBy('name')->get(['id', 'name', 'email']);
        return response()->json($staff);
    }

    /**
     * Update organizational relationships (dean, hod, etc.)
     */
    private function updateOrganizationalRelationships(User $user, array $data)
    {
        $role = $data['role'] ?? $user->role;

        // Update dean assignment
        if ($role === 'dean' && isset($data['faculty_id'])) {
            Faculty::where('dean_user_id', $user->id)->update(['dean_user_id' => null]);
            Faculty::where('id', $data['faculty_id'])->update(['dean_user_id' => $user->id]);
        }

        // Update HOD assignment
        if ($role === 'hod' && isset($data['department_id'])) {
            Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);
            Department::where('id', $data['department_id'])->update(['hod_user_id' => $user->id]);
        }
    }

    /**
     * Send welcome email to new staff user
     */
    private function sendWelcomeEmail(User $user, string $password)
    {
        try {
            $loginUrl = route('login');
            Mail::to($user->email)->send(new WelcomeStaffMail($user, $password, $loginUrl));
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    }
}
