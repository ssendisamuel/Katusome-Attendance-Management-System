<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: create a building then its rooms
        $make = function (string $building, array $rooms = []) {
            $b = Venue::firstOrCreate(['name' => $building, 'parent_id' => null]);
            foreach ($rooms as $room) {
                Venue::firstOrCreate(['name' => $room, 'parent_id' => $b->id]);
            }
        };

        // ── Main Campus (Nakawa) ──

        $make('ADB Building', [
            'ADB Theatre 1', 'ADB Theatre 2', 'ADB Demo Room',
            'ADB Lab 1', 'ADB Lab 2', 'ADB Lab 3', 'ADB Lab 4',
            'ADB Resource Room',
        ]);

        $make('Short Tower', [
            'Basement', 'Level 1', 'Level 2', 'Level 3',
        ]);

        $make('Former Minister\'s Block', [
            'Upper', 'Room 1', 'Room 2',
        ]);

        $make('Block 2');

        $make('Block 3', ['Room 1', 'Room 2', 'Upper']);

        $make('Block 4', ['Room 1', 'Room 2', 'Room 3']);

        $make('Block 5', ['Room 1', 'Room 2']);

        $make('Block 7', ['Room 2']);

        $make('Block 8');

        $make('Block 12', ['Room 1', 'Room 2']);

        $make('Block G', ['Room 1', 'Room 2', 'Upper']);

        $make('Kamya House', ['Room 2', 'Room 3']);

        $make('Kisubi House', ['Room 1', 'Room 2']);

        $make('Walusansa');

        $make('Main Library', [
            'Meeting Room', 'Audio Visual Room',
            '1st Floor', '2nd Floor', '3rd Floor', '4th Floor',
        ]);

        $make('Former GRC Library');

        $make('Former Library Upper');

        $make('Former Library Lower');

        $make('Main Building Computer Labs', [
            'Lab 3.1', 'Lab 3.3a', 'Lab 3.3b', 'Lab 3.4a', 'Lab 3.6',
        ]);

        $make('Main Building Rooms', ['RM 3.31']);

        $make('Catering Unit');

        $make('Digital Lab');

        $make('Conference Room');

        $make('Board Room');
    }
}
