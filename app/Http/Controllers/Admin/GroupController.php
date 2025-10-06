<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Program;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('program')->withCount('students')->paginate(15);
        return view('admin.groups.index', compact('groups'));
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