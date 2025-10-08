<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Program;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Group::with('program')->withCount('students');
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhereHas('program', fn($qq) => $qq->where('name', 'like', $term));
            });
        }
        $groups = $query->orderBy('name')->paginate(15)->appends($request->query());

        if ($request->wantsJson() || $request->input('format') === 'json') {
            $rows = $query->orderBy('name')->get();
            return response()->json([
                'title' => 'Groups',
                'columns' => ['Name', 'Code', 'Program', 'Students'],
                'rows' => $rows->map(function ($g) {
                    return [$g->name, $g->code, optional($g->program)->name, $g->students_count];
                }),
                'meta' => [
                    'generated_at' => now()->format('d M Y H:i'),
                    'filters' => [
                        'program_id' => $request->input('program_id'),
                        'search' => $request->input('search'),
                    ],
                    'user' => optional($request->user())->name,
                ],
                'summary' => [
                    'total' => $rows->count(),
                ],
            ]);
        }

        $programs = Program::all();
        return view('admin.groups.index', compact('groups', 'programs'));
    }

    public function create()
    {
        $programs = Program::all();
        return view('admin.groups.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        Group::create($data);
        return redirect()->route('admin.groups.index')->with('success', 'Group created');
    }

    public function edit(Group $group)
    {
        $programs = Program::all();
        return view('admin.groups.edit', compact('group', 'programs'));
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        $group->update($data);
        return redirect()->route('admin.groups.index')->with('success', 'Group updated');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Group deleted');
    }

    /**
     * Return groups for a given program (JSON for cascading selects).
     */
    public function byProgram(Program $program)
    {
        return response()->json(
            Group::where('program_id', $program->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }
}