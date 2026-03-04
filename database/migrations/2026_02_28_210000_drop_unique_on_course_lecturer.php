<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL won't let us drop a unique index that's used by a FK.
        // So we drop the FK, drop the unique index, then re-add the FK with a regular index.
        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['lecturer_id']);
        });

        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'lecturer_id']);
        });

        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('lecturer_id')->references('id')->on('lecturers')->cascadeOnDelete();
            $table->index(['course_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('course_lecturer', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'lecturer_id']);
            $table->unique(['course_id', 'lecturer_id']);
        });
    }
};
