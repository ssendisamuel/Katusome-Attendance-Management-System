<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth; // replaced with auth() helper
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'password' => ['required', 'string', 'min:8'],
            // Student-specific fields
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no'],
            'reg_no' => ['required', 'string', 'max:50', 'unique:students,reg_no'],  // REQUIRED
            'program_id' => ['nullable', 'exists:programs,id'],  // Optional - set during enrollment
            'group_id' => ['nullable', 'exists:groups,id'],      // Optional - set during enrollment
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'email.regex' => 'Email must be a mubs.ac.ug address.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
        ]);

        // Create student linked to canonical user
        Student::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_no' => $data['student_no'],
            'reg_no' => $data['reg_no'],  // Required
            'program_id' => $data['program_id'] ?? null,  // Optional - set during enrollment
            'group_id' => $data['group_id'] ?? null,      // Optional - set during enrollment
            'year_of_study' => $data['year_of_study'] ?? null,
        ]);

        auth()->login($user);
        $request->session()->regenerate();

        // Default new users are students; redirect accordingly
        return redirect()->intended(route('student.dashboard'));
    }
}
