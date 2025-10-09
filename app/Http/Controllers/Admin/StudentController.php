<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Program;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeUserMail;

class StudentController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Student::with(['program', 'group', 'user']);

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('year')) {
            $query->where('year_of_study', $request->integer('year'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn($qq) => $qq->where('name', 'like', $term)
                                                 ->orWhere('email', 'like', $term))
                  ->orWhereHas('program', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhereHas('group', fn($qq) => $qq->where('name', 'like', $term))
                  ->orWhere('student_no', 'like', $term)
                  ->orWhere('reg_no', 'like', $term);
            });
        }

        $students = $query->orderBy(
            DB::raw('(select name from users where users.id = students.user_id)'),
            'asc'
        )->paginate(15)->appends($request->query());

        if ($request->wantsJson() || $request->input('format') === 'json') {
            $rows = $query->orderBy(
                DB::raw('(select name from users where users.id = students.user_id)'),
                'asc'
            )->get();
            return response()->json([
                'title' => 'Students',
                'columns' => ['Name', 'Program', 'Group', 'Year'],
                'rows' => $rows->map(function ($s) {
                    return [
                        optional($s->user)->name ?? $s->name,
                        optional($s->program)->name,
                        optional($s->group)->name,
                        $s->year_of_study,
                    ];
                }),
                'meta' => [
                    'generated_at' => now()->format('d M Y H:i'),
                    'filters' => [
                        'program_id' => $request->input('program_id'),
                        'group_id' => $request->input('group_id'),
                        'year' => $request->input('year'),
                        'search' => $request->input('search'),
                    ],
                    'user' => optional($request->user())->name,
                ],
                'summary' => [
                    'total' => $rows->count(),
                ],
            ]);
        }

        $programs = Program::all();
        $groups = Group::all();
        if ($request->ajax() || $request->input('fragment') === 'table') {
            return view('admin.students.partials.table', compact('students'));
        }
        return view('admin.students.index', compact('students', 'programs', 'groups'));
    }

    public function create()
    {
        $programs = Program::all();
        $groups = Group::all();
        return view('admin.students.create', compact('programs', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Validate against canonical users table
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no'],
            'reg_no' => ['nullable', 'string', 'max:50', 'unique:students,reg_no'],
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
            'initial_password' => ['nullable', 'string', 'min:8'],
        ], [
            'email.regex' => 'Email must be a mubs.ac.ug address.',
        ]);

        // Create canonical user record for student
        $initial = $data['initial_password'] ?? 'password';
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initial),
            'must_change_password' => true,
            'role' => 'student',
        ]);
        // Build reset URL and login URL for welcome email
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
        $loginUrl = url(route('login', [], false));
        Mail::to($user->email)->queue(new WelcomeUserMail($user, $initial, $resetUrl, $loginUrl));

        // Create student linked to canonical user
        Student::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_no' => $data['student_no'],
            'reg_no' => $data['reg_no'] ?? null,
            'program_id' => $data['program_id'],
            'group_id' => $data['group_id'],
            'year_of_study' => $data['year_of_study'] ?? 1,
        ]);
        return redirect()->route('admin.students.index')
            ->with('success', 'Student created')
            ->with('info', 'Welcome emails are being sent in the background');
    }

    public function edit(Student $student)
    {
        $programs = Program::all();
        $groups = Group::all();
        return view('admin.students.edit', compact('student', 'programs', 'groups'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Validate against canonical users table
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . optional($student->user)->id, 'regex:/^[^@\s]+@mubs\.ac\.ug$/i'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no,'.$student->id],
            'reg_no' => ['nullable', 'string', 'max:50', 'unique:students,reg_no,'.$student->id],
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'must_change_password' => ['nullable', 'boolean'],
        ], [
            'email.regex' => 'Email must be a mubs.ac.ug address.',
        ]);
        // Ensure canonical user exists and is updated
        if (!$student->user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'student',
            ]);
            $token = Password::broker()->createToken($user);
            $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
            $loginUrl = url(route('login', [], false));
            Mail::to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
            $student->user()->associate($user);
        } else {
            $student->user->name = $data['name'];
            $student->user->email = $data['email'];
            if (!empty($data['password'])) {
                $student->user->password = \Illuminate\Support\Facades\Hash::make($data['password']);
                $student->user->must_change_password = (bool)($data['must_change_password'] ?? false);
            } elseif (array_key_exists('must_change_password', $data)) {
                $student->user->must_change_password = (bool)$data['must_change_password'];
            }
            $student->user->save();
        }

        // Update non-identity fields on student
        $student->fill([
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_no' => $data['student_no'],
            'reg_no' => $data['reg_no'] ?? null,
            'program_id' => $data['program_id'],
            'group_id' => $data['group_id'],
            'year_of_study' => $data['year_of_study'] ?? $student->year_of_study,
        ])->save();
        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated')
            ->with('info', 'Welcome emails are being sent in the background');
    }

    public function destroy(Student $student)
    {
        $user = $student->user;
        $student->delete();
        if ($user) {
            $user->delete();
        }
        return redirect()->route('admin.students.index')->with('success', 'Student deleted');
    }

    // Search students for typeahead suggestions
    public function search(Request $request)
    {
        $term = trim($request->input('q', ''));
        $limit = (int)$request->input('limit', 20);
        // Search by canonical user name and student_no/reg_no
        $query = Student::with('group', 'user');
        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function($q) use ($like) {
                $q->whereHas('user', fn($qq) => $qq->where('name', 'like', $like))
                  ->orWhere('student_no', 'like', $like)
                  ->orWhere('reg_no', 'like', $like);
            });
        }
        $students = $query->orderBy('id')->limit($limit)->get();
        return response()->json(
            $students->map(function($s){
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'group' => optional($s->group)->name,
                    'label' => $s->name . (optional($s->group)->name ? (' (' . optional($s->group)->name . ')') : ''),
                ];
            })
        );
    }

    // Bulk import
    public function importForm()
    {
        $programs = Program::all();
        $groups = Group::all();
        return view('admin.students.import', compact('programs', 'groups'));
    }

    public function importTemplate()
    {
        $headers = ['name','email','phone','gender','student_no','reg_no'];
        $csv = implode(',', $headers) . "\n";
        $csv .= "John Doe,johndoe@example.com,256700000000,male,S123456,REG2025-001\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ]);
    }

    public function importProcess(Request $request)
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $programId = (int) $data['program_id'];
        $groupId = (int) $data['group_id'];
        $year = $data['year_of_study'] ?? 1;

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (!$header) {
                $header = array_map('trim', $row);
                continue;
            }
            $record = [];
            foreach ($header as $index => $column) {
                $record[$column] = $row[$index] ?? null;
            }
            $name = trim($record['name'] ?? '');
            $email = trim($record['email'] ?? '');
            $phone = trim($record['phone'] ?? '');
            $gender = trim($record['gender'] ?? '');
            $studentNo = trim($record['student_no'] ?? '');
            $regNo = trim($record['reg_no'] ?? '');

            if ($name === '' || $email === '' || $studentNo === '') {
                $skipped++;
                $errors[] = "Missing required fields for student_no {$studentNo}";
                continue;
            }
            if (!preg_match('/^[^@\s]+@mubs\.ac\.ug$/i', $email)) {
                $skipped++;
                $errors[] = "Invalid email domain for {$email}";
                continue;
            }
            if (!in_array($gender, ['male','female','other',''], true)) {
                $skipped++;
                $errors[] = "Invalid gender '{$gender}' for {$studentNo}";
                continue;
            }
            // Check email uniqueness conflict against canonical users
            $emailConflict = User::where('email', $email)
                ->whereDoesntHave('student', function ($q) use ($studentNo) {
                    $q->where('student_no', $studentNo);
                })
                ->exists();
            if ($emailConflict) {
                $skipped++;
                $errors[] = "Email conflict for {$email}";
                continue;
            }

            $studentAttrs = [
                'phone' => $phone ?: null,
                'gender' => $gender ?: null,
                'reg_no' => $regNo ?: null,
                'program_id' => $programId,
                'group_id' => $groupId,
                'year_of_study' => $year,
            ];

            $existing = Student::where('student_no', $studentNo)->first();
            if ($existing) {
                // Ensure a canonical user exists and is updated
                if (!$existing->user) {
                    // Try to find user by email before creating
                    $user = User::where('email', $email)->first();
                    if (!$user) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => Hash::make('password'),
                            'must_change_password' => true,
                            'role' => 'student',
                        ]);
                        $token = Password::broker()->createToken($user);
                        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
                        $loginUrl = url(route('login', [], false));
                        try {
                            Mail::to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
                        } catch (\Throwable $e) {
                            $errors[] = "Mail queue failure for {$email}: " . $e->getMessage();
                        }
                    }
                    $existing->user()->associate($user);
                } else {
                    $existing->user->name = $name;
                    $existing->user->email = $email;
                    $existing->user->save();
                }
                $existing->update($studentAttrs);
                $updated++;
            } else {
                // Create canonical user then student
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'must_change_password' => true,
                        'role' => 'student',
                    ]);
                    $token = Password::broker()->createToken($user);
                    $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
                    $loginUrl = url(route('login', [], false));
                    try {
                        Mail::to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
                    } catch (\Throwable $e) {
                        $errors[] = "Mail queue failure for {$email}: " . $e->getMessage();
                    }
                }
                Student::create(array_merge($studentAttrs, [
                    'user_id' => $user->id,
                    'student_no' => $studentNo,
                ]));
                $created++;
            }
        }
        fclose($handle);

        $message = "Imported: {$created} created, {$updated} updated, {$skipped} skipped.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.students.index')
            ->with('success', $message)
            ->with('info', 'Welcome emails are being sent in the background');
    }
}