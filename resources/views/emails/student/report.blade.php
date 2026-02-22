@component('mail::message')
    # Attendance Report

    Hello **{{ $student->name }}**,

    Here is your requested attendance report.

    @component('mail::table')
        | Date | Course | Time In | Status |
        | :--- | :--- | :--- | :--- |
        @foreach ($records as $record)
            | {{ $record->marked_at->format('d M Y') }} | {{ optional($record->schedule->course)->code }} |
            {{ $record->marked_at->format('H:i') }} | {{ ucfirst($record->status) }} |
        @endforeach
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
