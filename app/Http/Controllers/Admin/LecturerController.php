<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeUserMail;

class LecturerController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Lecturer::query();

        // Search by lecturer identity
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn($qq) => $qq->where('name', 'like', $term)
                                                 ->orWhere('email', 'like', $term))
                  ->orWhere('phone', 'like', $term);
            });
        }

        // Order and paginate
        $lecturers = $query->orderBy(
            DB::raw('(select name from users where users.id = lecturers.user_id)'),
            'asc'
        )->paginate(15)->appends($request->query());

        if ($request->wantsJson() || $request->input('format') === 'json') {
            $rows = $query->orderBy(
                DB::raw('(select name from users where users.id = lecturers.user_id)'),
                'asc'
            )->get();
            return response()->json([
                'title' => 'Lecturers',
                'columns' => ['Name', 'Email', 'Phone'],
                'rows' => $rows->map(function ($l) {
                    $name = optional($l->user)->name ?? $l->name;
                    $email = optional($l->user)->email ?? $l->email;
                    return [$name, $email, $l->phone];
                }),
                'meta' => [
                    'generated_at' => now()->format('d M Y H:i'),
                    'filters' => [
                        'search' => $request->input('search'),
                    ],
                    'user' => optional($request->user())->name,
                ],
                'summary' => [
                    'total' => $rows->count(),
                ],
            ]);
        }

        return view('admin.lecturers.index', compact('lecturers'));
    }

    public function create()
    {
        return view('admin.lecturers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'phone' => ['nullable', 'string', 'max:50'],
            'initial_password' => ['nullable', 'string', 'min:8'],
        ], [
            'email.regex' => 'Email must be a mubs.ac.ug address.',
        ]);

        // Create canonical user record for lecturer
        $initial = $data['initial_password'] ?? 'password';
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initial),
            'must_change_password' => true,
            'role' => 'lecturer',
        ]);

        // Build reset URL and login URL for welcome email
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
        $loginUrl = url(route('login', [], false));
        Mail::to($user->email)->queue(new WelcomeUserMail($user, $initial, $resetUrl, $loginUrl));

        Lecturer::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
        ]);
        return redirect()->route('admin.lecturers.index')
            ->with('success', 'Lecturer created')
            ->with('info', 'Welcome emails are being sent in the background');
    }

    public function edit(Lecturer $lecturer)
    {
        return view('admin.lecturers.edit', compact('lecturer'));
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . optional($lecturer->user)->id, 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'phone' => ['nullable', 'string', 'max:50'],
        ], [
            'email.regex' => 'Email must be a mubs.ac.ug address.',
        ]);

        // Ensure canonical user exists and is updated
        if (!$lecturer->user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'lecturer',
            ]);
            $token = Password::broker()->createToken($user);
            $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
            $loginUrl = url(route('login', [], false));
            Mail::to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
            $lecturer->user()->associate($user);
        } else {
            $lecturer->user->name = $data['name'];
            $lecturer->user->email = $data['email'];
            $lecturer->user->save();
        }

        $lecturer->phone = $data['phone'] ?? null;
        $lecturer->save();
        return redirect()->route('admin.lecturers.index')
            ->with('success', 'Lecturer updated')
            ->with('info', 'Welcome emails are being sent in the background');
    }

    public function destroy(Lecturer $lecturer)
    {
        $user = $lecturer->user;
        $lecturer->delete();
        if ($user) {
            $user->delete();
        }
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer deleted');
    }
}