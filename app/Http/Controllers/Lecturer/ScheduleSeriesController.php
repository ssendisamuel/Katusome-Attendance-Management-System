<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSeries;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Group;
use App\Models\GenerationAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleSeriesController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);
        $lecturerId = $user->lecturer->id;

        // Show series where lecturer is assigned or created by them (though currently series has lecturer_id)
        $series = ScheduleSeries::with(['course', 'group'])
            ->where('lecturer_id', $lecturerId)
            ->orWhereHas('course.lecturers', function($q) use ($lecturerId) {
                $q->where('lecturers.id', $lecturerId);
            })
            ->latest()
            ->paginate(15);

        return view('lecturer.series.index', compact('series'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        $courses = $user->lecturer->courses;
        $groups = Group::all();
        $semesters = \App\Models\AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        $activeSemester = \App\Models\AcademicSemester::where('is_active', true)->first();

        return view('lecturer.series.create', compact('courses', 'groups', 'semesters', 'activeSemester'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);
        $assignedCourseIds = $user->lecturer->courses()->pluck('courses.id')->toArray();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id', function ($attribute, $value, $fail) use ($assignedCourseIds) {
                if (!in_array($value, $assignedCourseIds)) {
                     $fail('You are not assigned to this course.');
                }
            }],
            'group_id' => ['required', 'exists:groups,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_recurring' => ['boolean'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'is_online' => ['boolean'],
            'access_code' => ['nullable', 'string', 'max:50'],
        ]);

        if (empty($data['academic_semester_id'])) {
            $active = \App\Models\AcademicSemester::where('is_active', true)->value('id');
            if ($active) $data['academic_semester_id'] = $active;
        }

        // Force lecturer_id to self
        $data['lecturer_id'] = $user->lecturer->id;

        ScheduleSeries::create($data);
        return redirect()->route('lecturer.series.index')->with('success', 'Schedule series created');
    }

    public function edit(ScheduleSeries $series)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        if ($series->lecturer_id !== $user->lecturer->id && !$user->lecturer->courses->contains($series->course_id)) {
            abort(403);
        }

        $courses = $user->lecturer->courses;
        $groups = Group::all(); // Or filtered
        $semesters = \App\Models\AcademicSemester::orderByDesc('year')->orderByDesc('semester')->get();
        return view('lecturer.series.edit', compact('series', 'courses', 'groups', 'semesters'));
    }

    public function update(Request $request, ScheduleSeries $series)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        if ($series->lecturer_id !== $user->lecturer->id && !$user->lecturer->courses->contains($series->course_id)) {
            abort(403);
        }

        $assignedCourseIds = $user->lecturer->courses()->pluck('courses.id')->toArray();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id', function ($attribute, $value, $fail) use ($assignedCourseIds) {
                if (!in_array($value, $assignedCourseIds)) {
                     $fail('You are not assigned to this course.');
                }
            }],
            'group_id' => ['required', 'exists:groups,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'is_recurring' => ['boolean'],
            'academic_semester_id' => ['nullable', 'exists:academic_semesters,id'],
            'is_online' => ['boolean'],
            'access_code' => ['nullable', 'string', 'max:50'],
        ]);

        $series->update($data);
        return redirect()->route('lecturer.series.index')->with('success', 'Schedule series updated');
    }

    public function destroy(ScheduleSeries $series)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        // Only allow deleting own series
        if ($series->lecturer_id !== $user->lecturer->id) {
            abort(403, 'You can only delete series created by you.');
        }

        $series->delete();
        return redirect()->route('lecturer.series.index')->with('success', 'Schedule series deleted');
    }

    public function generateSchedules(Request $request, ScheduleSeries $series)
    {
        $user = Auth::user();
        if (!$user->lecturer) abort(403);

        if ($series->lecturer_id !== $user->lecturer->id && !$user->lecturer->courses->contains($series->course_id)) {
            abort(403);
        }

        $data = $request->validate([
            'overwrite' => ['nullable', 'boolean'],
            'skip_overlaps' => ['nullable', 'boolean'],
        ]);

        $overwrite = (bool)($data['overwrite'] ?? false);
        $skipOverlaps = (bool)($data['skip_overlaps'] ?? false);

        $days = collect($series->days_of_week ?? [])->map(function ($d) {
                $dd = strtolower(trim($d));
                $map = ['mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu', 'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun'];
                return $map[$dd] ?? $dd;
            })->filter()->unique()->values();

        if ($days->isEmpty()) {
            return redirect()->back()->with('error', 'Series has no days_of_week.');
        }

        $startDate = Carbon::parse($series->start_date)->startOfDay();
        $endDate = $series->end_date ? Carbon::parse($series->end_date)->endOfDay() : $startDate->copy()->endOfDay();

        if ($startDate->gt($endDate)) {
            return redirect()->back()->with('error', 'Invalid date range.');
        }

        if ($overwrite) {
            Schedule::where('series_id', $series->id)->delete();
        }

        $createdCount = 0;
        $createdDates = [];
        $cursor = $startDate->copy();

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
                            $q->where('start_at', '<', $endAt)->where('end_at', '>', $startAt);
                        })->exists();
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
                        'is_online' => $series->is_online,
                        'access_code' => $series->access_code,
                        'academic_semester_id' => $series->academic_semester_id,
                        'end_at' => $endAt,
                    ]
                );
                if ($schedule->wasRecentlyCreated) {
                    $createdCount++;
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

        return redirect()->route('lecturer.series.index')
            ->with('success', $createdCount . ' schedules generated.');
    }
}
