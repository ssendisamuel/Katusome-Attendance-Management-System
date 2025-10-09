<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lecturer;
use Illuminate\Http\Request;

class CourseLecturerController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['program', 'lecturers']);
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }
        $courses = $query->orderBy('code')->paginate(15)->appends($request->query());
        $programs = \App\Models\Program::all();

        if ($request->ajax()) {
            if ($request->input('fragment') === 'table') {
                return view('admin.course_lecturers.partials.table', compact('courses'));
            }
        }

        return view('admin.course_lecturers.index', compact('courses', 'programs'));
    }

    public function edit(Course $course)
    {
        $lecturers = Lecturer::with('user')->orderBy(
            \Illuminate\Support\Facades\DB::raw('(select name from users where users.id = lecturers.user_id)'),
            'asc'
        )->get();
        return view('admin.course_lecturers.edit', compact('course', 'lecturers'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'lecturer_ids' => ['nullable', 'array'],
            'lecturer_ids.*' => ['integer', 'exists:lecturers,id'],
        ]);

        $ids = array_values($data['lecturer_ids'] ?? []);
        $course->lecturers()->sync($ids);

        return redirect()->route('admin.course-lecturers.index')->with('success', 'Assignments updated');
    }
}