<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\WelcomeUserMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class SendLatestWelcome extends Command
{
    protected $signature = 'mail:test-welcome-latest {role=student}';
    protected $description = 'Send a welcome email immediately via SMTP to the latest created user for the given role';

    public function handle(): int
    {
        $role = (string) $this->argument('role');
        $user = User::where('role', $role)->orderByDesc('id')->first();
        if (!$user) { $this->error('No user found with role: ' . $role); return self::FAILURE; }
        $initial = 'password';
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
        $loginUrl = url(route('login', [], false));
        try {
            Mail::mailer('smtp')->to($user->email)->send(new WelcomeUserMail($user, $initial, $resetUrl, $loginUrl));
            $this->info('Sent welcome email immediately to ' . $user->email . ' (role: ' . $role . ')');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to send: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}