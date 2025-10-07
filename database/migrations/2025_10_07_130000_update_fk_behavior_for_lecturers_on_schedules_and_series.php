<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // schedules: lecturer_id should be nullable and set null on lecturer delete
        if (Schema::hasTable('schedules')) {
            // Drop existing foreign key if present
            Schema::table('schedules', function (Blueprint $table) {
                try { $table->dropForeign(['lecturer_id']); } catch (\Throwable $e) {}
            });
            Schema::table('schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('lecturer_id')->nullable()->change();
                $table->foreign('lecturer_id')->references('id')->on('lecturers')->nullOnDelete();
            });
        }

        // schedule_series: lecturer_id should be nullable and set null on lecturer delete
        if (Schema::hasTable('schedule_series')) {
            Schema::table('schedule_series', function (Blueprint $table) {
                try { $table->dropForeign(['lecturer_id']); } catch (\Throwable $e) {}
            });
            Schema::table('schedule_series', function (Blueprint $table) {
                $table->unsignedBigInteger('lecturer_id')->nullable()->change();
                $table->foreign('lecturer_id')->references('id')->on('lecturers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Revert to cascade on delete (if needed)
        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                try { $table->dropForeign(['lecturer_id']); } catch (\Throwable $e) {}
                $table->foreign('lecturer_id')->references('id')->on('lecturers')->cascadeOnDelete();
            });
        }
        if (Schema::hasTable('schedule_series')) {
            Schema::table('schedule_series', function (Blueprint $table) {
                try { $table->dropForeign(['lecturer_id']); } catch (\Throwable $e) {}
                $table->foreign('lecturer_id')->references('id')->on('lecturers')->cascadeOnDelete();
            });
        }
    }
};