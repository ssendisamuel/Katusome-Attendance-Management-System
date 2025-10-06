<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Keep legacy duplicated columns in sync if they still exist
        if ($user->relationLoaded('student')) {
            $student = $user->student;
        } else {
            $student = $user->student; // lazy load
        }

        if ($student) {
            $updates = [];
            if (Schema::hasColumn('students', 'name')) {
                $newName = $user->name;
                if ($student->getOriginal('name') !== $newName) {
                    $updates['name'] = $newName;
                }
            }
            if (Schema::hasColumn('students', 'email')) {
                $newEmail = $user->email;
                if ($student->getOriginal('email') !== $newEmail) {
                    $updates['email'] = $newEmail;
                }
            }
            if (!empty($updates)) {
                // Write quietly to avoid unnecessary event noise
                $student->forceFill($updates)->saveQuietly();
            }
        }

        if ($user->relationLoaded('lecturer')) {
            $lecturer = $user->lecturer;
        } else {
            $lecturer = $user->lecturer; // lazy load
        }

        if ($lecturer) {
            $updates = [];
            if (Schema::hasColumn('lecturers', 'name')) {
                $newName = $user->name;
                if ($lecturer->getOriginal('name') !== $newName) {
                    $updates['name'] = $newName;
                }
            }
            if (Schema::hasColumn('lecturers', 'email')) {
                $newEmail = $user->email;
                if ($lecturer->getOriginal('email') !== $newEmail) {
                    $updates['email'] = $newEmail;
                }
            }
            if (!empty($updates)) {
                $lecturer->forceFill($updates)->saveQuietly();
            }
        }
    }
}