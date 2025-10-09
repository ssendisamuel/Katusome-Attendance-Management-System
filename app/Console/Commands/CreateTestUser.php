<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test {email=student@example.com} {--name=SMTP Test} {--password=Temp1234}';
    protected $description = 'Create or update a test user to verify SMTP sending';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => bcrypt($password),
        ]);

        $this->info('Test user ready: ' . $user->email);
        return self::SUCCESS;
    }
}