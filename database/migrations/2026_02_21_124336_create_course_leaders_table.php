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
        Schema::create('course_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->integer('year_of_study');
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // A student can only be a leader once for a specific program/year/group combo
            $table->unique(['student_id', 'program_id', 'year_of_study', 'group_id'], 'course_leader_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_leaders');
    }
};
