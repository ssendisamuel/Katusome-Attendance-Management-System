<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('accuracy', 8, 2)->nullable()->after('lng');
            $table->integer('distance_meters')->nullable()->after('accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['accuracy', 'distance_meters']);
        });
    }
};