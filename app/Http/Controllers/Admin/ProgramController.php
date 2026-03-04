<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Program::with(['faculty.campuses'])->withCount('courses');

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->integer('faculty_id'));
        }

        // Sort by faculty name, then programme name
        $programs = $query->orderByRaw('COALESCE(faculty_id, 999999)')
            ->orderBy('name')
            ->get();

        $faculties = Faculty::where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Programmes',
                'columns' => ['Name', 'Code', 'Faculty', 'Duration', 'Courses'],
                'rows' => $programs->map(function ($p) {
                    return [$p->name, $p->code, $p->faculty?->code ?? '—', $p->duration_years . ' yr(s)', $p->courses_count];
                }),
                'meta' => [
                    'generated_at' => now()->format('d M Y H:i'),
                    'filters' => ['search' => $request->input('search'), 'faculty_id' => $request->input('faculty_id')],
                    'user' => optional($request->user())->name,
                ],
                'summary' => ['total' => $programs->count()],
            ]);
        }

        return view('admin.programs.index', compact('programs', 'faculties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:programs,code'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:7'],
        ]);

        Program::create($data);
        return redirect()->route('admin.programs.index')->with('success', 'Programme created successfully.');
    }

    public function update(Request $request, Program $program)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:programs,code,' . $program->id],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:7'],
        ]);

        $program->update($data);
        return redirect()->route('admin.programs.index')->with('success', 'Programme updated successfully.');
    }

    public function destroy(Program $program)
    {
        if ($program->courses()->exists()) {
            return redirect()->route('admin.programs.index')
                ->with('error', 'Cannot delete programme with assigned courses. Remove course assignments first.');
        }

        $program->delete();
        return redirect()->route('admin.programs.index')->with('success', 'Programme deleted successfully.');
    }
}
