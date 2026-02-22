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
        $query = Group::query();

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where('name', 'like', $term);
        }

        $groups = $query->orderBy('name')->paginate(15)->appends($request->query());

        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name'],
        ]);

        // Always create groups without program association
        $data['program_id'] = null;

        Group::create($data);
        return redirect()->route('admin.groups.index')->with('success', 'Group created');
    }

    public function edit(Group $group)
    {
        return view('admin.groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name,' . $group->id],
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
