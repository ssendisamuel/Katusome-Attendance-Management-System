<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSeries;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Group;
use App\Models\Lecturer;
use App\Models\GenerationAudit;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleSeriesController extends Controller
{
    public function index()
    {
        $series = ScheduleSeries::with(['course', 'group', 'lecturer'])->paginate(15);
        $audits = \App\Models\GenerationAudit::with(['series', 'user'])->latest()->paginate(10);
        return view('admin.series.index', compact('series', 'audits'));
    }

    public function create()
    {
        $courses = Course::all();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        return view('admin.series.create', compact('courses', 'groups', 'lecturers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_recurring' => ['boolean'],
        ]);

        ScheduleSeries::create($data);
        return redirect()->route('admin.series.index')->with('success', 'Schedule series created');
    }

    public function edit(ScheduleSeries $series)
    {
        $courses = Course::all();
        $groups = Group::all();
        $lecturers = Lecturer::all();
        return view('admin.series.edit', compact('series', 'courses', 'groups', 'lecturers'));
    }

    public function update(Request $request, ScheduleSeries $series)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'lecturer_id' => ['nullable', 'exists:lecturers,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_recurring' => ['boolean'],
        ]);

        $series->update($data);
        return redirect()->route('admin.series.index')->with('success', 'Schedule series updated');
    }

    public function destroy(ScheduleSeries $series)
    {
        $series->delete();
        return redirect()->route('admin.series.index')->with('success', 'Schedule series deleted');
    }

    /**
     * Generate schedules from the given series across its date range.
     */
    public function generateSchedules(Request $request, ScheduleSeries $series)
    {
        // Validate optional inputs to control generation behavior
        $data = $request->validate([
            'overwrite' => ['nullable', 'boolean'], // if true, delete existing schedules for series before generating
            'skip_overlaps' => ['nullable', 'boolean'], // if true, skip creating schedules that overlap existing ones for the same group
        ]);

        $overwrite = (bool)($data['overwrite'] ?? false);
        $skipOverlaps = (bool)($data['skip_overlaps'] ?? false);

        // Normalize days_of_week to lowercase short names (mon..sun)
        $days = collect($series->days_of_week ?? [])
            ->map(function ($d) {
                $dd = strtolower(trim($d));
                // allow inputs like 'Mon' or 'monday'
                $map = [
                    'mon' => 'mon', 'monday' => 'mon',
                    'tue' => 'tue', 'tues' => 'tue', 'tuesday' => 'tue',
                    'wed' => 'wed', 'wednesday' => 'wed',
                    'thu' => 'thu', 'thurs' => 'thu', 'thursday' => 'thu',
                    'fri' => 'fri', 'friday' => 'fri',
                    'sat' => 'sat', 'saturday' => 'sat',
                    'sun' => 'sun', 'sunday' => 'sun',
                ];
                return $map[$dd] ?? $dd;
            })
            ->filter()
            ->unique()
            ->values();

        if ($days->isEmpty()) {
            return redirect()->route('admin.series.index')
                ->with('error', 'Series has no days_of_week; cannot generate schedules.');
        }

        // Determine start and end dates
        $startDate = Carbon::parse($series->start_date)->startOfDay();
        $endDate = $series->end_date ? Carbon::parse($series->end_date)->endOfDay() : $startDate->copy()->endOfDay();

        if ($startDate->gt($endDate)) {
            return redirect()->route('admin.series.index')->with('error', 'Invalid date range in series.');
        }

        // Optionally remove previously generated schedules for this series
        if ($overwrite) {
            Schedule::where('series_id', $series->id)->delete();
        }

        // Build a map from day name to Carbon dayOfWeek (0=Sun ... 6=Sat)
        $dayToDow = [
            'sun' => Carbon::SUNDAY,
            'mon' => Carbon::MONDAY,
            'tue' => Carbon::TUESDAY,
            'wed' => Carbon::WEDNESDAY,
            'thu' => Carbon::THURSDAY,
            'fri' => Carbon::FRIDAY,
            'sat' => Carbon::SATURDAY,
        ];

        $createdCount = 0;
        $createdDates = [];

        // Iterate through each day in the range and create schedules on matching days
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dowName = strtolower($cursor->format('D')); // mon,tue,... in short form

            // Map 'Mon' to 'mon' etc
            $dowNorm = match ($dowName) {
                'sun' => 'sun', 'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat',
                default => $dowName,
            };

            if ($days->contains($dowNorm)) {
                // Compose start_at and end_at by combining date with series times
                $startAt = Carbon::parse($cursor->toDateString() . ' ' . $series->start_time->format('H:i'));
                $endAt = Carbon::parse($cursor->toDateString() . ' ' . $series->end_time->format('H:i'));

                // Optional overlap check within same group
                if ($skipOverlaps) {
                    $overlapExists = Schedule::where('group_id', $series->group_id)
                        ->where(function ($q) use ($startAt, $endAt) {
                            $q->where('start_at', '<', $endAt)
                              ->where('end_at', '>', $startAt);
                        })
                        ->exists();
                    if ($overlapExists) {
                        $cursor->addDay();
                        continue;
                    }
                }

                // Avoid duplicates: use firstOrCreate keyed by course/group/lecturer/date/time
                $schedule = Schedule::firstOrCreate(
                    [
                        'course_id' => $series->course_id,
                        'group_id' => $series->group_id,
                        'lecturer_id' => $series->lecturer_id,
                        'start_at' => $startAt,
                    ],
                    [
                        'series_id' => $series->id,
                        'location' => $series->location,
                        'end_at' => $endAt,
                    ]
                );
                // Count only newly created
                if ($schedule->wasRecentlyCreated) {
                    $createdCount++;
                    $createdDates[] = $startAt->toDateTimeString();
                }
            }

            $cursor->addDay();
        }

        // Audit log entry
        GenerationAudit::create([
            'user_id' => auth()->id(),
            'series_id' => $series->id,
            'generated_dates' => $createdDates,
            'overwrite' => $overwrite,
            'skip_overlaps' => $skipOverlaps,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('success', $createdCount . ' schedules generated from series "' . $series->name . '"');
    }

    /**
     * Bulk generate schedules for all series that cover the current date.
     */
    public function generateAll(Request $request)
    {
        $data = $request->validate([
            'overwrite' => ['nullable', 'boolean'],
            'skip_overlaps' => ['nullable', 'boolean'],
        ]);

        $overwrite = (bool)($data['overwrite'] ?? false);
        $skipOverlaps = (bool)($data['skip_overlaps'] ?? false);

        $today = Carbon::today();
        $seriesList = ScheduleSeries::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $totalCreated = 0;
        foreach ($seriesList as $series) {
            // Reuse existing single-series generator by emulating request
            // Normalize days & iterate similar to generateSchedules
            // We'll perform the same generation inline to avoid refactor

            // Normalize days_of_week to lowercase short names (mon..sun)
            $days = collect($series->days_of_week ?? [])
                ->map(function ($d) {
                    $dd = strtolower(trim($d));
                    $map = [
                        'mon' => 'mon', 'monday' => 'mon',
                        'tue' => 'tue', 'tues' => 'tue', 'tuesday' => 'tue',
                        'wed' => 'wed', 'wednesday' => 'wed',
                        'thu' => 'thu', 'thurs' => 'thu', 'thursday' => 'thu',
                        'fri' => 'fri', 'friday' => 'fri',
                        'sat' => 'sat', 'saturday' => 'sat',
                        'sun' => 'sun', 'sunday' => 'sun',
                    ];
                    return $map[$dd] ?? $dd;
                })
                ->filter()
                ->unique()
                ->values();

            if ($days->isEmpty()) {
                continue;
            }

            $startDate = Carbon::parse($series->start_date)->startOfDay();
            $endDate = $series->end_date ? Carbon::parse($series->end_date)->endOfDay() : $startDate->copy()->endOfDay();
            if ($startDate->gt($endDate)) {
                continue;
            }

            if ($overwrite) {
                Schedule::where('series_id', $series->id)->delete();
            }

            $cursor = $startDate->copy();
            $createdDates = [];
            while ($cursor->lte($endDate)) {
                $dowName = strtolower($cursor->format('D'));
                $dowNorm = match ($dowName) {
                    'sun' => 'sun', 'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat',
                    default => $dowName,
                };
                if ($days->contains($dowNorm)) {
                    $startAt = Carbon::parse($cursor->toDateString() . ' ' . $series->start_time->format('H:i'));
                    $endAt = Carbon::parse($cursor->toDateString() . ' ' . $series->end_time->format('H:i'));

                    if ($skipOverlaps) {
                        $overlapExists = Schedule::where('group_id', $series->group_id)
                            ->where(function ($q) use ($startAt, $endAt) {
                                $q->where('start_at', '<', $endAt)
                                  ->where('end_at', '>', $startAt);
                            })
                            ->exists();
                        if ($overlapExists) {
                            $cursor->addDay();
                            continue;
                        }
                    }

                    $schedule = Schedule::firstOrCreate(
                        [
                            'course_id' => $series->course_id,
                            'group_id' => $series->group_id,
                            'lecturer_id' => $series->lecturer_id,
                            'start_at' => $startAt,
                        ],
                        [
                            'series_id' => $series->id,
                            'location' => $series->location,
                            'end_at' => $endAt,
                        ]
                    );
                    if ($schedule->wasRecentlyCreated) {
                        $totalCreated++;
                        $createdDates[] = $startAt->toDateTimeString();
                    }
                }
                $cursor->addDay();
            }

            GenerationAudit::create([
                'user_id' => auth()->id(),
                'series_id' => $series->id,
                'generated_dates' => $createdDates,
                'overwrite' => $overwrite,
                'skip_overlaps' => $skipOverlaps,
            ]);
        }

        return redirect()->route('admin.schedules.index')
            ->with('success', $totalCreated . ' schedules generated across ' . $seriesList->count() . ' series');
    }
}