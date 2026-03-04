<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\LecturerController;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingLoadSeeder extends Seeder
{
    public function run(): void
    {
        // Build lookup: course name (lowercase) -> course id
        $courses = Course::all();
        $courseByName = [];
        $courseByCode = [];
        foreach ($courses as $c) {
            $courseByName[strtolower(trim($c->name))] = $c->id;
            $courseByCode[strtolower(trim($c->code))] = $c->id;
        }

        // Build lookup: lecturer email -> lecturer id
        $lecturerByEmail = [];
        $allLecturers = Lecturer::with('user')->get();
        foreach ($allLecturers as $l) {
            $email = optional($l->user)->email;
            if ($email) $lecturerByEmail[strtolower($email)] = $l->id;
        }

        $year = '2025/2026';
        $semester = 2;
        $inserted = 0;
        $skipped = 0;

        // format: [lecturer_name, course_name, program_code, group, year_of_study, hrs_per_week]
        $assignments = [
            // ═══ ACIT ═══
            ['Musa Moya', 'Computerised Data Analysis', 'BOIM', 'A', 2, 3],
            ['Musa Moya', 'Computerised Data Analysis', 'BOIM', 'B', 2, 3],
            ['Musa Moya', 'IT Project Development', 'BOIM', 'A', 3, 5],
            ['Robert Kyeyune', 'Database Design and Programming', 'BBC', 'A2', 1, 3],
            ['Robert Kyeyune', 'Database Design and Programming', 'BBC', 'A2', 1, 2],
            ['Robert Kyeyune', 'IT Project Development', 'BOIM', 'A', 3, 5],
            ['Robert Kyeyune', 'Project Research Methods', 'BBC', 'B', 2, 4],
            ['Samali V. Mlay', 'IT Project Management', 'BBC', 'C', 3, 4],
            ['Samali V. Mlay', 'IT Project Management', 'BOIM', 'B', 3, 4],
            ['Samali V. Mlay', 'Office Administration and Management', 'BOIM', 'A', 2, 4],
            ['Abdul Male Ssentumbwe', 'Computer Networks', 'BBC', 'A', 1, 4],
            ['Abdul Male Ssentumbwe', 'Advanced Routing and Switching', 'BBC', 'C', 3, 4],
            ['Abdul Male Ssentumbwe', 'Advanced Routing and Switching', 'BBC', 'B', 3, 4],
            ['Sumaya M. Kagoya', 'Internet & Emerging Technologies', 'BENT', 'A', 1, 5],
            ['Sumaya M. Kagoya', 'Internet & Emerging Technologies', 'BENT', 'B', 1, 5],
            ['Robinah Nabafu', 'Systems Analysis and Design', 'BBC', 'C', 1, 4],
            ['Robinah Nabafu', 'Computing Ethics', 'BBC', 'A', 3, 3],
            ['Robinah Nabafu', 'Computing Ethics', 'BOIM', 'A', 3, 4],
            ['Robinah Nabafu', 'Project Research Methods', 'BBC', 'C', 2, 4],
            ['Abdallah Ibrahim Nyero', 'Web Server Administration', 'BBC', 'B', 3, 4],
            ['Abdallah Ibrahim Nyero', 'Web Server Administration', 'BBC', 'C', 3, 4],
            ['Abdallah Ibrahim Nyero', 'Web Server Administration', 'BBC', 'A', 3, 4],
            ['Abdul Ddamba', 'Programming Theory and Problem Solving', 'BBC', 'C', 1, 5],
            ['Abdul Ddamba', 'Programming Theory and Problem Solving', 'BBC', 'B', 1, 5],
            ['Catherine Nyesiga', 'IT Project Management', 'BOIM', 'A', 3, 4],
            ['Catherine Nyesiga', 'IT Project Management', 'BBC', 'A', 3, 4],
            ['Catherine Nyesiga', 'Project Research Methods', 'BBC', 'A', 2, 4],
            ['Elizabeth Asianzu Ezati', 'Shorthand I', 'BOIM', 'A', 1, 4],
            ['Elizabeth Asianzu Ezati', 'Computer Key Board Skills I', 'BOIM', 'B', 1, 4],
            ['Elizabeth Asianzu Ezati', 'Computer Key Board Skills III', 'BOIM', 'A', 2, 4],
            ['Elizabeth Asianzu Ezati', 'Shorthand III', 'BOIM', 'B', 2, 3],
            ['Fatinah Nakabonge', 'Computing Ethics', 'BOIM', 'B', 3, 4],
            ['Fatinah Nakabonge', 'Computing Ethics', 'BBC', 'B', 3, 4],
            ['Ismael Kato', 'Project Research Methods', 'BBC', 'A', 2, 4],
            ['Ismael Kato', 'IT Project Development', 'BOIM', 'B', 3, 5],
            ['Ismael Kato', 'Computing Ethics', 'BBC', 'B', 3, 3],
            ['Josephine Namataba', 'Internet & Emerging Technologies', 'BPSCM', 'A', 1, 5],
            ['Josephine Namataba', 'Internet & Emerging Technologies', 'BPSCM', 'C', 1, 5],
            ['Moses Serugo', 'Computing Ethics', 'BBC', 'C', 3, 4],
            ['Moses Serugo', 'Computing Ethics', 'BOIM', 'B', 3, 4],
            ['Philper Irene Tusubira', 'Systems Analysis and Design', 'BBC', 'B', 1, 4],
            ['Stella Eva Nakalema', 'Office Administration and Management', 'BOIM', 'A', 1, 4],
            ['Stella Eva Nakalema', 'IT Project Management', 'BBC', 'B', 3, 4],
            ['Ali Balunywa', 'E-Business and Web Design', 'BOIM', 'A', 2, 4],
            ['Ali Balunywa', 'E-Business and Web Design', 'BOIM', 'B', 2, 4],
            ['Ali Balunywa', 'E-Business and Web Design', 'BBA', 'A1', 2, 2],
            ['Ali Balunywa', 'E-Business and Web Design', 'BBA', 'A1', 2, 2],
            ['Aisha Namome Watsemba', 'IT Project Management', 'BBC', 'A', 3, 4],
            ['Aisha Namome Watsemba', 'Enterprise Information Resource Management', 'BOIM', 'B', 2, 3],
            ['Afulah Namatovu', 'Internet & Emerging Technologies', 'BOIM', 'B', 1, 4],
            ['Aisha Mwesigye', 'Computing Ethics', 'BBC', 'C', 3, 4],
            ['Benedict Ogot', 'Enterprise Network Administration & Management', 'BOIM', 'A', 3, 4],
            ['Benedict Ogot', 'Enterprise Network Administration & Management', 'BOIM', 'B', 3, 4],
            ['Benedict Ogot', 'Computer Networks', 'BBC', 'C', 1, 4],
            ['Caroline Atuhaire', 'Business Intelligence and Data Warehousing', 'BBC', 'C', 2, 4],
            ['Caroline Atuhaire', 'Computer Networks', 'BBC', 'B', 1, 4],
            ['Godfrey Mujungu', 'Programming Theory and Problem Solving', 'BBC', 'A', 1, 5],
            ['Godfrey Mujungu', 'Business Application Programming', 'BBC', 'A', 2, 5],
            ['Godfrey Mujungu', 'Business Application Programming', 'BBC', 'B', 2, 5],
            ['Hajarah Ali Namuwaya', 'Web Server Administration', 'BBC', 'A', 3, 4],
            ['Hajarah Ali Namuwaya', 'Web Server Administration', 'BBC', 'B', 3, 4],
            ['Hajarah Ali Namuwaya', 'Web Server Administration', 'BBC', 'C', 3, 4],
            ['Hassan Were', 'E-Business and Web Design', 'BBA', 'B1', 2, 2],
            ['Hassan Were', 'E-Business and Web Design', 'BBA', 'B1', 2, 2],
            ['Hassan Were', 'E-Business and Web Design', 'BBA', 'C', 2, 4],
            ['Hassan Were', 'Systems Analysis and Design', 'BBC', 'C', 1, 4],
            ['Hillary Nagawa Mirembe', 'Database Design and Programming', 'BBC', 'B', 1, 5],
            ['Hillary Nagawa Mirembe', 'Database Design and Programming', 'BBC', 'A2', 1, 3],
            ['Hillary Nagawa Mirembe', 'Database Design and Programming', 'BBC', 'A2', 1, 2],
            ['Kennedy Turyasingura', 'Internet & Emerging Technologies', 'BSM', 'B', 1, 5],
            ['Nasser Wangubo', 'Internet & Emerging Technologies', 'BPSCM', 'D', 1, 5],
            ['Peter Kikanja', 'E-Business and Web Design', 'BBA', 'C', 2, 4],
            ['Ronnie Arinda', 'Internet & Emerging Technologies', 'BPSCM', 'B', 1, 5],
            ['Samuel Ssendi', 'Computer Networks', 'BBC', 'A', 1, 4],
            ['Samuel Ssendi', 'Advanced Routing and Switching', 'BBC', 'A', 3, 4],
            ['Samuel Ssendi', 'Advanced Routing and Switching', 'BBC', 'B', 3, 4],
            ['Samuel Ssendi', 'Enterprise Network Administration & Management', 'BOIM', 'A', 3, 4],
            ['Stella Kyalimpa', 'Programming Theory and Problem Solving', 'BBC', 'A', 1, 5],
            ['Stella Kyalimpa', 'Programming Theory and Problem Solving', 'BBC', 'B', 1, 5],
            ['Swaleh Ssessanga', 'Computerised Data Analysis', 'BOIM', 'B', 2, 3],
            ['Swaleh Ssessanga', 'Document Production', 'BOIM', 'A', 2, 4],
            ['Swaleh Ssessanga', 'Programming Theory and Problem Solving', 'BBC', 'C', 1, 5],
            ['Winnie Kisaakye', 'Business Intelligence and Data Warehousing', 'BBC', 'B', 2, 4],

            // ═══ CSE ═══
            ['Sonny Nyeko', 'Systems Analysis and Design', 'BBC', 'A', 1, 4],
            ['Edward Kabaale', 'Software Engineering for Business', 'BBC', 'A', 3, 4],
            ['Edward Kabaale', 'Software Testing and Documentation', 'BBC', 'A', 3, 4],
            ['Ruqqaiya Naluwooza', 'Systems Analysis and Design', 'BBC', 'B', 1, 4],
            ['Ruqqaiya Naluwooza', 'Systems Analysis and Design', 'BBC', 'A', 1, 4],
            ['Ruqqaiya Naluwooza', 'Management Information Systems', 'BOIM', 'A', 2, 3],
            ['Ruqqaiya Naluwooza', 'Management Information Systems', 'BOIM', 'B', 2, 3],
            ['Samuel Eelu', 'Enterprise Network Administration & Management', 'BOIM', 'A', 3, 4],
            ['Samuel Eelu', 'Enterprise Network Administration & Management', 'BOIM', 'B', 3, 4],
            ['Bonface Abima', 'IT Project Management', 'BOIM', 'B', 3, 4],
            ['Bonface Abima', 'IT Project Management', 'BBC', 'C', 3, 4],
            ['Shamim Kemigisha', 'Office Administration and Management', 'BOIM', 'B', 1, 4],
            ['John Magala', 'E-Business and Web Design', 'BBA', 'B', 2, 4],
            ['John Magala', 'Web Application Development', 'BBC', 'B', 2, 5],
            ['John Magala', 'Web Application Development', 'BBC', 'C', 2, 5],
            ['John Magala', 'Database Design and Programming', 'BBC', 'A1', 1, 5],
            ['Maria Miiro', 'E-Business and Web Design', 'BBA', 'C', 2, 4],
            ['Maria Miiro', 'E-Business and Web Design', 'BBA', 'B', 2, 2],
            ['Maria Miiro', 'E-Business and Web Design', 'BBA', 'D', 2, 4],
            ['Philip Kato Khatiya', 'E-Business and Web Design', 'BBA', 'A', 2, 2],
            ['Philip Kato Khatiya', 'E-Business and Web Design', 'BBA', 'D', 2, 4],
            ['Philip Kato Khatiya', 'Internet & Emerging Technologies', 'BOIM', 'A', 1, 4],
            ['Albert Miwanda', 'Internet & Emerging Technologies', 'BOIM', 'B', 1, 4],
            ['Edward Miiro', 'Business Application Programming', 'BBC', 'A', 2, 5],
            ['Edward Miiro', 'Business Application Programming', 'BBC', 'C', 2, 5],
            ['Louis Amwine', 'Software Engineering for Business', 'BBC', 'B', 2, 4],
            ['Louis Amwine', 'Software Engineering for Business', 'BBC', 'C', 2, 4],
            ['Louis Amwine', 'Computer Networks', 'BBC', 'C', 1, 4],
            ['Francis Byabazire', 'Software Engineering for Business', 'BBC', 'B', 2, 4],
            ['Richard Tumusiime', 'Computer Networks', 'BBC', 'B', 1, 4],
            ['Linda Lisa Kainomugisha', 'Software Engineering for Business', 'BBC', 'A', 2, 4],
            ['Linda Lisa Kainomugisha', 'Software Testing and Documentation', 'BBC', 'A', 3, 4],
            ['Linda Lisa Kainomugisha', 'Software Testing and Documentation', 'BBC', 'B', 3, 4],
            ['Linda Lisa Kainomugisha', 'Software Testing and Documentation', 'BBC', 'C', 3, 4],
            ['Elizabeth Namutebi', 'Software Engineering for Business', 'BBC', 'B', 2, 4],
            ['Elizabeth Namutebi', 'Software Engineering for Business', 'BBC', 'C', 2, 4],
            ['Elizabeth Namutebi', 'Software Testing and Documentation', 'BBC', 'B', 3, 4],
            ['Elizabeth Namutebi', 'Software Testing and Documentation', 'BBC', 'C', 3, 4],
            ['Amah Dopia Brenda', 'Computer Key Board Skills I', 'BOIM', 'B', 1, 4],
            ['Amah Dopia Brenda', 'Shorthand I', 'BOIM', 'A', 1, 4],
            ['Amah Dopia Brenda', 'Computer Key Board Skills III', 'BOIM', 'A', 3, 4],
            ['Amah Dopia Brenda', 'Shorthand III', 'BOIM', 'B', 2, 4],

            // ═══ IS ═══
            ['Kasule Abdal', 'Business Intelligence and Data Warehousing', 'BBC', 'A', 2, 4],
            ['Kasule Abdal', 'Advanced Mobile Application Development', 'BBC', 'A', 3, 4],
            ['Engotoit Bernard', 'Internet & Emerging Technologies', 'BOIM', 'A', 1, 4],
            ['Engotoit Bernard', 'Shorthand I', 'BOIM', 'B', 1, 4],
            ['Engotoit Bernard', 'Computer Key Board Skills III', 'BOIM', 'A', 2, 4],
            ['Engotoit Bernard', 'Shorthand III', 'BOIM', 'B', 2, 3],
            ['Lugemwa Bryan', 'Computing Ethics', 'BBC', 'A', 3, 3],
            ['Lugemwa Bryan', 'Computing Ethics', 'BOIM', 'A', 3, 4],
            ['Barbara N. Kayondo', 'Project Research Methods', 'BBC', 'B', 2, 4],
            ['Barbara N. Kayondo', 'Project Research Methods', 'BOIM', 'C', 2, 4],
            ['Barbara N. Kayondo', 'Project Research Methods', 'BOIM', 'A', 2, 4],
            ['Mukuuma Kassim', 'Database Design and Programming', 'BBC', 'B', 1, 5],
            ['Mukuuma Kassim', 'Business Application Programming', 'BBC', 'B', 2, 5],
            ['Namakula Sarah', 'Document Production', 'BOIM', 'A', 2, 4],
            ['Namakula Sarah', 'Document Production', 'BOIM', 'B', 2, 4],
            ['Nansamba Christine', 'Business Intelligence and Data Warehousing', 'BBC', 'C', 2, 4],
            ['Olupot Charles', 'Enterprise Information Resource Management', 'BOIM', 'A', 2, 3],
            ['Byomire Gorretti', 'Internet & Emerging Technologies', 'BOIM', 'B', 1, 4],
            ['Byomire Gorretti', 'E-Business and Web Design', 'BOIM', 'A', 2, 4],
            ['Nantege Zuhrah', 'Office Administration and Management', 'BOIM', 'B', 1, 4],
            ['Nantege Zuhrah', 'IT Project Management', 'BOIM', 'A', 3, 4],
            ['Nantege Zuhrah', 'Enterprise Information Resource Management', 'BOIM', 'A', 3, 3],
            ['Mutebi Bashir', 'Database Design and Programming', 'BBC', 'B', 1, 5],
            ['Mutebi Bashir', 'Web Application Development', 'BBC', 'A', 2, 5],
            ['Mutebi Bashir', 'Business Intelligence and Data Warehousing', 'BBC', 'B', 2, 4],
            ['Bukoma Sadat', 'Business Application Programming', 'BBC', 'A', 2, 5],
            ['Bukoma Sadat', 'Web Application Development', 'BBC', 'C', 2, 5],
            ['Makubuya Rogers', 'Advanced Mobile Application Development', 'BBC', 'A', 3, 4],
            ['Makubuya Rogers', 'Web Application Development', 'BBC', 'B', 2, 5],
            ['Makubuya Rogers', 'Advanced Mobile Application Development', 'BBC', 'C', 3, 4],
            ['Amal Josephine', 'E-Business and Web Design', 'BBA', 'A', 2, 3],
            ['Kinyiri Juma Balunywa', 'E-Business and Web Design', 'BBA', 'D', 2, 4],
            ['Kinyiri Juma Balunywa', 'Business Intelligence and Data Warehousing', 'BBC', 'C', 2, 4],
            ['Kinyiri Juma Balunywa', 'Database Design and Programming', 'BBC', 'C', 1, 5],
            ['Nassimbwa Angella', 'Internet & Emerging Technologies', 'BENT', 'A', 1, 5],
            ['Nassimbwa Angella', 'Internet & Emerging Technologies', 'BENT', 'B', 1, 5],
            ['Tebandeke Edrisa', 'E-Business and Web Design', 'BBA', 'D', 2, 4],
            ['Atuhurira Seith', 'Internet & Emerging Technologies', 'BPSCM', 'C', 1, 5],
            ['Ategeka Charles', 'Computerised Data Analysis', 'BOIM', 'A', 2, 3],
            ['Nakabirye Nuriat', 'Computer Key Board Skills I', 'BOIM', 'A', 1, 4],
            ['Nakabirye Nuriat', 'Shorthand I', 'BOIM', 'B', 1, 4],
            ['Nakabirye Nuriat', 'Computer Key Board Skills III', 'BOIM', 'B', 2, 4],
            ['Nakabirye Nuriat', 'Shorthand III', 'BOIM', 'A', 2, 3],
            ['Patricia Arionget', 'Office Administration and Management', 'BOIM', 'A', 1, 4],
            ['Joy Tiko', 'Database Design and Programming', 'BBC', 'A', 1, 5],
            ['Joy Tiko', 'Web Application Development', 'BBC', 'A', 2, 5],
            ['Joy Tiko', 'Business Application Programming', 'BBC', 'C', 2, 5],
            ['Nansubuga Annette Knolly', 'Management Information Systems', 'BOIM', 'A', 3, 3],
            ['Nansubuga Annette Knolly', 'Management Information Systems', 'BOIM', 'B', 3, 3],
        ];

        // Map common names to course codes
        $nameToCode = [
            'computerised data analysis' => 'BBC2205',
            'computer networks' => 'BUC1222',
            'advanced routing and switching' => 'BUC3218',
            'database design and programming' => 'BUC1224',
            'project research methods' => 'BUC3111',
            'it project management' => 'BUC3217',
            'office administration and management' => 'BUC1215',
            'systems analysis and design' => 'BUC1229',
            'computing ethics' => 'BUC3216',
            'web server administration' => 'BUC3219',
            'programming theory and problem solving' => 'BUC1223',
            'internet & emerging technologies' => 'BUC1219',
            'it project development' => 'BUC3213',
            'shorthand i' => 'BUC1218',
            'computer key board skills i' => 'BUC1220',
            'computer key board skills iii' => 'BUC2225',
            'shorthand iii' => 'BUC2227',
            'e-business and web design' => 'BBC2204',
            'document production' => 'BUC2226',
            'enterprise information resource management' => 'BUC2229',
            'management information systems' => 'BUC2228',
            'business intelligence and data warehousing' => 'BUC2230',
            'web application development' => 'BUC2231',
            'business application programming' => 'BUC2232',
            'software engineering for business' => 'BUC2233',
            'software testing and documentation' => 'BUC3220',
            'enterprise network administration & management' => 'BUC3230',
            'advanced mobile application development' => 'BUC3221',
        ];

        foreach ($assignments as $a) {
            $lecturerName = $a[0];
            $courseName = $a[1];
            $programCode = $a[2];
            $group = $a[3];
            $yearOfStudy = $a[4];
            $hrsPerWeek = $a[5];

            // Find lecturer
            $email = LecturerController::generateEmail($lecturerName);
            $lecturerId = $lecturerByEmail[strtolower($email)] ?? null;
            if (!$lecturerId) {
                $this->command->warn("Lecturer not found: {$lecturerName} ({$email})");
                $skipped++;
                continue;
            }

            // Find course
            $courseId = null;
            $cNameLower = strtolower(trim($courseName));

            // First try name-to-code mapping
            if (isset($nameToCode[$cNameLower])) {
                $code = strtolower($nameToCode[$cNameLower]);
                $courseId = $courseByCode[$code] ?? null;
            }

            // Then try direct name match
            if (!$courseId) {
                $courseId = $courseByName[$cNameLower] ?? null;
            }

            if (!$courseId) {
                $this->command->warn("Course not found: {$courseName}");
                $skipped++;
                continue;
            }

            // Check for duplicate
            $exists = DB::table('course_lecturer')
                ->where('lecturer_id', $lecturerId)
                ->where('course_id', $courseId)
                ->where('academic_year', $year)
                ->where('semester', $semester)
                ->where('program_code', $programCode)
                ->where('study_group', $group)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DB::table('course_lecturer')->insert([
                'lecturer_id' => $lecturerId,
                'course_id' => $courseId,
                'academic_year' => $year,
                'semester' => $semester,
                'program_code' => $programCode,
                'study_group' => $group,
                'year_of_study' => $yearOfStudy,
                'hours_per_week' => $hrsPerWeek,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inserted++;
        }

        $this->command->info("Teaching load seeded: {$inserted} assignments created, {$skipped} skipped.");
    }
}
