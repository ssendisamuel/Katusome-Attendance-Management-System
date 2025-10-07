<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
{
    public function request()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.passwords.email', ['pageConfigs' => $pageConfigs]);
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required','email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function reset(string $token)
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.passwords.reset', ['token' => $token, 'pageConfigs' => $pageConfigs]);
    }

    public function update(Request $request)
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required','email'],
            'password' => ['required', Rules\Password::defaults(), 'confirmed'],
        ]);

        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ])->save();

                auth()->logoutOtherDevices($password);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
