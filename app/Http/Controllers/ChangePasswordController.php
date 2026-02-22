<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ChangePasswordController extends Controller
{
    public function edit()
    {
        return view('content.account.change-password');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ]);

        $user = auth()->user();

        // 1. Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // 2. Refresh sessions (this uses the NEW password hash)
        // Note: This updates the remember token but shouldn't overwrite other fields on $user unless specifically re-fetched
        auth()->logoutOtherDevices($validated['password']);

        // 3. Update force-change flag and save AGAIN to ensure it persists
        // We use fresh() to ensure we have the latest state before saving this flag,
        // to avoid any race condition or overwrite from logoutOtherDevices
        $user = $user->fresh();
        $user->must_change_password = false;
        $user->save();

        $role = strtolower($user->role);

        // Redirect based on role
        if ($role === 'student') {
            return redirect()->route('student.dashboard')->with('success', 'Password changed successfully.');
        }
        if ($role === 'lecturer') {
            return redirect()->route('lecturer.dashboard')->with('success', 'Password changed successfully.');
        }
        if ($role === 'admin') {
            return redirect()->route('admin.dashboard')->with('success', 'Password changed successfully.');
        }

        // Fallback
        return redirect('/')->with('success', 'Password changed successfully.');
    }
}
