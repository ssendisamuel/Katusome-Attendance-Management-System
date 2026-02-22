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
        Schema::table('schedules', function (Blueprint $table) {
            $table->enum('attendance_status', ['scheduled', 'open', 'late', 'closed'])->default('scheduled')->after('end_at');
            $table->timestamp('attendance_open_at')->nullable()->after('attendance_status');
            $table->timestamp('late_at')->nullable()->after('attendance_open_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            //
        });
    }
};
