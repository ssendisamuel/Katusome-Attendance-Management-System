<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('series_id')->constrained('schedule_series')->cascadeOnDelete();
            $table->json('generated_dates')->nullable();
            $table->boolean('overwrite')->default(false);
            $table->boolean('skip_overlaps')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_audits');
    }
};