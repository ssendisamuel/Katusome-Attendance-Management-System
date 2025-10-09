@component('mail::message')
# Attendance Confirmed

Hello {{ optional($student->user)->name }},

Your attendance has been recorded successfully.

- Course: **{{ optional($schedule->course)->name ?? '—' }}**
- Time: **{{ optional($attendance->marked_at)->format('Y-m-d h:i A') }}**
- Status: **{{ ucfirst($attendance->status) }}**

@if(isset($selfieUrl))
@component('mail::panel')
Selfie captured at check-in:

<img src="{{ $selfieUrl }}" alt="Attendance selfie" style="max-width: 280px; border-radius: 8px;" />
@endcomponent
@endif

@component('mail::button', ['url' => $checkinUrl])
View Attendance Summary
@endcomponent

Thanks,
{{ config('app.name') }} Team
@endcomponent