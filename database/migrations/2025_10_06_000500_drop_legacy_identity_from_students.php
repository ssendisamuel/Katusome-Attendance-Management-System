<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy identity columns if they still exist
        if (Schema::hasColumn('students', 'name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
        if (Schema::hasColumn('students', 'email')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }

    public function down(): void
    {
        // Re-add legacy columns as nullable strings (without unique constraints)
        if (!Schema::hasColumn('students', 'name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('name')->nullable();
            });
        }
        if (!Schema::hasColumn('students', 'email')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('email')->nullable();
            });
        }
    }
};