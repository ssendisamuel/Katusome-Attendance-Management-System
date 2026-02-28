<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // [code, name, abbreviation, credit_units, department_code]
        $courses = [
            // BBC Year 1
            ['BUC1222', 'Computer Networks', 'CN', 4, 'CSE'],
            ['MGT1105', 'Business Communication Skills', 'BCS', 3, 'BC'],
            ['BUC1223', 'Programming Theory and Problem Solving', 'PTP', 5, 'CSE'],
            ['BUC1224', 'Database Design and Programming', 'DDP', 5, 'IS'],
            ['BUC1229', 'Systems Analysis and Design', 'SAAD', 4, 'IS'],
            // BBC Year 2
            ['BUC2230', 'Business Intelligence and Data Warehousing', 'BIDW', 4, 'IS'],
            ['BUC3111', 'Project Research Methods', 'PRM', 3, 'ACIT'],
            ['BUC2231', 'Web Application Development', 'WAP', 5, 'IS'],
            ['BUC2232', 'Business Application Programming', 'BAP', 5, 'CSE'],
            ['BUC2233', 'Software Engineering for Business', 'SEB', 4, 'CSE'],
            ['UFA2301', 'Field Attachment', 'FA', 5, null],
            // BBC Year 3
            ['BAD3210', 'Strategic Management', 'SM', 4, 'MGT'],
            ['BEM3221', 'Entrepreneurial Mindset and Action', 'EMA', 3, 'ENT'],
            ['BUC3216', 'Computing Ethics', 'CET', 3, 'ACIT'],
            ['BUC3217', 'IT Project Management', 'ITP', 4, 'IS'],
            ['BUC3218', 'Advanced Routing and Switching', 'ARS', 4, 'CSE'],
            ['BUC3219', 'Web Server Administration', 'WSA', 4, 'CSE'],
            ['BUC3220', 'Software Testing and Documentation', 'STD', 4, 'CSE'],
            ['BUC3221', 'Advanced Mobile Application Development', 'AMAP', 4, 'CSE'],
            // BOIM Year 1
            ['BUC1215', 'Office Administration and Management', 'OAM', 4, 'ACIT'],
            ['BUC1219', 'Internet & Emerging Technologies', 'IET', 4, 'IS'],
            ['BUC1220', 'Computer Key Board Skills I', 'CKBS I', 4, 'ACIT'],
            ['BUC1218', 'Shorthand I', 'SH I', 4, 'ACIT'],
            ['FIN1214', 'Principles of Economics', 'PECO', 4, 'ECON'],
            // BOIM Year 2
            ['BUC2225', 'Computer Key Board Skills III', 'CKBS III', 4, 'ACIT'],
            ['BUC2226', 'Document Production', 'DP', 4, 'ACIT'],
            ['BBC2204', 'E-Business and Web Design', 'EWEB', 4, 'IS'],
            ['BBC2205', 'Computerized Data Analysis', 'CDA', 3, 'CSE'],
            ['UFA2320', 'Field Attachment', 'FA', 5, null],
            ['HRM2207', 'Principles of Organization Behaviour', 'POB', 3, 'HRM'],
            ['BUC2227', 'Shorthand III', 'SH III', 3, 'ACIT'],
            ['BUC2228', 'Management Information Systems', 'MIS', 3, 'IS'],
            ['BUC2229', 'Enterprise Information Resource Management', 'EIRM', 3, 'IS'],
            // BOIM Year 3
            ['BUC3226', 'Computing Ethics', 'CET', 4, 'ACIT'],
            ['BUC3213', 'IT Project Development', 'ITPD', 5, 'IS'],
            ['BUC3228', 'Professional Speaking and Public Relations', 'PPR', 4, 'BC'],
            ['BUC3230', 'Enterprise Network Administration & Management', 'ENAM', 4, 'CSE'],
        ];

        // Build department lookup
        $deptLookup = Department::pluck('id', 'code')->toArray();

        foreach ($courses as [$code, $name, $abbr, $credits, $deptCode]) {
            Course::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'abbreviation' => $abbr,
                    'credit_units' => $credits,
                    'department_id' => $deptCode ? ($deptLookup[$deptCode] ?? null) : null,
                ]
            );
        }
    }
}
