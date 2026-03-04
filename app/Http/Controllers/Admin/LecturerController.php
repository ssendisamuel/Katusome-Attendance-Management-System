<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class LecturerController extends Controller
{
    public function index(Request $request)
    {
        $query = Lecturer::with(['user', 'department'])->withCount('courses');

        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn($qq) => $qq->where('name', 'like', $term)
                                                 ->orWhere('email', 'like', $term))
                  ->orWhere('phone', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('designation', 'like', $term);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        $lecturers = $query->orderBy(
            DB::raw('(select name from users where users.id = lecturers.user_id)'), 'asc'
        )->get();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'title' => 'Lecturers',
                'columns' => ['Title', 'Name', 'Designation', 'Email', 'Phone', 'Department'],
                'rows' => $lecturers->map(fn($l) => [
                    $l->title ?? '—',
                    optional($l->user)->name ?? '—',
                    $l->designation ?? '—',
                    optional($l->user)->email ?? '—',
                    $l->phone ?? '—',
                    $l->department?->code ?? '—',
                ]),
                'meta' => ['generated_at' => now()->format('d M Y H:i'), 'user' => optional($request->user())->name],
                'summary' => ['total' => $lecturers->count()],
            ]);
        }

        return view('admin.lecturers.index', compact('lecturers', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $initial = 'password';
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($initial),
            'must_change_password' => true,
            'role' => 'lecturer',
        ]);

        Lecturer::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'title' => $data['title'] ?? null,
            'designation' => $data['designation'] ?? null,
            'department_id' => $data['department_id'] ?? null,
        ]);

        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer created successfully.');
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . optional($lecturer->user)->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        if ($lecturer->user) {
            $lecturer->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'lecturer',
            ]);
            $lecturer->user_id = $user->id;
        }

        $lecturer->update([
            'phone' => $data['phone'] ?? null,
            'title' => $data['title'] ?? null,
            'designation' => $data['designation'] ?? null,
            'department_id' => $data['department_id'] ?? null,
        ]);

        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer updated successfully.');
    }

    public function destroy(Lecturer $lecturer)
    {
        if ($lecturer->courses()->exists()) {
            return redirect()->route('admin.lecturers.index')
                ->with('error', 'Cannot delete lecturer with teaching assignments. Remove assignments first.');
        }

        $user = $lecturer->user;
        $lecturer->delete();
        if ($user) {
            $user->delete();
        }
        return redirect()->route('admin.lecturers.index')->with('success', 'Lecturer deleted successfully.');
    }

    /**
     * Bulk import lecturers from CSV.
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_map('strtolower', array_map('trim', array_shift($rows)));

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (count($row) < count($header)) continue;
            $record = array_combine($header, $row);

            $name = trim($record['name'] ?? '');
            $email = trim($record['email'] ?? '');
            if (!$name) continue;

            // Auto-generate email if not provided
            if (!$email) {
                $email = $this->generateEmail($name);
            }

            // Skip if email exists
            if (User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'lecturer',
            ]);

            $deptCode = trim($record['department'] ?? '');
            $deptId = $deptCode ? Department::where('code', $deptCode)->value('id') : null;

            Lecturer::create([
                'user_id' => $user->id,
                'title' => trim($record['title'] ?? '') ?: null,
                'designation' => trim($record['designation'] ?? '') ?: null,
                'phone' => trim($record['phone'] ?? '') ?: null,
                'department_id' => $deptId,
            ]);

            $created++;
        }

        return redirect()->route('admin.lecturers.index')
            ->with('success', "Imported {$created} lecturers. {$skipped} skipped (existing emails).");
    }

    /**
     * Select2-compatible lecturers search endpoint.
     */
    public function search(Request $request)
    {
        $termInput = $request->input('q', $request->input('term'));
        $term = $termInput ? ('%' . trim($termInput) . '%') : null;

        $q = Lecturer::with('user');
        if ($term) {
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('user', fn($u) => $u->where('name', 'like', $term)
                                                  ->orWhere('email', 'like', $term))
                   ->orWhere('phone', 'like', $term);
            });
        }

        $q->orderBy(DB::raw('(select name from users where users.id = lecturers.user_id)'), 'asc');

        $page = max(1, (int)$request->input('page', 1));
        $perPage = 20;
        $total = (clone $q)->count();
        $items = $q->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = $items->map(function ($l) {
            $name = optional($l->user)->name ?? ('Lecturer #' . $l->id);
            $email = optional($l->user)->email;
            return [
                'id' => $l->id,
                'text' => $email ? ($name . ' (' . $email . ')') : $name,
                'name' => $name,
                'email' => $email,
                'phone' => $l->phone,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Generate MUBS email from name: "Surname Firstname" -> "fsurname@mubs.ac.ug"
     */
    public static function generateEmail(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            $surname = strtolower(preg_replace('/[^a-zA-Z]/', '', $parts[count($parts) - 1] ?? $parts[0]));
            $first = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', $parts[0]), 0, 1));
            return $first . $surname . '@mubs.ac.ug';
        }
        return strtolower(preg_replace('/[^a-zA-Z]/', '', $name)) . '@mubs.ac.ug';
    }
}
