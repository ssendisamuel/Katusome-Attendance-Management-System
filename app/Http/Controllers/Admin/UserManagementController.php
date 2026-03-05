<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
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

        $viewData['campuses'] = Campus::where('is_active', true)->orderBy('name')->get();
        if ($role === 'dean' || $role === 'hod') {
            $viewData['faculties'] = Faculty::where('is_active', true)->orderBy('name')->get();
        }
        if ($role === 'hod') {
            $viewData['departments'] = Department::where('is_active', true)->with('faculty')->orderBy('name')->get();
        }

        return view('admin.users.index', $viewData);
    }

    public function store(Request $request, string $role)
    {
        if (($role === 'hod' || $role === 'dean') && $request->filled('user_id')) {
            // Assign existing user
            $user = User::findOrFail($request->integer('user_id'));

            // Note: In an ideal system we might update the role or allow multiple roles,
            // but for this system we will update the role to hod/dean to ensure they show up in the list
            $user->update(['role' => $role]);

            if ($role === 'dean' && $request->filled('faculty_id')) {
                Faculty::where('dean_user_id', $user->id)->update(['dean_user_id' => null]);
                Faculty::where('id', $request->integer('faculty_id'))
                    ->update(['dean_user_id' => $user->id]);
            } elseif ($role === 'hod' && $request->filled('department_id')) {
                Department::where('hod_user_id', $user->id)->update(['hod_user_id' => null]);
                Department::where('id', $request->integer('department_id'))
                    ->update(['hod_user_id' => $user->id]);
            }

            return redirect()->route('admin.users.role', $role)
                ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' assigned successfully.');
        }

        // Original creation logic
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'password123'),
            'role' => $role,
        ]);

        // Role-specific assignment
        if ($role === 'dean' && $request->filled('faculty_id')) {
            Faculty::where('id', $request->integer('faculty_id'))
                ->update(['dean_user_id' => $user->id]);
        } elseif ($role === 'hod' && $request->filled('department_id')) {
            Department::where('id', $request->integer('department_id'))
                ->update(['hod_user_id' => $user->id]);
        } elseif ($role === 'lecturer') {
            Lecturer::create([
                'user_id' => $user->id,
                'department_id' => $request->input('department_id'),
                'title' => $request->input('title'),
                'specialization' => $request->input('specialization'),
            ]);
        }

        return redirect()->route('admin.users.role', $role)
            ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' created successfully.');
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
}
