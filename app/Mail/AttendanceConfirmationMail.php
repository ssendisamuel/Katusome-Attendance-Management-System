<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;

class AttendanceConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;


    public Student $student;
    public Schedule $schedule;
    public Attendance $attendance;

    /**
     * Create a new message instance.
     */
    public function __construct(Student $student, Schedule $schedule, Attendance $attendance)
    {
        $this->student = $student;
        $this->schedule = $schedule;
        $this->attendance = $attendance;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $courseName = optional($this->schedule->course)->name;
        $subject = 'Attendance confirmation' . ($courseName ? (' for ' . $courseName) : '');
        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $selfieUrl = null;
        if ($this->attendance->selfie_path) {
            // Only expose selfie if it exists on the public disk
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->attendance->selfie_path)) {
                // Public disk URL (requires storage:link)
                $selfieUrl = url(Storage::url($this->attendance->selfie_path));
            }
        }

        // Prefer a dedicated summary page for the specific attendance record.
        // Fallback to schedule check-in page if the attendance isn't persisted yet.
        $checkinUrl = $this->attendance->id
            ? route('attendance.summary', $this->attendance->id)
            : route('attendance.checkin.show', $this->schedule->id);

        return new Content(
            markdown: 'emails.attendance_confirmation',
            with: [
                'student' => $this->student,
                'schedule' => $this->schedule,
                'attendance' => $this->attendance,
                'selfieUrl' => $selfieUrl,
                'checkinUrl' => $checkinUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    // Queued mailable for async delivery
}