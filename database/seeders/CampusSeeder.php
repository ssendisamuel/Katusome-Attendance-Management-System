<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['name' => 'Main Campus', 'code' => 'MAIN', 'location' => 'Nakawa, Kampala'],
            ['name' => 'Arua Campus', 'code' => 'ARUA', 'location' => 'Arua'],
            ['name' => 'Jinja Campus', 'code' => 'JINJA', 'location' => 'Jinja'],
            ['name' => 'Mbale Campus', 'code' => 'MBALE', 'location' => 'Mbale'],
            ['name' => 'Mbarara Campus', 'code' => 'MBARARA', 'location' => 'Mbarara'],
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['code' => $campus['code']],
                $campus
            );
        }
    }
}
