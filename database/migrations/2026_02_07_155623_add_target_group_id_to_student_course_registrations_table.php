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
        Schema::table('student_course_registrations', function (Blueprint $table) {
            $table->foreignId('target_group_id')->nullable()->after('course_id')->constrained('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_course_registrations', function (Blueprint $table) {
            $table->dropForeign(['target_group_id']);
            $table->dropColumn('target_group_id');
        });
    }
};
