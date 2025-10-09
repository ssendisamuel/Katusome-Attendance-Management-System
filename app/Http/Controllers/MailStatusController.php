<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MailStatusController extends Controller
{
    /**
     * Check welcome mail send status for a given user id.
     */
    public function welcome(Request $request)
    {
        $id = (int) $request->query('user_id');
        if (!$id) {
            return response()->json(['status' => 'unknown'], 400);
        }
        $status = Cache::get('mail:welcome:' . $id, 'unknown');
        return response()->json(['status' => $status]);
    }

    /**
     * Check attendance confirmation mail status for a given attendance id.
     */
    public function attendance(Request $request)
    {
        $id = (int) $request->query('attendance_id');
        if (!$id) {
            return response()->json(['status' => 'unknown'], 400);
        }
        $status = Cache::get('mail:attendance:' . $id, 'unknown');
        return response()->json(['status' => $status]);
    }
}