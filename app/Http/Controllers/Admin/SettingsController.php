<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocationSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function locationEdit()
    {
        $setting = LocationSetting::current();
        return view('admin.settings.location', compact('setting'));
    }

    public function locationUpdate(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        $setting = LocationSetting::current();
        $setting->fill($data);
        $setting->save();

        return redirect()->route('admin.settings.location.edit')->with('success', 'Location settings updated.');
    }
}