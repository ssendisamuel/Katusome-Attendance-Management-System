<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = User::where('role', 'admin')->orderBy('name')->paginate(20);
        return view('admin.admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.admins.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $admin)
    {
        if ($admin->role !== 'admin') {
            abort(404);
        }
        return view('admin.admins.edit', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $admin)
    {
        if ($admin->role !== 'admin') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];
        if ($request->filled('password')) {
            $admin->password = Hash::make($validated['password']);
        }
        $admin->save();

        return redirect()->route('admin.admins.index')->with('success', 'Admin user updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $admin)
    {
        if ($admin->role !== 'admin') {
            abort(404);
        }

        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // Optional: Check if this is the last admin to prevent lockout?
        // simple count check
        if (User::where('role', 'admin')->count() <= 1) {
             return back()->with('error', 'Cannot delete the last admin user.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
