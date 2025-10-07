<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = (bool) $request->boolean('remember', false);
        // Attempt login with provided credentials
        if (auth()->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = auth()->user();
            // Role-based intended redirect
            if ($user && $user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }
            if ($user && $user->role === 'lecturer') {
                return redirect()->intended(route('lecturer.attendance.index'));
            }
            if ($user && $user->role === 'student') {
                return redirect()->intended(route('student.dashboard'));
            }

            return redirect()->intended('/');
        }
        // Provide clearer error feedback: distinguish unknown email and wrong password
        $userExists = \App\Models\User::where('email', $request->input('email'))->exists();
        $errorMessage = $userExists
            ? 'Incorrect password. Please try again.'
            : 'No account found with that email.';

        // Attach error to the relevant field key for Blade error display
        $errorKey = $userExists ? 'password' : 'email';

        return back()->withErrors([
            $errorKey => $errorMessage,
        ])->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}