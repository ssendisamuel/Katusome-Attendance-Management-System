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
        Schema::table('course_program', function (Blueprint $table) {
            $table->integer('credit_units')->default(3)->after('course_id');
            $table->enum('course_type', ['Core', 'Elective', 'Audit'])->default('Core')->after('credit_units');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_program', function (Blueprint $table) {
            $table->dropColumn(['credit_units', 'course_type']);
        });
    }
};
