<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Map: faculty_code => [ [dept_code, dept_name], ... ]
        $data = [
            'FCO' => [
                ['ACC', 'Accounting'],
                ['FIN', 'Finance'],
                ['BLAW', 'Business Law'],
                ['AT', 'Auditing and Taxation'],
            ],
            'FCI' => [
                ['CSE', 'Computer Science and Engineering'],
                ['IS', 'Information Systems'],
                ['ACIT', 'Applied Computing and Information Technology'],
            ],
            'FOM' => [
                ['MGT', 'Management'],
                ['HRM', 'Human Resource Management'],
                ['LG', 'Leadership and Governance'],
            ],
            'FESBM' => [
                ['ENT', 'Entrepreneurship and Innovation'],
                ['PSBM', 'Project & Small Business Management'],
                ['SBM', 'Small Business Management'],
            ],
            'FMIB' => [
                ['MKT', 'Marketing'],
                ['IBT', 'International Business'],
            ],
            'FPLM' => [
                ['PSCM', 'Procurement & Supply Chain Management'],
                ['TL', 'Transport & Logistics Management'],
            ],
            'FEEMS' => [
                ['ECON', 'Energy & Economics'],
                ['ASM', 'Applied Statistics and Management Science'],
            ],
            'FBA' => [
                ['BA', 'Business Administration'],
                ['BC', 'Business Communication'],
            ],
            'FTHL' => [
                ['TH', 'Tourism & Hospitality'],
                ['LE', 'Leisure and Events'],
                ['LAN', 'Languages'],
            ],
        ];

        foreach ($data as $facultyCode => $departments) {
            $faculty = Faculty::where('code', $facultyCode)->first();
            if (!$faculty) continue;

            foreach ($departments as [$code, $name]) {
                Department::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'faculty_id' => $faculty->id,
                    ]
                );
            }
        }
    }
}
