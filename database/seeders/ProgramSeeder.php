<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Map: faculty_code => [ [program_code, program_name, duration_years] ]
        $data = [
            // Faculty of Graduate Studies and Research
            'FGSR' => [
                ['MBA',   'Master of Business Administration', 2],
                ['MIB',   'Master of International Business', 2],
                ['MSAF',  'Master of Science in Accounting and Finance', 2],
                ['MBI',   'Master of Science in Banking and Investment', 2],
                ['MEI',   'Master of Entrepreneurship', 2],
                ['EPM',   'Master of Arts in Economic Policy and Management', 2],
                ['HTM',   'Master of Hospitality and Tourism Management', 2],
                ['MLG',   'Master of Science in Leadership and Governance', 2],
                ['MHRM',  'Master of Human Resource Management', 2],
                ['MOBP',  'Master of Business Psychology', 2],
                ['MKT-M', 'Master of Science in Marketing', 2],
                ['MPS',   'Master of Procurement and Supply Chain Management', 2],
                ['EEG',   'Master of Energy Economics and Governance', 2],
                ['PEG',   'Doctor of Philosophy in Energy Economics and Governance', 3],
                ['GDBE',  'Postgraduate Diploma in Business Education', 1],
                ['PGDBD', 'Postgraduate Diploma in Business Intelligence and Data Analytics', 1],
            ],
            // Faculty of Commerce
            'FCO' => [
                ['COM',  'Bachelor of Commerce', 3],
                ['BSA',  'Bachelor of Science in Accounting', 3],
                ['BSF',  'Bachelor of Science in Finance', 3],
                ['BRE',  'Bachelor of Real Estate Management', 3],
            ],
            // Faculty of Business Administration
            'FBA' => [
                ['BBA', 'Bachelor of Business Administration', 3],
            ],
            // Faculty of Entrepreneurship & Small Business Management
            'FESBM' => [
                ['BEM', 'Bachelor of Entrepreneurship', 3],
            ],
            // Faculty of Computing & Informatics
            'FCI' => [
                ['BBC',  'Bachelor of Business Computing', 3],
                ['BOIM', 'Bachelor of Office and Information Management', 3],
            ],
            // Faculty of Economics, Energy & Management Science
            'FEEMS' => [
                ['BAE', 'Bachelor of Arts in Economics', 3],
                ['BBS', 'Bachelor of Business Statistics', 3],
            ],
            // Faculty of Procurement & Logistics Management
            'FPLM' => [
                ['BTLM', 'Bachelor of Transport and Logistics Management', 3],
                ['BPSM', 'Bachelor of Procurement and Supply Chain Management', 3],
            ],
            // Faculty of Management
            'FOM' => [
                ['BHRM', 'Bachelor of Human Resource Management', 3],
                ['BLG',  'Bachelor of Leadership and Governance', 3],
            ],
            // Faculty of Marketing & International Business
            'FMIB' => [
                ['BIB', 'Bachelor of International Business', 3],
                ['BOM', 'Bachelor of Marketing', 3],
            ],
            // Faculty of Tourism, Hospitality & Languages
            'FTHL' => [
                ['BTTM',  'Bachelor of Travel and Tourism Management', 3],
                ['BLEHM', 'Bachelor of Leisure, Events and Hospitality Management', 3],
            ],
            // Faculty of Vocational & Distance Education
            'FVDE' => [
                ['DBA',  'Diploma in Business Administration', 2],
                ['DES',  'Diploma in Entrepreneurship & Small Business Management', 2],
                ['DCS',  'Diploma in Computer Science', 2],
                ['DCH',  'Diploma in Catering and Hotel Operations', 2],
                ['DPS',  'Diploma in Procurement and Supply Chain Management', 2],
                ['DAF',  'Diploma in Accounting and Finance', 2],
                ['DBD',  'Diploma in Business Intelligence and Data Analytics', 2],
                ['DBC',  'Diploma in Business Computing', 2],
                ['HEC',  'Higher Education Certificate in Business Studies', 1],
                ['NCBA', 'National Certificate in Business Administration', 1],
            ],
        ];

        foreach ($data as $facultyCode => $programs) {
            $faculty = Faculty::where('code', $facultyCode)->first();
            if (!$faculty) continue;

            foreach ($programs as [$code, $name, $duration]) {
                Program::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'faculty_id' => $faculty->id,
                        'duration_years' => $duration,
                    ]
                );
            }
        }
    }
}
