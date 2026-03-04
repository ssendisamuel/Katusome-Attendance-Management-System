<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\LecturerController;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        $deptACIT = Department::where('code', 'ACIT')->value('id');
        $deptCSE = Department::where('code', 'CSE')->value('id');
        $deptIS = Department::where('code', 'IS')->value('id');

        $lecturers = [
            // ═══ ACIT ═══
            ['name' => 'Musa Moya', 'title' => 'Prof.', 'designation' => 'Professor', 'dept' => $deptACIT],
            ['name' => 'Robert Kyeyune', 'title' => 'Assoc. Prof.', 'designation' => 'Associate Professor', 'dept' => $deptACIT],
            ['name' => 'Samali V. Mlay', 'title' => 'Assoc. Prof.', 'designation' => 'Associate Professor', 'dept' => $deptACIT],
            ['name' => 'Abdul Male Ssentumbwe', 'title' => 'Dr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Sumaya M. Kagoya', 'title' => 'Dr.', 'designation' => 'Senior Lecturer', 'dept' => $deptACIT],
            ['name' => 'Robinah Nabafu', 'title' => 'Dr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Abdallah Ibrahim Nyero', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Abdul Ddamba', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Catherine Nyesiga', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Elizabeth Asianzu Ezati', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Fatinah Nakabonge', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Ismael Kato', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Josephine Namataba', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Moses Serugo', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Philper Irene Tusubira', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Stella Eva Nakalema', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptACIT],
            ['name' => 'Ali Balunywa', 'title' => 'Mr.', 'designation' => 'Assistant Lecturer', 'dept' => $deptACIT],
            ['name' => 'Aisha Namome Watsemba', 'title' => 'Ms.', 'designation' => 'Assistant Lecturer', 'dept' => $deptACIT],
            ['name' => 'Afulah Namatovu', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Aisha Mwesigye', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Benedict Ogot', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Caroline Atuhaire', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Godfrey Mujungu', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Hajarah Ali Namuwaya', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Hassan Were', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Hillary Nagawa Mirembe', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Kennedy Turyasingura', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Nasser Wangubo', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Peter Kikanja', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Ronnie Arinda', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Samuel Ssendi', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Stella Kyalimpa', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Swaleh Ssessanga', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],
            ['name' => 'Winnie Kisaakye', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptACIT],

            // ═══ CSE ═══
            ['name' => 'Sonny Nyeko', 'title' => 'Prof.', 'designation' => 'Associate Professor', 'dept' => $deptCSE],
            ['name' => 'Kituyi Mayoka Geofrey', 'title' => 'Prof.', 'designation' => 'Associate Professor', 'dept' => $deptCSE],
            ['name' => 'Edward Kabaale', 'title' => 'Dr.', 'designation' => 'Senior Lecturer', 'dept' => $deptCSE],
            ['name' => 'Ruqqaiya Naluwooza', 'title' => 'Dr.', 'designation' => 'Senior Lecturer', 'dept' => $deptCSE],
            ['name' => 'Samuel Eelu', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Bonface Abima', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Shamim Kemigisha', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'John Magala', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Maria Miiro', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Philip Kato Khatiya', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Albert Miwanda', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Edward Miiro', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Christine Amulen J', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Joanina Ayebare', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptCSE],
            ['name' => 'Louis Amwine', 'title' => 'Mr.', 'designation' => 'Assistant Lecturer', 'dept' => $deptCSE],
            ['name' => 'Francis Byabazire', 'title' => 'Mr.', 'designation' => 'Assistant Lecturer', 'dept' => $deptCSE],
            ['name' => 'Richard Tumusiime', 'title' => 'Mr.', 'designation' => 'Assistant Lecturer', 'dept' => $deptCSE],
            ['name' => 'Linda Lisa Kainomugisha', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptCSE],
            ['name' => 'Elizabeth Namutebi', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptCSE],
            ['name' => 'Amah Dopia Brenda', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptCSE],
            ['name' => 'Judith Among', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptCSE],

            // ═══ IS ═══
            ['name' => 'John Paul Kasse', 'title' => 'Dr.', 'designation' => 'Senior Lecturer', 'dept' => $deptIS],
            ['name' => 'Kasule Abdal', 'title' => 'Dr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Engotoit Bernard', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Lugemwa Bryan', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Barbara N. Kayondo', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Mukuuma Kassim', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Nakawoya Fatumah', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Namakula Sarah', 'title' => 'Dr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Nansamba Christine', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Olupot Charles', 'title' => 'Mr.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Byomire Gorretti', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Nantege Zuhrah', 'title' => 'Ms.', 'designation' => 'Lecturer', 'dept' => $deptIS],
            ['name' => 'Mutebi Bashir', 'title' => 'Mr.', 'designation' => 'Assistant Lecturer', 'dept' => $deptIS],
            ['name' => 'Bukoma Sadat', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Makubuya Rogers', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Amal Josephine', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Kinyiri Juma Balunywa', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Nassimbwa Angella', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Tebandeke Edrisa', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Atuhurira Seith', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Ategeka Charles', 'title' => 'Mr.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Nakabirye Nuriat', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Patricia Arionget', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Joy Tiko', 'title' => 'Ms.', 'designation' => 'Teaching Assistant', 'dept' => $deptIS],
            ['name' => 'Nansubuga Annette Knolly', 'title' => 'Ms.', 'designation' => 'Assistant Lecturer', 'dept' => $deptIS],
        ];

        foreach ($lecturers as $l) {
            $email = LecturerController::generateEmail($l['name']);

            // Skip if already exists
            if (User::where('email', $email)->exists()) {
                // Update existing lecturer's title, designation, department
                $user = User::where('email', $email)->first();
                $lecturer = Lecturer::where('user_id', $user->id)->first();
                if ($lecturer) {
                    $lecturer->update([
                        'title' => $l['title'],
                        'designation' => $l['designation'],
                        'department_id' => $l['dept'],
                    ]);
                }
                continue;
            }

            $user = User::create([
                'name' => $l['name'],
                'email' => $email,
                'password' => Hash::make('password'),
                'must_change_password' => true,
                'role' => 'lecturer',
            ]);

            Lecturer::create([
                'user_id' => $user->id,
                'title' => $l['title'],
                'designation' => $l['designation'],
                'department_id' => $l['dept'],
            ]);
        }

        $this->command->info('Seeded ' . count($lecturers) . ' lecturers from FCI teaching load data.');
    }
}
