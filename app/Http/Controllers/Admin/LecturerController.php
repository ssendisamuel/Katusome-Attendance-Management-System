<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    public function index()
    {
        $lecturers = Lecturer::paginate(15);
        return view('admin.lecturers.index', compact('lecturers'));
    }

    public function create()
    {
        return view('admin.lecturers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        Lecturer::create($data);
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer created');
    }

    public function edit(Lecturer $lecturer)
    {
        return view('admin.lecturers.edit', compact('lecturer'));
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $lecturer->update($data);
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer updated');
    }

    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer deleted');
    }
}