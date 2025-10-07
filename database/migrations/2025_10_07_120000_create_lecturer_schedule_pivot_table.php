<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('lecturers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['schedule_id', 'lecturer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_schedule');
    }
};