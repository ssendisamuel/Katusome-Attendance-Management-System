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

        $users = $query->orderBy('name')->get();
        $viewData = compact('users', 'role');

        // Add extra data for specific roles
        if ($role === 'dean') {
            $viewData['faculties'] = Faculty::where('is_active', true)->orderBy('name')->get();
        } elseif ($role === 'hod') {
            $viewData['departments'] = Department::where('is_active', true)->with('faculty')->orderBy('name')->get();
        } elseif ($role === 'campus_chief') {
            $viewData['campuses'] = Campus::where('is_active', true)->orderBy('name')->get();
        }

        return view('admin.users.index', $viewData);
    }

    public function store(Request $request, string $role)
    {
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

        $user->delete();

        return redirect()->route('admin.users.role', $role)
            ->with('success', ucfirst(str_replace('_', ' ', $role)) . ' deleted successfully.');
    }
}
