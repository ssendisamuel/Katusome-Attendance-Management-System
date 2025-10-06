<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Schedule;
use App\Models\ScheduleSeries;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::first();
        $group = Group::first();
        $lecturer = Lecturer::first();
        $seriesList = ScheduleSeries::take(3)->get();

        if (! $course || ! $group || ! $lecturer) {
            return;
        }

        $base = Carbon::now()->startOfWeek();
        $slots = [
            ['dayOffset' => 0, 'start' => '09:00', 'end' => '11:00', 'location' => 'Room A'],
            ['dayOffset' => 1, 'start' => '14:00', 'end' => '16:00', 'location' => 'Room B'],
            ['dayOffset' => 4, 'start' => '10:00', 'end' => '12:00', 'location' => 'Auditorium'],
        ];

        foreach ($slots as $i => $slot) {
            $start = $base->copy()->addDays($slot['dayOffset'])->setTime(...explode(':', $slot['start']));
            $end = $base->copy()->addDays($slot['dayOffset'])->setTime(...explode(':', $slot['end']));

            Schedule::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'group_id' => $group->id,
                    'lecturer_id' => $lecturer->id,
                    'start_at' => $start,
                ],
                [
                    'series_id' => optional($seriesList->get($i))->id,
                    'location' => $slot['location'],
                    'end_at' => $end,
                ]
            );
        }
    }
}