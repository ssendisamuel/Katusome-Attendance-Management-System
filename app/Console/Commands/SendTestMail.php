<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestMail extends Command
{
    protected $signature = 'mail:test-send {to} {--subject=SMTP Test} {--body=This is a test email sent via SMTP.}';
    protected $description = 'Send a simple SMTP test email to a given address';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $subject = (string) $this->option('subject');
        $body = (string) $this->option('body');

        try {
            Mail::mailer('smtp')->raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            $this->info('Sent SMTP test email to ' . $to);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to send SMTP test email: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}