<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('venues')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->nullable();
            $table->timestamps();
        });

        // Add venue_id to schedules and schedule_series
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('location')->constrained('venues')->nullOnDelete();
        });

        Schema::table('schedule_series', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('location')->constrained('venues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_series', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
        Schema::dropIfExists('venues');
    }
};
