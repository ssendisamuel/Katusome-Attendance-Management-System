<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Providers\AuthServiceProvider;
use App\Console\Commands\SyncIdentityDuplicates;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        SyncIdentityDuplicates::class,
    ])
    ->withProviders([
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);
        $middleware->web(App\Http\Middleware\ForcePasswordChange::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
