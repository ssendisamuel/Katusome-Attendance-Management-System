<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SystemStatusController extends Controller
{
    public function index()
    {
        return view('admin.system-status.index');
    }

    public function runAutoClockOut()
    {
        try {
            \App\Jobs\AutoClockOutJob::dispatchSync();
            return redirect()->back()->with('success', 'Auto Clock-Out Job ran successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to run job: ' . $e->getMessage());
        }
    }

    public function runMarkAbsent()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('attendance:mark-absent');
            return redirect()->back()->with('success', 'Absenteeism Check ran successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to run command: ' . $e->getMessage());
        }
    }
}
