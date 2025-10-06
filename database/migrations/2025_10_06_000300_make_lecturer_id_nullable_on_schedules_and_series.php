<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // schedule_series: drop FK, make column nullable, re-add FK
        Schema::table('schedule_series', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });
        Schema::table('schedule_series', function (Blueprint $table) {
            $table->unsignedBigInteger('lecturer_id')->nullable()->change();
            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
        });

        // schedules: drop FK, make column nullable, re-add FK
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('lecturer_id')->nullable()->change();
            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // schedule_series: revert to NOT NULL
        Schema::table('schedule_series', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });
        Schema::table('schedule_series', function (Blueprint $table) {
            $table->unsignedBigInteger('lecturer_id')->nullable(false)->change();
            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
        });

        // schedules: revert to NOT NULL
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('lecturer_id')->nullable(false)->change();
            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
        });
    }
};