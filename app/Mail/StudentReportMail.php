<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $records;

    public function __construct(Student $student, Collection $records)
    {
        $this->student = $student;
        $this->records = $records;
    }

    public function build()
    {
        return $this->subject('Your Attendance Report - Katusome')
                    ->markdown('emails.student.report');
    }
}
