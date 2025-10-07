<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Backoff timing between retries in seconds.
     */
    public $backoff = [60, 120, 300, 600];

    public User $user;
    public string $initialPassword;
    public string $resetUrl;
    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $initialPassword, string $resetUrl, string $loginUrl)
    {
        $this->user = $user;
        $this->initialPassword = $initialPassword;
        $this->resetUrl = $resetUrl;
        $this->loginUrl = $loginUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'user' => $this->user,
                'initialPassword' => $this->initialPassword,
                'resetUrl' => $this->resetUrl,
                'loginUrl' => $this->loginUrl,
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
}
