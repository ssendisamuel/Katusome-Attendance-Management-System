<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Course::with('program');
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
        $courses = $query->orderBy('code')->paginate(15)->appends($request->query());

        if ($request->wantsJson() || $request->input('format') === 'json') {
            $rows = $query->orderBy('code')->get();
            return response()->json([
                'title' => 'Courses',
                'columns' => ['Code', 'Name', 'Program'],
                'rows' => $rows->map(function ($c) {
                    return [$c->code, $c->name, optional($c->program)->name];
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
        return view('admin.courses.index', compact('courses', 'programs'));
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