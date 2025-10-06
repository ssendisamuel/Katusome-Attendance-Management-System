<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7)->default(0.332931);
            $table->decimal('longitude', 11, 7)->default(32.621927);
            $table->unsignedInteger('radius_meters')->default(150);
            $table->timestamps();
        });

        // Seed a single default row
        DB::table('location_settings')->insert([
            'latitude' => 0.332931,
            'longitude' => 32.621927,
            'radius_meters' => 150,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_settings');
    }
};