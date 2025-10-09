<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\WelcomeUserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class EnqueueWelcomeMail extends Command
{
    protected $signature = 'mail:enqueue-welcome {email} {--password=password}';
    protected $description = 'Send a welcome email immediately for the given user email via SMTP';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        if (!$user) { $this->error('User not found: ' . $email); return self::FAILURE; }
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
        $loginUrl = url(route('login', [], false));
        $initial = (string) $this->option('password');
        Mail::to($user->email)->send(new WelcomeUserMail($user, $initial, $resetUrl, $loginUrl));
        $this->info('Sent welcome email immediately for ' . $user->email);
        return self::SUCCESS;
    }
}