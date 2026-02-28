<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    public function index(Request $request)
    {
        $query = Campus::withCount('faculties');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhere('location', 'like', $term);
            });
        }

        $campuses = $query->orderBy('name')->get();

        return view('admin.campuses.index', compact('campuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campuses,name',
            'code' => 'nullable|string|max:20|unique:campuses,code',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Campus::create($validated);

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus created successfully.');
    }

    public function update(Request $request, Campus $campus)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campuses,name,' . $campus->id,
            'code' => 'nullable|string|max:20|unique:campuses,code,' . $campus->id,
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $campus->update($validated);

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus updated successfully.');
    }

    public function destroy(Campus $campus)
    {
        if ($campus->faculties()->exists()) {
            return redirect()->route('admin.campuses.index')
                ->with('error', 'Cannot delete campus with assigned faculties. Reassign them first.');
        }

        $campus->delete();

        return redirect()->route('admin.campuses.index')
            ->with('success', 'Campus deleted successfully.');
    }
}
