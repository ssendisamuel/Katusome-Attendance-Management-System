<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Make reg_no NOT NULL (required)
            $table->string('reg_no', 50)->nullable(false)->change();

            // Add unique constraint if not exists
            // Note: unique constraint should already exist from original migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Make reg_no nullable again
            $table->string('reg_no', 50)->nullable()->change();
        });
    }
};
