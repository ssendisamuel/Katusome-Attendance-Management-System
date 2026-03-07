<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', function (User $user) {
            $currentRole = $user->getCurrentRole();
            return in_array($currentRole, ['admin', 'super_admin', 'principal', 'registrar', 'campus_chief', 'qa_director', 'dean', 'hod']);
        });

        Gate::define('lecturer', function (User $user) {
            return $user->hasRole('lecturer');
        });

        Gate::define('student', function (User $user) {
            return $user->hasRole('student');
        });
    }
}
