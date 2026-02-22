<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Student record not found associated with this user.'], 404);
        }

        $enrollment = $student->currentEnrollment();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'student_no' => $student->student_no,
                'reg_no' => $student->reg_no,
                'program_id' => $student->program_id,
                'group_id' => $student->group_id,
                'year_of_study' => $student->year_of_study,
            ],
            'enrollment' => $enrollment ? [
                'program_id' => $enrollment->program_id,
                'group_id' => $enrollment->group_id,
                'program' => $enrollment->program->name,
                'program_code' => $enrollment->program->code,
                'year' => $enrollment->year_of_study,
                'semester' => $enrollment->academicSemester->name,
                'group' => $enrollment->group->name ?? 'N/A',
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($request->user()->id),
            ],
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Phone is often on the student record in some systems, but here we only have user email/name in basic profile.
        // If student table has phone, update it here. Assuming user table for simplicity or extending as needed.

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match.'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function updateEnrollment(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        // If student doesn't exist yet (e.g. new Google Sign-In user), require student_no/reg_no
        $rules = [
            'program_id' => 'required|exists:programs,id',
            'group_id' => 'required|exists:groups,id',
            'year_of_study' => 'required|integer|min:1|max:5',
        ];

        if (!$student) {
            $rules['student_no'] = 'required|string|max:50|unique:students,student_no';
            $rules['reg_no'] = 'nullable|string|max:50|unique:students,reg_no';
        }

        $request->validate($rules);

        $activeSemester = \App\Models\AcademicSemester::where('is_active', true)->first();

        if (!$activeSemester) {
            return response()->json(['message' => 'No active semester for enrollment.'], 400);
        }

        // Create student if doesn't exist (for new Google Sign-In users)
        if (!$student) {
            $student = \App\Models\Student::create([
                'user_id' => $user->id,
                'student_no' => $request->student_no,
                'reg_no' => $request->reg_no ?? null,
                'program_id' => $request->program_id,
                'group_id' => $request->group_id,
                'year_of_study' => $request->year_of_study,
            ]);
        } else {
            // Update existing student record
            $student->update([
                'program_id' => $request->program_id,
                'group_id' => $request->group_id,
                'year_of_study' => $request->year_of_study,
            ]);
        }

        // Create/Update Enrollment Record for Active Semester
        \App\Models\StudentEnrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_semester_id' => $activeSemester->id,
            ],
            [
                'program_id' => $request->program_id,
                'group_id' => $request->group_id,
                'year_of_study' => $request->year_of_study,
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]
        );

        return response()->json(['message' => 'Enrollment updated successfully.']);
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048', // Max 2MB
        ]);

        $user = $request->user();

        if ($request->hasFile('photo')) {
            // Setup a unique filename
            $filename = time() . '_' . $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('profile-photos', $filename, 'public');

            // Generate full URL or relative path depending on how frontend consumes it
            // Storing relative path '/storage/profile-photos/...' which maps to public/storage
            $user->avatar_url = '/storage/' . $path;
            $user->save();
        }

        return response()->json([
            'message' => 'Profile photo updated successfully',
            'avatar_url' => $user->avatar_url,
        ]);
    }
}
