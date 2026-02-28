<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $query = Faculty::with(['campuses', 'dean'])->withCount('departments');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('campuses', function ($q) use ($request) {
                $q->where('campuses.id', $request->integer('campus_id'));
            });
        }

        $faculties = $query->orderBy('name')->get();
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.faculties.index', compact('faculties', 'campuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:faculties,code',
            'name' => 'required|string|max:255|unique:faculties,name',
            'campus_ids' => 'nullable|array',
            'campus_ids.*' => 'exists:campuses,id',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $faculty = Faculty::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
        ]);

        if (!empty($validated['campus_ids'])) {
            $faculty->campuses()->sync($validated['campus_ids']);
        }

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty created successfully.');
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:faculties,code,' . $faculty->id,
            'name' => 'required|string|max:255|unique:faculties,name,' . $faculty->id,
            'campus_ids' => 'nullable|array',
            'campus_ids.*' => 'exists:campuses,id',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $faculty->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'],
        ]);

        $faculty->campuses()->sync($validated['campus_ids'] ?? []);

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty updated successfully.');
    }

    public function destroy(Faculty $faculty)
    {
        if ($faculty->departments()->exists()) {
            return redirect()->route('admin.faculties.index')
                ->with('error', 'Cannot delete faculty with assigned departments. Reassign them first.');
        }

        $faculty->campuses()->detach();
        $faculty->delete();

        return redirect()->route('admin.faculties.index')
            ->with('success', 'Faculty deleted successfully.');
    }
}
