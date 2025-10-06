<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Lecturer;
use Illuminate\Support\Facades\Schema;

class SyncIdentityDuplicates extends Command
{
    protected $signature = 'identity:sync-duplicates {--dry : Show changes without writing}';
    protected $description = 'Sync name/email duplicates in students and lecturers from users';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $this->info('Syncing students…');
        $students = Student::with('user')->get();
        foreach ($students as $s) {
            $name = optional($s->user)->name;
            $email = optional($s->user)->email;
            if (Schema::hasColumn('students', 'name') && $name && $s->getOriginal('name') !== $name) {
                $this->line("Student #{$s->id}: name '{$s->getOriginal('name')}' -> '{$name}'");
                if (!$dry) {
                    $s->forceFill(['name' => $name])->saveQuietly();
                }
            }
            if (Schema::hasColumn('students', 'email') && $email && $s->getOriginal('email') !== $email) {
                $this->line("Student #{$s->id}: email '{$s->getOriginal('email')}' -> '{$email}'");
                if (!$dry) {
                    $s->forceFill(['email' => $email])->saveQuietly();
                }
            }
        }

        $this->info('Syncing lecturers…');
        $lecturers = Lecturer::with('user')->get();
        foreach ($lecturers as $l) {
            $name = optional($l->user)->name;
            $email = optional($l->user)->email;
            if (Schema::hasColumn('lecturers', 'name') && $name && $l->getOriginal('name') !== $name) {
                $this->line("Lecturer #{$l->id}: name '{$l->getOriginal('name')}' -> '{$name}'");
                if (!$dry) {
                    $l->forceFill(['name' => $name])->saveQuietly();
                }
            }
            if (Schema::hasColumn('lecturers', 'email') && $email && $l->getOriginal('email') !== $email) {
                $this->line("Lecturer #{$l->id}: email '{$l->getOriginal('email')}' -> '{$email}'");
                if (!$dry) {
                    $l->forceFill(['email' => $email])->saveQuietly();
                }
            }
        }

        $this->info($dry ? 'Dry-run complete.' : 'Sync complete.');
        return self::SUCCESS;
    }
}