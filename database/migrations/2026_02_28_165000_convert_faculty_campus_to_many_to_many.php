<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create('campus_faculty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campus_id', 'faculty_id']);
        });

        // 2. Migrate existing campus_id data into the pivot
        $faculties = DB::table('faculties')->whereNotNull('campus_id')->get();
        foreach ($faculties as $fac) {
            DB::table('campus_faculty')->insert([
                'campus_id' => $fac->campus_id,
                'faculty_id' => $fac->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop old campus_id column from faculties
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
        });
    }

    public function down(): void
    {
        // Re-add campus_id column
        Schema::table('faculties', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        // Copy first campus back from pivot
        $pivots = DB::table('campus_faculty')->get();
        foreach ($pivots as $pivot) {
            DB::table('faculties')->where('id', $pivot->faculty_id)->update([
                'campus_id' => $pivot->campus_id,
            ]);
        }

        Schema::dropIfExists('campus_faculty');
    }
};
