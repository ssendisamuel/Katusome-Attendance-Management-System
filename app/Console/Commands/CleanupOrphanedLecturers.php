<?php

namespace App\Console\Commands;

use App\Models\Lecturer;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Console\Command;

class CleanupOrphanedLecturers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-lecturers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove lecturer records for users who no longer have the lecturer role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for orphaned lecturer records...');

        $orphanedCount = 0;

        // Get all lecturer records
        $lecturers = Lecturer::with('user')->get();

        foreach ($lecturers as $lecturer) {
            $user = $lecturer->user;

            if (!$user) {
                $this->warn("Lecturer record #{$lecturer->id} has no associated user. Deleting...");
                $lecturer->delete();
                $orphanedCount++;
                continue;
            }

            // Check if user has lecturer role (either as primary or in user_roles)
            $hasLecturerRole = $user->role === 'lecturer' ||
                UserRole::where('user_id', $user->id)
                    ->where('role', 'lecturer')
                    ->where('is_active', true)
                    ->exists();

            if (!$hasLecturerRole) {
                $this->warn("User {$user->name} ({$user->email}) has lecturer record but no lecturer role. Deleting...");
                $lecturer->delete();
                $orphanedCount++;
            }
        }

        if ($orphanedCount > 0) {
            $this->info("Cleaned up {$orphanedCount} orphaned lecturer record(s).");
        } else {
            $this->info('No orphaned lecturer records found.');
        }

        return 0;
    }
}
