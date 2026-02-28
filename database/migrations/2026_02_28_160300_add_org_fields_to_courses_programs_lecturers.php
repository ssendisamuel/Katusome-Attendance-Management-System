<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('description')->constrained()->nullOnDelete();
            $table->string('abbreviation')->nullable()->after('name');
            if (!Schema::hasColumn('courses', 'credit_units')) {
                $table->unsignedTinyInteger('credit_units')->nullable()->after('abbreviation');
            }
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('duration_years')->default(3)->after('faculty_id');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('title')->nullable()->after('department_id');
            $table->string('specialization')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['abbreviation']);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('faculty_id');
            $table->dropColumn('duration_years');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['title', 'specialization']);
        });
    }
};
