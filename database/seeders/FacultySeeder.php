<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $mainCampus = Campus::where('code', 'MAIN')->first();
        $campusId = $mainCampus?->id;

        $faculties = [
            ['code' => 'FVDE', 'name' => 'Faculty of Vocational & Distance Education'],
            ['code' => 'FTHL', 'name' => 'Faculty of Tourism, Hospitality & Languages'],
            ['code' => 'FSCE', 'name' => 'Faculty of Science Education'],
            ['code' => 'FPLM', 'name' => 'Faculty of Procurement & Logistics Management'],
            ['code' => 'FMIB', 'name' => 'Faculty of Marketing & International Business'],
            ['code' => 'FOM',  'name' => 'Faculty of Management'],
            ['code' => 'FGSR', 'name' => 'Faculty of Graduate Studies & Research'],
            ['code' => 'FESBM','name' => 'Faculty of Entrepreneurship & Small Business Management'],
            ['code' => 'FEEMS','name' => 'Faculty of Economics, Energy & Management Science'],
            ['code' => 'FCI',  'name' => 'Faculty of Computing & Informatics'],
            ['code' => 'FCO',  'name' => 'Faculty of Commerce'],
            ['code' => 'FBA',  'name' => 'Faculty of Business Administration'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::updateOrCreate(
                ['code' => $faculty['code']],
                array_merge($faculty, ['campus_id' => $campusId])
            );
        }
    }
}
