<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index()
    {
        $buildings = Venue::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        return view('admin.settings.venues.index', compact('buildings'));
    }

    public function create()
    {
        $buildings = Venue::whereNull('parent_id')->orderBy('name')->get();
        return view('admin.settings.venues.create', compact('buildings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|exists:venues,id',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
        ]);

        Venue::create($data);

        return redirect()->route('admin.settings.venues.index')
            ->with('success', 'Venue created successfully.');
    }

    public function edit(Venue $venue)
    {
        $buildings = Venue::whereNull('parent_id')
            ->where('id', '!=', $venue->id)
            ->orderBy('name')
            ->get();

        return view('admin.settings.venues.edit', compact('venue', 'buildings'));
    }

    public function update(Request $request, Venue $venue)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'parent_id'     => 'nullable|exists:venues,id',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
        ]);

        // Prevent a building from becoming its own child
        if (isset($data['parent_id']) && $data['parent_id'] == $venue->id) {
            return back()->withErrors(['parent_id' => 'A venue cannot be its own parent.']);
        }

        $venue->update($data);

        return redirect()->route('admin.settings.venues.index')
            ->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue)
    {
        $venue->delete(); // cascade deletes children via FK
        return redirect()->route('admin.settings.venues.index')
            ->with('success', 'Venue deleted successfully.');
    }
}
