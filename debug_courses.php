<?php

use Illuminate\Support\Facades\DB;
use App\Models\Course;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentId = 519;
$student = \App\Models\Student::find($studentId);

echo "Checking courses for Program " . $student->program_id . ", Year " . $student->year_of_study . "\n";

$p2y3 = DB::table('course_program')
    ->where('program_id', $student->program_id)
    ->where('year_of_study', $student->year_of_study)
    ->get();

echo "--- Program " . $student->program_id . " Year " . $student->year_of_study . " Courses ---\n";
foreach($p2y3 as $cp) {
    $c = Course::find($cp->course_id);
    echo $c->name . " (ID: " . $c->id . ", Sem: " . $cp->semester_offered . ")\n";
}

$p2y2 = DB::table('course_program')
    ->where('program_id', $student->program_id)
    ->where('year_of_study', 2)
    ->get();
echo "--- Program " . $student->program_id . " Year 2 Courses ---\n";
foreach($p2y2 as $cp) {
    try {
        $c = Course::find($cp->course_id);
        if ($c) {
             echo $c->name . " (ID: " . $c->id . ", Sem: " . $cp->semester_offered . ")\n";
        }
    } catch (\Exception $e) {
        echo "Error finding course " . $cp->course_id . "\n";
    }
}
