<?php

namespace App\Jobs;

use App\Mail\WelcomeUserMail;
use App\Models\Group;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $programId;
    protected $groupId;
    protected $yearOfStudy;
    protected $adminUser;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $programId, $groupId, $yearOfStudy, $adminUser = null)
    {
        $this->filePath = $filePath;
        $this->programId = $programId;
        $this->groupId = $groupId;
        $this->yearOfStudy = $yearOfStudy;
        $this->adminUser = $adminUser;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting student import job", ['file' => $this->filePath]);

        if (!file_exists($this->filePath)) {
            Log::error("Import file not found: " . $this->filePath);
            return;
        }

        $handle = fopen($this->filePath, 'r');
        $header = null;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Cache programs for faster lookup
        $programsMap = Program::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
             return [strtoupper(trim($name)) => $id];
        });

        // Increase memory limit for this job
        ini_set('memory_limit', '512M');

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (!$header) {
                // Remove BOM if present
                $row[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $row[0]);
                $header = array_map(fn($h) => strtoupper(trim($h)), $row);
                continue;
            }

            $record = [];
            foreach ($header as $index => $colName) {
                $record[$colName] = $row[$index] ?? '';
            }

            // Map fields
            $surname = trim($record['SURNAME'] ?? '');
            $othernames = trim($record['OTHERNAMES'] ?? $record['OTHER NAMES'] ?? '');
            $fullname = trim("$surname $othernames");

            $email = trim($record['EMAIL'] ?? '');
            $phone = trim($record['PHONE'] ?? '');
            $rawGender = strtoupper(trim($record['GENDER'] ?? ''));
            $studentNo = trim($record['STUDENT NO.'] ?? $record['STUDENT NO'] ?? '');
            $regNo = trim($record['REGISTRATION NO.'] ?? $record['REGISTRATION NO'] ?? '');
            $progName = strtoupper(trim($record['PROGRAMME'] ?? $record['PROGRAM'] ?? ''));

            // Validation
            if ($fullname === '' || $email === '' || $studentNo === '') {
                $skipped++;
                $errors[] = "Missing required fields (Name, Email, Student No) for row.";
                continue;
            }
            if (!preg_match('/^[^@\s]+@mubs\.ac\.ug$/i', $email)) {
                $skipped++;
                $errors[] = "Invalid email domain for {$email}";
                continue;
            }

            // Gender Normalization
            $gender = null;
            if (in_array($rawGender, ['M', 'MALE', 'MAN'])) $gender = 'male';
            elseif (in_array($rawGender, ['F', 'FEMALE', 'WOMAN'])) $gender = 'female';
            elseif ($rawGender) $gender = 'other';

            // Resolve Program
            $programId = $this->programId;
            if ($progName && isset($programsMap[$progName])) {
                $programId = $programsMap[$progName];
            } elseif ($progName) {
                $skipped++;
                $errors[] = "Unknown program '{$progName}' for {$studentNo}";
                continue;
            }

            if (!$programId) {
                $skipped++;
                $errors[] = "No program specified for {$studentNo}";
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
                'gender' => $gender,
                'reg_no' => $regNo ?: $studentNo, // Fallback if reg missing
                'program_id' => $programId,
                'group_id' => $this->groupId,
                'year_of_study' => $this->yearOfStudy,
            ];

            $existing = Student::where('student_no', $studentNo)->first();
            if ($existing) {
                // Update User
                if ($existing->user) {
                    $existing->user->name = $fullname;
                    $existing->user->email = $email;
                    $existing->user->save();
                } else {
                     // create basic user if missing
                     $u = User::firstOrCreate(
                         ['email' => $email],
                         ['name' => $fullname, 'password' => Hash::make('password'), 'role' => 'student', 'must_change_password' => true]
                     );
                     $existing->user()->associate($u);
                }
                $existing->update($studentAttrs);
                $updated++;
            } else {
                // Create New
                $user = User::where('email', $email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $fullname,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'must_change_password' => true,
                        'role' => 'student',
                    ]);
                    // Send Welcome Email
                    try {
                        $token = Password::broker()->createToken($user);
                        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
                        $loginUrl = url(route('login', [], false));
                        Mail::mailer('smtp')->to($user->email)->queue(new WelcomeUserMail($user, 'password', $resetUrl, $loginUrl));
                    } catch (\Throwable $e) {
                         Log::warning('Failed to queue welcome mail inside import job: ' . $e->getMessage());
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

        // Clean up file
        @unlink($this->filePath);

        $message = "Import Job Completed: {$created} created, {$updated} updated, {$skipped} skipped.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        Log::info($message);
    }
}
