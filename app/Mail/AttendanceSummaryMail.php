<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $schedule;
    public $attendance;

    public function __construct($student, $schedule, $attendance)
    {
        $this->student = $student;
        $this->schedule = $schedule;
        $this->attendance = $attendance;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Attendance Summary: ' . $this->schedule->course->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.attendance.summary',
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
}
