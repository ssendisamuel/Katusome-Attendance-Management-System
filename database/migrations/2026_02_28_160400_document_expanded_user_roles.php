<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The role column is already a string with default 'student'.
        // No schema change needed — we just allow new role values.
        // Existing values (admin, student, lecturer) remain valid.
        // New values: super_admin, principal, registrar, campus_chief, qa_director, dean, hod
    }

    public function down(): void
    {
        // Nothing to revert — no schema change was made.
    }
};
