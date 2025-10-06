<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Create canonical user record for lecturer
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(12)),
            'role' => 'lecturer',
        ]);

        Lecturer::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
        ]);
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . optional($lecturer->user)->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        // Ensure canonical user exists and is updated
        if (!$lecturer->user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(12)),
                'role' => 'lecturer',
            ]);
            $lecturer->user()->associate($user);
        } else {
            $lecturer->user->name = $data['name'];
            $lecturer->user->email = $data['email'];
            $lecturer->user->save();
        }

        $lecturer->phone = $data['phone'] ?? null;
        $lecturer->save();
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer updated');
    }

    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer deleted');
    }
}