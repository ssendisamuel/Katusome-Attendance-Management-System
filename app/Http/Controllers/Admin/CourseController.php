<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('department.faculty');

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhere('abbreviation', 'like', $term);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        $courses = $query->orderBy('code')->get();
        $faculties = Faculty::where('is_active', true)->with('departments')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Courses',
                'columns' => ['Code', 'Name', 'Abbreviation', 'Credit Units', 'Department'],
                'rows' => $courses->map(fn($c) => [$c->code, $c->name, $c->abbreviation ?? '—', $c->credit_units ?? '—', $c->department?->code ?? '—']),
                'meta' => [
                    'generated_at' => now()->format('d M Y H:i'),
                    'filters' => ['search' => $request->input('search'), 'department_id' => $request->input('department_id')],
                    'user' => optional($request->user())->name,
                ],
                'summary' => ['total' => $courses->count()],
            ]);
        }

        return view('admin.courses.index', compact('courses', 'faculties', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:20'],
            'credit_units' => ['nullable', 'integer', 'min:1', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        Course::create($data);
        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully.');
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:courses,code,' . $course->id],
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:20'],
            'credit_units' => ['nullable', 'integer', 'min:1', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $course->update($data);
        return redirect()->route('admin.courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        if ($course->programs()->exists()) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'Cannot delete course assigned to programmes. Remove assignments first.');
        }

        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Course deleted successfully.');
    }
}
