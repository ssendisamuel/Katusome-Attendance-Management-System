<x-mail::message>
    # Attendance Summary

    Dear {{ $student->name }},

    This email confirms your attendance record for **{{ $schedule->course->name }}** ({{ $schedule->course->code }}).

    <x-mail::table>
        | Detail | Value |
        | :--- | :--- |
        | **Date** | {{ $schedule->start_at->format('D, M j, Y') }} |
        | **Time** | {{ $schedule->start_at->format('H:i') }} - {{ $schedule->end_at->format('H:i') }} |
        | **Clock In** | {{ $attendance->marked_at->format('H:i') }} |
        | **Clock Out** |
        {{ $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : 'Auto-Clocked Out' }} |
        | **Status** | {{ ucfirst($attendance->status) }} |
        | **Platform** | {{ ucfirst($attendance->platform ?? 'Web') }} |
        | **IP Address** | {{ $attendance->ip_address ?? 'N/A' }} |
    </x-mail::table>

    @if ($attendance->is_auto_clocked_out)
        <x-mail::panel>
            **Note:** You were automatically clocked out because you did not clock out before the session limit. This
            may affect your attendance record.
        </x-mail::panel>
    @endif

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
