<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Group;
use Illuminate\Http\Request;

class RegisterBasic extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    $programs = Program::all();
    $groups = Group::all();
    return view('content.authentications.auth-register-basic', [
      'pageConfigs' => $pageConfigs,
      'programs' => $programs,
      'groups' => $groups,
    ]);
  }
}
