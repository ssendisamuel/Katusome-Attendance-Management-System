<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Student;
use App\Models\Program;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $googleUser = Socialite::driver('google')->user();

        $email = $googleUser->getEmail();
        $name = $googleUser->getName();
        $avatar = $googleUser->getAvatar();

        // Enforce MUBS domain for students; allow admins/lecturers existing
        $domain = substr(strrchr($email, '@'), 1);
        $allowedDomain = config('app.allowed_google_domain', 'mubs.ac.ug');
        if ($allowedDomain && strtolower($domain) !== strtolower($allowedDomain)) {
            return redirect()->route('login')->with('error', 'Please use your MUBS Google account');
        }

        // Find or create user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(str()->random(16)),
                'role' => 'student',
                'avatar_url' => $avatar,
            ]);
        } else {
            // Update basic profile info
            $user->update([
                'name' => $name ?? $user->name,
                'avatar_url' => $avatar ?? $user->avatar_url,
            ]);
        }

        // If student profile exists, log in and go to dashboard
        $student = Student::where('user_id', $user->id)->first();
        if ($student) {
            Auth::login($user);
            return redirect()->route('student.dashboard');
        }

        // Otherwise, prompt to complete profile
        $programs = Program::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        // Store google data in session temporarily
        session([
            'oauth_user_id' => $user->id,
        ]);
        return view('auth.oauth-complete-profile', compact('user', 'programs', 'groups'));
    }

    public function completeProfile(Request $request)
    {
        if ($request->isMethod('get')) {
            $userId = session('oauth_user_id');
            $user = User::findOrFail($userId);
            $programs = Program::orderBy('name')->get();
            $groups = Group::orderBy('name')->get();
            return view('auth.oauth-complete-profile', compact('user', 'programs', 'groups'));
        }

        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'student_no' => ['required', 'string', 'max:50'],
            'reg_no' => ['nullable', 'string', 'max:50'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $userId = session('oauth_user_id');
        if (!$userId) {
            return redirect()->route('login')->with('error', 'Session expired, please sign in again');
        }

        $user = User::findOrFail($userId);

        DB::transaction(function () use ($user, $data) {
            Student::create([
                'user_id' => $user->id,
                'student_no' => $data['student_no'],
                'reg_no' => $data['reg_no'] ?? null,
                'program_id' => $data['program_id'],
                'group_id' => $data['group_id'],
                'year_of_study' => $data['year_of_study'] ?? 1,
            ]);
        });

        // Clear session placeholder and log in
        session()->forget('oauth_user_id');
        Auth::login($user);
        return redirect()->route('student.dashboard')->with('success', 'Profile completed');
    }
}