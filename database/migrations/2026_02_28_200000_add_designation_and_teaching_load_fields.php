<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add designation to lecturers
        Schema::table('lecturers', function (Blueprint $table) {
            if (!Schema::hasColumn('lecturers', 'designation')) {
                $table->string('designation', 50)->nullable()->after('title');
            }
        });

        // Expand course_lecturer pivot with teaching load fields
        Schema::table('course_lecturer', function (Blueprint $table) {
            if (!Schema::hasColumn('course_lecturer', 'academic_year')) {
                $table->string('academic_year', 20)->nullable()->after('lecturer_id');
            }
            if (!Schema::hasColumn('course_lecturer', 'semester')) {
                $table->tinyInteger('semester')->nullable()->after('academic_year');
            }
            if (!Schema::hasColumn('course_lecturer', 'program_code')) {
                $table->string('program_code', 20)->nullable()->after('semester');
            }
            if (!Schema::hasColumn('course_lecturer', 'study_group')) {
                $table->string('study_group', 10)->nullable()->after('program_code');
            }
            if (!Schema::hasColumn('course_lecturer', 'year_of_study')) {
                $table->tinyInteger('year_of_study')->nullable()->after('study_group');
            }
            if (!Schema::hasColumn('course_lecturer', 'hours_per_week')) {
                $table->decimal('hours_per_week', 4, 1)->nullable()->after('year_of_study');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropColumn('designation');
        });
        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'semester', 'program_code', 'study_group', 'year_of_study', 'hours_per_week']);
        });
    }
};
