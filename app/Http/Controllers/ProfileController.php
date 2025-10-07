<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Lecturer;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $student = $user && $user->role === 'student' ? $user->student : null;
        $lecturer = $user && $user->role === 'lecturer' ? $user->lecturer : null;

        return view('content.account.profile', compact('user', 'student', 'lecturer'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $context = $request->input('context');

        if ($context === 'avatar') {
            // Avatar-only update
            $validated = $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->forceFill([
                'avatar_url' => asset('storage/' . $path),
            ])->save();
        } else {
            // Profile info update (name/email are required here)
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'phone' => ['nullable', 'string', 'max:25'],
                'gender' => ['nullable', 'in:male,female,other'],
                'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ])->save();

            // Optional avatar update when saving profile info
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->forceFill([
                    'avatar_url' => asset('storage/' . $path),
                ])->save();
            }

            if ($user->role === 'student') {
                $user->student?->update([
                    'phone' => $validated['phone'] ?? $user->student?->phone,
                    'gender' => $validated['gender'] ?? $user->student?->gender,
                ]);
            }

            if ($user->role === 'lecturer') {
                $user->lecturer?->update([
                    'phone' => $validated['phone'] ?? $user->lecturer?->phone,
                ]);
            }
        }

        return back()->with('success', 'Profile updated successfully');
    }
}