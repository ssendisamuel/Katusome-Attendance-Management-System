<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change latitude/longitude to DOUBLE to allow variable decimal precision
        DB::statement('ALTER TABLE location_settings MODIFY latitude DOUBLE NOT NULL DEFAULT 0.332931');
        DB::statement('ALTER TABLE location_settings MODIFY longitude DOUBLE NOT NULL DEFAULT 32.621927');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to DECIMAL with fixed scale
        DB::statement('ALTER TABLE location_settings MODIFY latitude DECIMAL(10,7) NOT NULL DEFAULT 0.332931');
        DB::statement('ALTER TABLE location_settings MODIFY longitude DECIMAL(11,7) NOT NULL DEFAULT 32.621927');
    }
};