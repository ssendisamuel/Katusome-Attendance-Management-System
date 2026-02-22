<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSemester;
use Illuminate\Http\Request;

class AcademicSemesterController extends Controller
{
    public function index()
    {
        $semesters = AcademicSemester::orderByDesc('year')
            ->orderByDesc('semester')
            ->get();

        return view('content.admin.academic-semesters.index', compact('semesters'));
    }

    public function create()
    {
        return view('content.admin.academic-semesters.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:Semester 1,Semester 2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        // Check for duplicate
        $existing = AcademicSemester::where('year', $data['year'])
            ->where('semester', $data['semester'])
            ->first();

       if ($existing) {
            return back()->withErrors(['year' => 'This academic year and semester combination already exists.'])->withInput();
        }

        AcademicSemester::create($data);

        return redirect()->route('admin.academic-semesters.index')
            ->with('success', 'Academic semester created successfully.');
    }

    public function edit(AcademicSemester $academicSemester)
    {
        return view('content.admin.academic-semesters.edit', compact('academicSemester'));
    }

    public function update(Request $request, AcademicSemester $academicSemester)
    {
        $data = $request->validate([
            'year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester' => ['required', 'in:Semester 1,Semester 2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $academicSemester->update($data);

        return redirect()->route('admin.academic-semesters.index')
            ->with('success', 'Academic semester updated successfully.');
    }

    public function destroy(AcademicSemester $academicSemester)
    {
        // Prevent deletion if has enrollments or schedules
        if ($academicSemester->enrollments()->exists() || $academicSemester->schedules()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete a semester with existing enrollments or schedules.']);
        }

        $academicSemester->delete();

        return redirect()->route('admin.academic-semesters.index')
            ->with('success', 'Academic semester deleted successfully.');
    }

    /**
     * Activate a semester
     */
    public function activate(AcademicSemester $academicSemester)
    {
        $academicSemester->activate();

        return redirect()->route('admin.academic-semesters.index')
            ->with('success', "{$academicSemester->display_name} is now the active semester for enrollment.");
    }

    /**
     * Deactivate the active semester
     */
    public function deactivate(AcademicSemester $academicSemester)
    {
        $academicSemester->is_active = false;
        $academicSemester->save();

        return redirect()->route('admin.academic-semesters.index')
            ->with('success', "{$academicSemester->display_name} has been deactivated. No semester is currently active.");
    }
}
