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
            'reg_no' => ['required', 'string', 'max:50', 'unique:students,reg_no'],  // REQUIRED
            'program_id' => ['nullable', 'exists:programs,id'],  // Optional - set during enrollment
            'group_id' => ['nullable', 'exists:groups,id'],      // Optional - set during enrollment
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],  // Optional
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
        try {
            \Illuminate\Support\Facades\Mail::mailer('smtp')->to($user->email)->queue(new \App\Mail\WelcomeUserMail($user, $initial, $resetUrl, $loginUrl));
            \Illuminate\Support\Facades\Log::info('Queued welcome mail for new student', ['email' => $user->email]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to queue welcome mail for new student: ' . $user->email . ' error: ' . $e->getMessage());
        }

        // Create student linked to canonical user
        Student::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_no' => $data['student_no'],
            'reg_no' => $data['reg_no'] ?? null,
            'program_id' => $data['program_id'] ?? null,  // Nullable - will be set during enrollment
            'group_id' => $data['group_id'] ?? null,      // Nullable - will be set during enrollment
            'year_of_study' => $data['year_of_study'] ?? null,  // Nullable
        ]);
        return redirect()->route('admin.students.index')
            ->with('success', 'Student created')
            ->with('info', 'Welcome email sent immediately via SMTP');
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
            'reg_no' => ['required', 'string', 'max:50', 'unique:students,reg_no,'.$student->id],  // REQUIRED
            'program_id' => ['nullable', 'exists:programs,id'],  // Optional
            'group_id' => ['nullable', 'exists:groups,id'],      // Optional
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
            try {
                \Illuminate\Support\Facades\Mail::mailer('smtp')->to($user->email)->queue(new \App\Mail\WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
                \Illuminate\Support\Facades\Log::info('Queued welcome mail when creating missing student user', ['email' => $user->email]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to queue welcome mail for created user: ' . $user->email . ' error: ' . $e->getMessage());
            }
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
            'reg_no' => $data['reg_no'],  // Required
            'program_id' => $data['program_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
            'year_of_study' => $data['year_of_study'] ?? $student->year_of_study,
        ])->save();
        return redirect()->route('admin.students.index')
            ->with('success', 'Student updated')
            ->with('info', 'Welcome email sent immediately via SMTP');
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

    public function show(Student $student)
    {
        $student->load(['program', 'group', 'user']);
        if (request()->ajax()) {
            return view('admin.students.partials.details', compact('student'));
        }
        // Fallback for non-ajax
        return view('admin.students.show', compact('student'));
    }

    // Search students for typeahead suggestions
    public function search(Request $request)
    {
        $term = trim($request->input('q', ''));
        $limit = (int)$request->input('limit', 20);
        $groupId = $request->input('group_id');
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
        if ($groupId) {
            $query->where('group_id', (int)$groupId);
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
        $headers = ['SURNAME', 'OTHERNAMES', 'PROGRAMME', 'STUDENT NO.', 'REGISTRATION NO.', 'GENDER', 'EMAIL', 'PHONE'];
        $csv = implode(',', $headers) . "\n";
        $csv .= "DOE,JOHN,Bachelor of Science in Computer Science,S123456,REG2025-001,Male,johndoe@mubs.ac.ug,256700000000\n";
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ]);
    }

    public function importProcess(Request $request)
    {
        // Handle Chunked JSON Upload
        if ($request->isJson() && $request->has('rows')) {
            $data = $request->validate([
                'rows' => ['required', 'array'],
                // Loop handles validation per row to avoid failing the whole chunk
                'program_id' => ['nullable', 'exists:programs,id'],
                'group_id' => ['nullable', 'exists:groups,id'],
                'year_of_study' => ['nullable', 'integer'],
            ]);

            set_time_limit(120); // Should be enough for a small chunk

            $rows = $data['rows'];
            $defaultProgramId = $data['program_id'] ?? null;
            $defaultGroupId = $data['group_id'] ?? null;
            $year = $data['year_of_study'] ?? 1;

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            // Cache programs
            $programsMap = Program::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
                 return [strtoupper(trim($name)) => $id];
            });

            foreach ($rows as $record) {
                 // Map fields from JSON (keys should be normalized in JS, but let's be safe)
                 $surname = trim($record['SURNAME'] ?? '');
                 $othernames = trim($record['OTHERNAMES'] ?? $record['OTHER NAMES'] ?? $record['OTHERNAMES'] ?? '');
                 $fullname = trim("$surname $othernames");

                 $email = trim($record['EMAIL'] ?? '');
                 $phone = trim($record['PHONE'] ?? '');
                 $rawGender = strtoupper(trim($record['GENDER'] ?? ''));
                 // Fallbacks for "STUDENT NO." vs "STUDENTNO." (spaced vs unspaced)
                 $studentNo = trim($record['STUDENT NO.'] ?? $record['STUDENT NO'] ?? $record['STUDENTNO.'] ?? $record['STUDENTNO'] ?? '');
                 $regNo = trim($record['REGISTRATION NO.'] ?? $record['REGISTRATION NO'] ?? $record['REGISTRATIONNO.'] ?? $record['REGISTRATIONNO'] ?? '');
                 $progName = strtoupper(trim($record['PROGRAMME'] ?? $record['PROGRAM'] ?? ''));

                 // Validation
                 if ($fullname === '' || $email === '' || $studentNo === '') {
                     $skipped++;
                     // More detailed error
                     $missing = [];
                     if ($fullname === '') $missing[] = 'Name';
                     if ($email === '') $missing[] = 'Email';
                     if ($studentNo === '') $missing[] = 'Student No';
                     $errors[] = "Row {$email}: Missing " . implode(', ', $missing);
                     continue;
                 }
                 if (!preg_match('/^[^@\s]+@mubs\.ac\.ug$/i', $email)) {
                     $skipped++;
                     $errors[] = "Invalid email domain: {$email}";
                     continue;
                 }

                 // Resolve Program
                 $programId = $defaultProgramId;
                 if ($progName && isset($programsMap[$progName])) {
                     $programId = $programsMap[$progName];
                 }

                 if (!$programId) {
                     $skipped++;
                     $errors[] = "No program found for {$studentNo}";
                     continue;
                 }

                 // Gender
                 $gender = null;
                 if (in_array($rawGender, ['M', 'MALE', 'MAN'])) $gender = 'male';
                 elseif (in_array($rawGender, ['F', 'FEMALE', 'WOMAN'])) $gender = 'female';
                 elseif ($rawGender) $gender = 'other';

                 $studentAttrs = [
                     'phone' => $phone ?: null,
                     'gender' => $gender,
                     'reg_no' => $regNo ?: $studentNo,
                     'program_id' => $programId,
                     'group_id' => $defaultGroupId,
                     'year_of_study' => $year,
                 ];

                 $existing = Student::where('student_no', $studentNo)->first();
                 if ($existing) {
                     // Update User
                    if ($existing->user) {
                        $existing->user->name = $fullname;
                        $existing->user->email = $email;
                        $existing->user->save();
                    }
                     $existing->update($studentAttrs);
                     $updated++;
                 } else {
                     // Create
                     $user = User::firstOrCreate(
                         ['email' => $email],
                         ['name' => $fullname, 'password' => Hash::make('password'), 'role' => 'student', 'must_change_password' => true]
                     );

                     // Send Email if new user
                     if ($user->wasRecentlyCreated) {
                         try {
                            $token = Password::broker()->createToken($user);
                            $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
                            $loginUrl = url(route('login', [], false));
                            Mail::mailer('smtp')->to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
                         } catch (\Throwable $e) {}
                     }

                     Student::firstOrCreate(
                         ['student_no' => $studentNo],
                         array_merge($studentAttrs, ['user_id' => $user->id])
                     );
                     $created++;
                 }
            }

            return response()->json([
                'success' => true,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
        }

        // Fallback to File Upload (Legacy)
        $data = $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        // Increase execution time and memory for bulk operations
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        // ... (Legacy logic would go here, but omitted for brevity as we are moving to chunks)
        // For now, redirect with error if they manage to bypass JS
        return redirect()->back()->with('error', 'Please enable JavaScript to upload files.');
    }
}
