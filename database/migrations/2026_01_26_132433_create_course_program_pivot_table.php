<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the new pivot table
        Schema::create('course_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('year_of_study')->comment('Year of study context for this program (1-4)');
            $table->enum('semester_offered', ['Semester 1', 'Semester 2', 'Both']);
            $table->timestamps();

            // Prevent duplicate assignment of same course to same program
            $table->unique(['program_id', 'course_id']);
        });

        // 2. Migrate existing data from courses table to pivot table
        $courses = DB::table('courses')->whereNotNull('program_id')->get();
        foreach ($courses as $course) {
            DB::table('course_program')->insert([
                'program_id' => $course->program_id,
                'course_id' => $course->id,
                'year_of_study' => $course->year_of_study ?? 1, // Default to 1 if null (though we made it required recently)
                'semester_offered' => $course->semester_offered ?? 'Both',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Remove columns from courses table
        Schema::table('courses', function (Blueprint $table) {
            // Drop foreign key first using the standard naming convention or array syntax
            // Laravel usually names it table_column_foreign
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'year_of_study', 'semester_offered']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Add columns back to courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('year_of_study')->nullable();
            $table->enum('semester_offered', ['Semester 1', 'Semester 2', 'Both'])->nullable();
        });

        // 2. Restore data (best effort: take the first program assignment found)
        $assignments = DB::table('course_program')->get();
        foreach ($assignments as $assignment) {
            // Only update if course still exists and hasn't been updated yet
            $course = DB::table('courses')->where('id', $assignment->course_id)->first();
            if ($course && is_null($course->program_id)) {
                DB::table('courses')->where('id', $assignment->course_id)->update([
                    'program_id' => $assignment->program_id,
                    'year_of_study' => $assignment->year_of_study,
                    'semester_offered' => $assignment->semester_offered,
                ]);
            }
        }

        // 3. Drop pivot table
        Schema::dropIfExists('course_program');
    }
};
