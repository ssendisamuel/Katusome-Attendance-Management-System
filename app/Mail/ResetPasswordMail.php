<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    use Queueable;

    public function __construct(public \App\Models\User $user, public string $token)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Your Password');
    }

    public function content(): Content
    {
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $this->user->email], false));
        return new Content(
            view: 'emails.reset-password',
            with: [
                'user' => $this->user,
                'url' => $url,
            ]
        );
    }
}