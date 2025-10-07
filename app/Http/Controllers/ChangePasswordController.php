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
            'password' => ['required', Rules\Password::defaults(), 'confirmed'],
        ]);

        $user = auth()->user();
        $user->password = Hash::make($validated['password']);
        // Clear force-change flag after successful password update
        $user->must_change_password = false;
        $user->save();

        // Optionally log out other devices for security
        auth()->logoutOtherDevices($validated['password']);

        return back()->with('success', 'Your password has been updated.');
    }
}