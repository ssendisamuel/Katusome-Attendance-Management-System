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
        Schema::create('student_course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_semester_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['retake', 'missed', 'extra'])->default('retake');
            $table->timestamps();

            // Prevent duplicate registration for same course in same semester
            $table->unique(['student_id', 'course_id', 'academic_semester_id'], 'student_course_sem_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_course_registrations');
    }
};
