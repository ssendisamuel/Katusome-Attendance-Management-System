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
            $table->boolean('requires_clock_out')->default(false)->after('location');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('clock_in_time')->nullable()->after('status');
            $table->timestamp('clock_out_time')->nullable()->after('clock_in_time');
            $table->decimal('clock_out_lat', 10, 7)->nullable()->after('lat');
            $table->decimal('clock_out_lng', 10, 7)->nullable()->after('lng');
            $table->boolean('is_auto_clocked_out')->default(false)->after('selfie_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('requires_clock_out');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_time',
                'clock_out_time',
                'clock_out_lat',
                'clock_out_lng',
                'is_auto_clocked_out'
            ]);
        });
    }
};
