<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Ensure user is a student
        if ($user->role !== 'student') {
             return response()->json([
                'message' => 'Only students can login to the mobile app.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Check for enrollment requirement
        $needsEnrollment = false;
        if ($user->role === 'student' && $user->student) {
            $activeSemester = \App\Models\AcademicSemester::where('is_active', true)->first();
            if ($activeSemester) {
                $hasEnrollment = \App\Models\StudentEnrollment::where('student_id', $user->student->id)
                    ->where('academic_semester_id', $activeSemester->id)
                    ->exists();

                if (!$hasEnrollment) {
                    $needsEnrollment = true;
                }
            }
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('student'),
            'needs_enrollment' => $needsEnrollment,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'password' => ['required', 'string', 'min:8'],
            // Student-specific fields
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no'],
            'reg_no' => ['required', 'string', 'max:50', 'unique:students,reg_no'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
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

        \App\Models\Student::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_no' => $data['student_no'],
            'reg_no' => $data['reg_no'],
            'program_id' => $data['program_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
            'year_of_study' => $data['year_of_study'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('student'),
        ], 201);
    }

    /**
     * Google Sign-In for mobile app
     * Verifies Google ID token and creates/authenticates user
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            // Verify Google ID token using Google API
            $client = new \Google\Client();
            $client->setClientId(config('services.google.client_id'));

            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json(['message' => 'Invalid Google token'], 401);
            }

            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;
            $avatar = $payload['picture'] ?? null;

            if (!$email) {
                return response()->json(['message' => 'Email not provided by Google'], 400);
            }

            // Validate MUBS domain
            $domain = substr(strrchr($email, '@'), 1);
            $allowedDomain = config('app.allowed_google_domain', 'mubs.ac.ug');
            if ($allowedDomain && strtolower($domain) !== strtolower($allowedDomain)) {
                return response()->json([
                    'message' => 'Please use your MUBS Google account (@mubs.ac.ug)'
                ], 403);
            }

            // Find or create user
            $user = User::where('email', $email)->first();
            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(str()->random(16)),
                    'role' => 'student',
                    'avatar_url' => $avatar,
                ]);
                $isNewUser = true;
            } else {
                // Update profile info
                $user->update([
                    'name' => $name ?? $user->name,
                    'avatar_url' => $avatar ?? $user->avatar_url,
                ]);
            }

            // Ensure user is a student
            if ($user->role !== 'student') {
                return response()->json([
                    'message' => 'Only students can login to the mobile app.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Check if student profile exists
            $needsProfileCompletion = !$user->student;

            // Check for semester enrollment
            $needsEnrollment = false;
            if ($user->student) {
                $activeSemester = \App\Models\AcademicSemester::where('is_active', true)->first();
                if ($activeSemester) {
                    $hasEnrollment = \App\Models\StudentEnrollment::where('student_id', $user->student->id)
                        ->where('academic_semester_id', $activeSemester->id)
                        ->exists();
                    if (!$hasEnrollment) {
                        $needsEnrollment = true;
                    }
                }
            }

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('student'),
                'needs_profile_completion' => $needsProfileCompletion,
                'needs_enrollment' => $needsEnrollment || $needsProfileCompletion,
                'is_new_user' => $isNewUser,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
