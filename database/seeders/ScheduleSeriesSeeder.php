<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\ScheduleSeries;

class ScheduleSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::first();
        $group = Group::first();
        $lecturer = Lecturer::first();

        if (! $course || ! $group || ! $lecturer) {
            return; // prerequisites not met
        }

        $seriesData = [
            [
                'name' => 'Intro Week',
                'location' => 'Room A',
                'start_date' => Carbon::now()->startOfWeek()->toDateString(),
                'end_date' => Carbon::now()->startOfWeek()->addWeeks(1)->toDateString(),
                'start_time' => Carbon::createFromTime(9, 0)->toTimeString(),
                'end_time' => Carbon::createFromTime(11, 0)->toTimeString(),
                'days_of_week' => ['Mon', 'Wed'],
                'is_recurring' => true,
            ],
            [
                'name' => 'Midterm Prep',
                'location' => 'Room B',
                'start_date' => Carbon::now()->addWeeks(2)->startOfWeek()->toDateString(),
                'end_date' => Carbon::now()->addWeeks(3)->endOfWeek()->toDateString(),
                'start_time' => Carbon::createFromTime(14, 0)->toTimeString(),
                'end_time' => Carbon::createFromTime(16, 0)->toTimeString(),
                'days_of_week' => ['Tue', 'Thu'],
                'is_recurring' => true,
            ],
            [
                'name' => 'Final Review',
                'location' => 'Auditorium',
                'start_date' => Carbon::now()->addWeeks(4)->startOfWeek()->toDateString(),
                'end_date' => Carbon::now()->addWeeks(4)->endOfWeek()->toDateString(),
                'start_time' => Carbon::createFromTime(10, 0)->toTimeString(),
                'end_time' => Carbon::createFromTime(12, 0)->toTimeString(),
                'days_of_week' => ['Fri'],
                'is_recurring' => false,
            ],
        ];

        foreach ($seriesData as $data) {
            ScheduleSeries::firstOrCreate(
                [
                    'name' => $data['name'],
                    'course_id' => $course->id,
                    'group_id' => $group->id,
                    'lecturer_id' => $lecturer->id,
                ],
                [
                    'location' => $data['location'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'days_of_week' => $data['days_of_week'],
                    'is_recurring' => $data['is_recurring'],
                ]
            );
        }
    }
}