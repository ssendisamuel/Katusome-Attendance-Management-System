<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Group;

class ApiController extends Controller
{
    /**
     * Get all active programs.
     */
    public function getPrograms()
    {
        $programs = Program::select('id', 'name', 'code')->get();
        return response()->json(['programs' => $programs]);
    }

    /**
     * Get groups for a specific program.
     */
    public function getGroups($programId)
    {
        $groups = Group::where('program_id', $programId)
            ->select('id', 'name')
            ->get();
        return response()->json(['groups' => $groups]);
    }
    /**
     * Get all groups.
     */
    public function getAllGroups()
    {
        $groups = Group::select('id', 'name')->get();
        return response()->json(['groups' => $groups]);
    }

    /**
     * Get active semester.
     */
    public function getActiveSemester()
    {
        $semester = \App\Models\AcademicSemester::where('is_active', true)->first();
        if ($semester) {
            return response()->json([
                'id' => $semester->id,
                'name' => $semester->name,
                'year' => $semester->academic_year,
                'display_name' => $semester->display_name // Accessor if exists, or construct it
            ]);
        }
        return response()->json(['message' => 'No active semester found'], 404);
    }
}
