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
        Schema::table('groups', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['program_id']);

            // Make program_id nullable
            $table->foreignId('program_id')->nullable()->change();

            // Re-add foreign key constraint (nullable)
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
        });

        // Seed simple letter groups A-G (without program association)
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($letters as $letter) {
            \App\Models\Group::firstOrCreate(
                ['name' => $letter, 'program_id' => null]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove seeded letter groups
        \App\Models\Group::whereIn('name', ['A', 'B', 'C', 'D', 'E', 'F', 'G'])
            ->whereNull('program_id')
            ->delete();

        Schema::table('groups', function (Blueprint $table) {
            // Drop the nullable foreign key
            $table->dropForeign(['program_id']);

            // Make program_id required again
            $table->foreignId('program_id')->nullable(false)->change();

            // Re-add foreign key with cascade
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }
};
