<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleSwitchController extends Controller
{
    /**
     * Get available roles for the current user
     */
    public function getAvailableRoles(Request $request)
    {
        $user = $request->user();

        $roles = collect([
            [
                'role' => $user->role,
                'display_name' => ucfirst(str_replace('_', ' ', $user->role)),
                'is_primary' => true,
                'is_active' => $user->getCurrentRole() === $user->role,
            ]
        ]);

        $additionalRoles = $user->getAvailableRoles()->map(function ($userRole) use ($user) {
            return [
                'role' => $userRole->role,
                'display_name' => $userRole->display_name,
                'is_primary' => false,
                'is_active' => $user->getCurrentRole() === $userRole->role,
                'context' => [
                    'campus' => $userRole->campus?->name,
                    'faculty' => $userRole->faculty?->name,
                    'department' => $userRole->department?->name,
                ],
            ];
        });

        $allRoles = $roles->concat($additionalRoles);

        return response()->json([
            'current_role' => $user->getCurrentRole(),
            'roles' => $allRoles,
        ]);
    }

    /**
     * Switch to a different role
     */
    public function switchRole(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user = $request->user();

        if ($user->switchRole($validated['role'])) {
            // Regenerate session to apply new role
            $request->session()->regenerate();

            // Determine redirect URL based on new role
            $redirectUrl = $this->getRedirectUrlForRole($validated['role']);

            return response()->json([
                'success' => true,
                'message' => 'Role switched successfully.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have access to this role.',
        ], 403);
    }

    /**
     * Get redirect URL based on role
     */
    private function getRedirectUrlForRole(string $role): string
    {
        return match ($role) {
            'admin', 'super_admin', 'principal', 'registrar', 'campus_chief', 'qa_director', 'dean', 'hod' => route('admin.dashboard'),
            'lecturer' => route('lecturer.dashboard'),
            'student' => route('student.dashboard'),
            default => route('login'),
        };
    }
}
