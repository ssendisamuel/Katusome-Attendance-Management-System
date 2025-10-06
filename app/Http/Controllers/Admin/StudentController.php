<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Program;
use App\Models\Group;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['program', 'group'])->paginate(15);
        return view('admin.students.index', compact('students'));
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
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no'],
            'reg_no' => ['nullable', 'string', 'max:50', 'unique:students,reg_no'],
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        Student::create($data);
        return redirect()->route('admin.students.index')->with('success', 'Student created');
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
            'email' => ['required', 'email', 'max:255', 'unique:students,email,'.$student->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'student_no' => ['required', 'string', 'max:50', 'unique:students,student_no,'.$student->id],
            'reg_no' => ['nullable', 'string', 'max:50', 'unique:students,reg_no,'.$student->id],
            'program_id' => ['required', 'exists:programs,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $student->update($data);
        return redirect()->route('admin.students.index')->with('success', 'Student updated');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', 'Student deleted');
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
            if (!in_array($gender, ['male','female','other',''], true)) {
                $skipped++;
                $errors[] = "Invalid gender '{$gender}' for {$studentNo}";
                continue;
            }
            // Check email uniqueness conflict
            $emailConflict = Student::where('email', $email)
                ->where('student_no', '!=', $studentNo)
                ->exists();
            if ($emailConflict) {
                $skipped++;
                $errors[] = "Email conflict for {$email}";
                continue;
            }

            $attributes = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: null,
                'gender' => $gender ?: null,
                'reg_no' => $regNo ?: null,
                'program_id' => $programId,
                'group_id' => $groupId,
                'year_of_study' => $year,
            ];

            $existing = Student::where('student_no', $studentNo)->first();
            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                Student::create(array_merge($attributes, ['student_no' => $studentNo]));
                $created++;
            }
        }
        fclose($handle);

        $message = "Imported: {$created} created, {$updated} updated, {$skipped} skipped.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->route('admin.students.index')->with('success', $message);
    }
}