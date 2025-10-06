<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lecturers', 'name')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
        if (Schema::hasColumn('lecturers', 'email')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('lecturers', 'name')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->string('name')->nullable();
            });
        }
        if (!Schema::hasColumn('lecturers', 'email')) {
            Schema::table('lecturers', function (Blueprint $table) {
                $table->string('email')->nullable();
            });
        }
    }
};