<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('program')->paginate(15);
        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $programs = Program::all();
        return view('admin.courses.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        Course::create($data);
        return redirect()->route('admin.courses.index')->with('success', 'Course created');
    }

    public function edit(Course $course)
    {
        $programs = Program::all();
        return view('admin.courses.edit', compact('course', 'programs'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code,' . $course->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        $course->update($data);
        return redirect()->route('admin.courses.index')->with('success', 'Course updated');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Course deleted');
    }
}