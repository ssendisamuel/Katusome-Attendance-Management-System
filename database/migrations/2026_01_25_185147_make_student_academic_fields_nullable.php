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
        Schema::table('students', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['program_id']);
            $table->dropForeign(['group_id']);

            // Make fields nullable
            $table->foreignId('program_id')->nullable()->change();
            $table->foreignId('group_id')->nullable()->change();
            $table->integer('year_of_study')->nullable()->change();

            // Re-add foreign keys with null on delete
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop nullable foreign keys
            $table->dropForeign(['program_id']);
            $table->dropForeign(['group_id']);

            // Make fields required again (this may fail if nulls exist)
            $table->foreignId('program_id')->nullable(false)->change();
            $table->foreignId('group_id')->nullable(false)->change();
            $table->integer('year_of_study')->nullable(false)->change();

            // Re-add foreign keys with cascade
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });
    }
};
