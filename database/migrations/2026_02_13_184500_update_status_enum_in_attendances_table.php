<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the column to include 'excused'
        // leveraging raw SQL for ENUM modification to be safe and explicit
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original 3 statuses
        // WARNING: This will truncate 'excused' values if they exist, possibly to '' or error.
        // For safety in dev environments we can permit it, but ideally we'd map them back to something else.
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'absent', 'late') NOT NULL DEFAULT 'present'");
    }
};
