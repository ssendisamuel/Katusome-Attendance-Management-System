<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->after('group_id')->constrained()->nullOnDelete();
        });

        // Backfill: derive campus from program→faculty→campus_faculty pivot (first campus per faculty)
        DB::statement("
            UPDATE student_enrollments se
            JOIN programs p ON p.id = se.program_id
            JOIN campus_faculty cf ON cf.faculty_id = p.faculty_id
            SET se.campus_id = cf.campus_id
            WHERE se.campus_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
